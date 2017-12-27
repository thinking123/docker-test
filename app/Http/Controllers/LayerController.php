<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\File;
use App\Models\Layer;
use Output;
use Log;
use Validator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LayerController extends Controller
{
    /**
     * 创建文件下属 Layer
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function createFileLayer(Request $request, $id)
    {
        $file = File::where('status', File::STATUS_NORMAL)->find($id);

        if (is_null($file) || $file->userId != $request->user()->id) {
            return Output::error(trans('common.file_not_found'), 50000, [], Response::HTTP_BAD_REQUEST);
        }

        $inputs = $request->only(['name', 'parent', 'before', 'type', 'data', 'styles']);

        $rules = [
            'name'   => 'required|length:1,100',
            'parent' => 'integer|min:0',
            'before' => 'required|integer|min:0',
            'type'   => 'required|in:screen,text,image,box,icon,slot',
        ];

        $messages = [
            'name.required'   => trans('common.param_required', ['param' => 'name']),
            'name.length'     => trans('common.invalid_param_length', ['param' => 'name']),
            'parent.integer'  => trans('common.param_must_be_int', ['param' => 'parent']),
            'parent.min'      => trans('common.invalid_parameter_value', ['param' => 'parent']),
            'before.required' => trans('common.param_required', ['param' => 'before']),
            'before.integer'  => trans('common.param_must_be_int', ['param' => 'before']),
            'before.min'      => trans('common.invalid_parameter_value', ['param' => 'before']),
            'type.required'   => trans('common.param_required', ['param' => 'type']),
            'type.in'         => trans('common.invalid_parameter_value', ['param' => 'type'])
        ];

        $validator = Validator::make($inputs, $rules, $messages);

        if ($validator->fails()) {
            return Output::error($validator->errors()->first(), 50001, $inputs, Response::HTTP_BAD_REQUEST);
        }

        if ($inputs['parent'] > 0) {
            $parent = Layer::where('status', Layer::STATUS_NORMAL)->find($inputs['parent']);

            if (is_null($parent)) {
                return Output::error(trans('common.layer_not_found', ['param' => $inputs['parent']]), 50002, [],
                    Response::HTTP_BAD_REQUEST);
            }

            if (is_null($parent->fileId) || $parent->fileId != $id) {
                return Output::error(trans('common.illegal_operation', ['param' => $inputs['parent']]), 50003, [],
                    Response::HTTP_BAD_REQUEST);
            }

            if ($parent->type != 1 && $parent->type != 4) {
                return Output::error(trans('common.illegal_operation', ['param' => $inputs['parent']]), 50004, [],
                    Response::HTTP_BAD_REQUEST);
            }
        }

        $before = null;

        if ($inputs['before'] > 0) {
            $before = Layer::where('fileId', $id)->where('parentId', $inputs['parent'])->where('status',
                Layer::STATUS_NORMAL)->find($inputs['before']);

            if (is_null($before)) {
                return Output::error(trans('common.layer_not_found', ['param' => $inputs['before']]), 50005, [],
                    Response::HTTP_BAD_REQUEST);
            }
        }

        $beforePosition = is_null($before) ? 0 : $before->position;

        $after = Layer::where('fileId', $id)
            ->where('parentId', $inputs['parent'])
            ->where('position', '>', $beforePosition)
            ->where('status', Layer::STATUS_NORMAL)
            ->orderBy('position', 'ASC')
            ->first();

        $afterPosition = is_null($after) ? $beforePosition + 10 : $after->position;

        $layer = new Layer();

        $layer->name = $inputs['name'];
        $layer->type = Layer::getTypeIdByName($inputs['type']);
        $layer->fileId = $id;
        $layer->componentId = null;
        $layer->parentId = $inputs['parent'];
        $layer->position = ($beforePosition + $afterPosition) / 2;
        $layer->data = isset($inputs['data']) && !is_null(json_decode($inputs['data'])) ? $inputs['data'] : '{}';
        $layer->styles = isset($inputs['styles']) && !is_null(json_decode($inputs['styles'])) ? $inputs['styles'] : '{}';
        $layer->status = Layer::STATUS_NORMAL;
        $layer->createdAt = date('Y-m-d H:i:s');

        if (!$layer->save()) {
            return Output::error(trans('common.server_is_busy'), 50006, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $layer = Layer::where('fileId', $id)->where('status', Layer::STATUS_NORMAL)->find($layer->id)->toArray();

        if (is_null($layer)) {
            return Output::error(trans('common.server_is_busy'), 50007, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return Output::ok(Layer::filter($layer));
    }

    /**
     * 删除 Layer
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteLayer(Request $request, $id)
    {
        $layer = Layer::where('status', Layer::STATUS_NORMAL)->find($id);

        if (is_null($layer)) {
            return Output::error(trans('common.layer_not_found', ['param' => $id]), 50100, [],
                Response::HTTP_BAD_REQUEST);
        }

        $file = File::where('id', $layer->fileId)->where('status', File::STATUS_NORMAL)->first();

        if (is_null($file)) {
            return Output::error(trans('common.server_is_busy'), 50101, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($file->userId != $request->user()->id) {
            return Output::error(trans('common.layer_not_found', ['param' => $id]), 50102, [],
                Response::HTTP_BAD_REQUEST);
        }

        $data = [
            'parent'    => null,
            'position'  => null,
            'status'    => Layer::STATUS_DELETED,
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        $affected = Layer::where('id', $id)->where('status', Layer::STATUS_NORMAL)->update($data);

        if ($affected == 0) {
            return Output::error(trans('common.server_is_busy'), 50103, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return Output::ok();
    }

    /**
     * 删除 Layer
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function editLayer(Request $request, $id)
    {
        $layer = Layer::where('status', Layer::STATUS_NORMAL)->find($id);

        if (is_null($layer)) {
            return Output::error(trans('common.layer_not_found', ['param' => $id]), 50200, [],
                Response::HTTP_BAD_REQUEST);
        }

        $file = File::where('id', $layer->fileId)->where('status', File::STATUS_NORMAL)->first();

        if (is_null($file)) {
            return Output::error(trans('common.server_is_busy'), 50201, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($file->userId != $request->user()->id) {
            return Output::error(trans('common.layer_not_found', ['param' => $id]), 50202, [],
                Response::HTTP_BAD_REQUEST);
        }

        $inputs = $request->only(['name', 'parent', 'before', 'data', 'styles']);

        $rules = [
            'name'   => 'required|length:1,100',
            'parent' => 'integer|min:0',
            'before' => 'required|integer|min:0',
        ];

        $messages = [
            'name.required'   => trans('common.param_required', ['param' => 'name']),
            'name.length'     => trans('common.invalid_param_length', ['param' => 'name']),
            'parent.integer'  => trans('common.param_must_be_int', ['param' => 'parent']),
            'parent.min'      => trans('common.invalid_parameter_value', ['param' => 'parent']),
            'before.required' => trans('common.param_required', ['param' => 'before']),
            'before.integer'  => trans('common.param_must_be_int', ['param' => 'before']),
            'before.min'      => trans('common.invalid_parameter_value', ['param' => 'before']),
        ];

        $validator = Validator::make($inputs, $rules, $messages);

        if ($validator->fails()) {
            return Output::error($validator->errors()->first(), 50203, $inputs, Response::HTTP_BAD_REQUEST);
        }

        if ($inputs['parent'] > 0) {
            $parent = Layer::where('fileId', $layer->fileId)->where('status',
                Layer::STATUS_NORMAL)->find($inputs['parent']);

            if (is_null($parent)) {
                return Output::error(trans('common.layer_not_found', ['param' => $inputs['parent']]), 50204, [],
                    Response::HTTP_BAD_REQUEST);
            }
        }

        $before = null;

        if ($inputs['before'] > 0) {
            $before = Layer::where('fileId', $layer->fileId)->where('parentId', $inputs['parent'])->where('status',
                Layer::STATUS_NORMAL)->find($inputs['before']);

            if (is_null($before)) {
                return Output::error(trans('common.layer_not_found', ['param' => $inputs['before']]), 50205, [],
                    Response::HTTP_BAD_REQUEST);
            }
        }

        $beforePosition = is_null($before) ? 0 : $before->position;

        $after = Layer::where('fileId', $layer->fileId)
            ->where('parentId', $inputs['parent'])
            ->where('position', '>', $beforePosition)
            ->where('status', Layer::STATUS_NORMAL)
            ->orderBy('position', 'ASC')
            ->first();

        $afterPosition = is_null($after) ? $beforePosition + 10 : $after->position;

        $data = [
            'name'      => $inputs['name'],
            'parentId'  => $inputs['parent'],
            'position'  => ($beforePosition + $afterPosition) / 2,
            'data'      => isset($inputs['data']) && !is_null(json_decode($inputs['data'])) ? $inputs['data'] : '{}',
            'styles'    => isset($inputs['styles']) && !is_null(json_decode($inputs['styles'])) ? $inputs['styles'] : '{}',
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        $affected = Layer::where('id', $id)->where('status', Layer::STATUS_NORMAL)->update($data);

        if ($affected == 0) {
            return Output::error(trans('common.server_is_busy'), 50206, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $layer = Layer::where('status', Layer::STATUS_NORMAL)->find($layer->id)->toArray();

        if (is_null($layer)) {
            return Output::error(trans('common.server_is_busy'), 50207, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return Output::ok(Layer::filter($layer));
    }

    /**
     * 获取 Layer
     *
     * @param Request $request
     * @param int $id file id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFileLayers(Request $request, $id)
    {
        $file = File::where('id', $id)->where('status', File::STATUS_NORMAL)->first();

        if (is_null($file)) {
            return Output::error(trans('common.file_not_found'), 50300, [], Response::HTTP_BAD_REQUEST);
        }

        if ($file->userId != $request->user()->id && $file->access != File::ACCESS_PUBLIC) {
            return Output::error(trans('common.file_not_found'), 50301, [], Response::HTTP_BAD_REQUEST);
        }

        $layers = Layer::getFileLayers($id);

        return Output::ok($layers);
    }

    /**
     * 获取 Layer 的后代 Layer
     *
     * @param Request $request
     * @param int $id layer id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLayerChildren(Request $request, $id)
    {
        $layer = Layer::where('id', $id)->where('status', Layer::STATUS_NORMAL)->first();

        if (is_null($layer)) {
            return Output::error(trans('common.layer_not_found', ['param' => $id]), 50400, [],
                Response::HTTP_BAD_REQUEST);
        }

        $file = File::where('id', $layer->fileId)->where('status', File::STATUS_NORMAL)->first();

        if (is_null($file)) {
            return Output::error(trans('common.file_not_found'), 50401, [], Response::HTTP_BAD_REQUEST);
        }

        if ($file->userId != $request->user()->id && $file->access != File::ACCESS_PUBLIC) {
            return Output::error(trans('common.file_not_found'), 50402, [], Response::HTTP_BAD_REQUEST);
        }

        $layers = Layer::getLayerChildren([$id]);

        return Output::ok($layers);
    }

    /**
     * 创建组件下属 Layer
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function createComponentLayer(Request $request, $id)
    {
        $component = Component::where('status', Component::STATUS_NORMAL)->find($id);

        if (is_null($component) || $component->userId != $request->user()->id) {
            return Output::error(trans('common.component_not_found'), 50500, [], Response::HTTP_BAD_REQUEST);
        }

        $inputs = $request->only(['name', 'parent', 'before', 'type', 'data', 'styles']);

        $rules = [
            'name'   => 'required|length:1,100',
            'parent' => 'integer|min:0',
            'before' => 'required|integer|min:0',
            'type'   => 'required|in:screen,text,image,box,icon,slot',
        ];

        $messages = [
            'name.required'   => trans('common.param_required', ['param' => 'name']),
            'name.length'     => trans('common.invalid_param_length', ['param' => 'name']),
            'parent.integer'  => trans('common.param_must_be_int', ['param' => 'parent']),
            'parent.min'      => trans('common.invalid_parameter_value', ['param' => 'parent']),
            'before.required' => trans('common.param_required', ['param' => 'before']),
            'before.integer'  => trans('common.param_must_be_int', ['param' => 'before']),
            'before.min'      => trans('common.invalid_parameter_value', ['param' => 'before']),
            'type.required'   => trans('common.param_required', ['param' => 'type']),
            'type.in'         => trans('common.invalid_parameter_value', ['param' => 'type'])
        ];

        $validator = Validator::make($inputs, $rules, $messages);

        if ($validator->fails()) {
            return Output::error($validator->errors()->first(), 50501, $inputs, Response::HTTP_BAD_REQUEST);
        }

        if ($inputs['parent'] > 0) {
            $parent = Layer::where('componentId', $id)->where('status', Layer::STATUS_NORMAL)->find($inputs['parent']);

            if (is_null($parent)) {
                return Output::error(trans('common.layer_not_found', ['param' => $inputs['parent']]), 50502, [],
                    Response::HTTP_BAD_REQUEST);
            }

            if ($parent->type != 1 && $parent->type != 4) {
                return Output::error(trans('common.illegal_operation', ['param' => $inputs['parent']]), 50503, [],
                    Response::HTTP_BAD_REQUEST);
            }
        }

        $before = null;

        if ($inputs['before'] > 0) {
            $before = Layer::where('componentId', $id)->where('parentId', $inputs['parent'])->where('status',
                Layer::STATUS_NORMAL)->find($inputs['before']);

            if (is_null($before)) {
                return Output::error(trans('common.layer_not_found', ['param' => $inputs['before']]), 50504, [],
                    Response::HTTP_BAD_REQUEST);
            }
        }

        $beforePosition = is_null($before) ? 0 : $before->position;

        $after = Layer::where('componentId', $id)
            ->where('parentId', $inputs['parent'])
            ->where('position', '>', $beforePosition)
            ->where('status', Layer::STATUS_NORMAL)
            ->orderBy('position', 'ASC')
            ->first();

        $afterPosition = is_null($after) ? $beforePosition + 10 : $after->position;

        $layer = new Layer();

        $layer->name = $inputs['name'];
        $layer->type = Layer::getTypeIdByName($inputs['type']);
        $layer->fileId = null;
        $layer->componentId = $id;
        $layer->parentId = $inputs['parent'];
        $layer->position = ($beforePosition + $afterPosition) / 2;
        $layer->data = isset($inputs['data']) && !is_null(json_decode($inputs['data'])) ? $inputs['data'] : '{}';
        $layer->styles = isset($inputs['styles']) && !is_null(json_decode($inputs['styles'])) ? $inputs['styles'] : '{}';
        $layer->status = Layer::STATUS_NORMAL;
        $layer->createdAt = date('Y-m-d H:i:s');

        if (!$layer->save()) {
            return Output::error(trans('common.server_is_busy'), 50505, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $layer = Layer::where('componentId', $id)->where('status', Layer::STATUS_NORMAL)->find($layer->id)->toArray();

        if (is_null($layer)) {
            return Output::error(trans('common.server_is_busy'), 50506, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return Output::ok(Layer::filter($layer));
    }

    /**
     * 获取组件 Layer 列表
     *
     * @param Request $request
     * @param int $id file id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getComponentLayers(Request $request, $id)
    {
        $component = Component::where('id', $id)->where('status', Component::STATUS_NORMAL)->first();

        if (is_null($component)) {
            return Output::error(trans('common.component_not_found'), 50600, [], Response::HTTP_BAD_REQUEST);
        }

        if ($component->userId != $request->user()->id && $component->access != Component::ACCESS_PUBLIC) {
            return Output::error(trans('common.component_not_found'), 50601, [], Response::HTTP_BAD_REQUEST);
        }

        $layers = Layer::getComponentLayers($id);

        return Output::ok($layers);
    }
}
