<?php

namespace App\Http\Controllers;

use App\Jobs\TransformJob;
use App\Models\Component;
use App\Models\File;
use App\Models\FileComponent;
use App\Models\Layer;
use DB;
use Output;
use Log;
use Validator;
use Redis;
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

        $layer->data = '{}';
        $layer->referenceTo = null;
        if (isset($inputs['data']) && !is_null($data = json_decode($inputs['data'], true))) {
            $layer->data = $inputs['data'];

            if ($layer->type == Layer::getTypeIdByName('slot') && isset($data['referenceTo']) && is_numeric($data['referenceTo'])) {
                $data['referenceTo'] = intval($data['referenceTo']);

                $component = Component::where('status', Component::STATUS_NORMAL)->find($data['referenceTo']);
                if (is_null($component) || $component->userId != $request->user()->id) {
                    return Output::error(trans('common.component_not_found'), 50006, [], Response::HTTP_BAD_REQUEST);
                }

                $layer->referenceTo = $data['referenceTo'];
            }
        }

        $layer->styles = isset($inputs['styles']) && !is_null(json_decode($inputs['styles'])) ? $inputs['styles'] : '{}';
        $layer->status = Layer::STATUS_NORMAL;
        $layer->createdAt = date('Y-m-d H:i:s');

        DB::beginTransaction();

        try {
            if (!$layer->save()) {
                throw new \Exception('Save layer failed.');
            }

            if (!is_null($layer->referenceTo)) {

                $fc = new FileComponent();

                $fc->fileId = $id;
                $fc->layerId = $layer->id;
                $fc->componentId = $layer->referenceTo;
                $fc->status = FileComponent::STATUS_NORMAL;

                if (!$fc->save()) {
                    throw new \Exception('Save file-component relationship failed.');
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            self::log($e);

            return Output::error(trans('common.server_is_busy'), 50007, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $layer = Layer::where('fileId', $id)->where('status', Layer::STATUS_NORMAL)->find($layer->id)->toArray();

        if (is_null($layer)) {
            return Output::error(trans('common.server_is_busy'), 50008, [], Response::HTTP_INTERNAL_SERVER_ERROR);
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

        if (is_null($layer->fileId) && is_null($layer->componentId)) {
            return Output::error(trans('common.server_is_busy'), 50101, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!is_null($layer->fileId)) {
            $file = File::where('id', $layer->fileId)->where('status', File::STATUS_NORMAL)->first();

            if (is_null($file)) {
                return Output::error(trans('common.server_is_busy'), 50102, [], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            if ($file->userId != $request->user()->id) {
                return Output::error(trans('common.layer_not_found', ['param' => $id]), 50103, [],
                    Response::HTTP_BAD_REQUEST);
            }
        } else {
            $component = Component::where('id', $layer->componentId)->where('status',
                Component::STATUS_NORMAL)->first();

            if (is_null($component)) {
                return Output::error(trans('common.server_is_busy'), 50104, [], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            if ($component->userId != $request->user()->id) {
                return Output::error(trans('common.layer_not_found', ['param' => $id]), 50105, [],
                    Response::HTTP_BAD_REQUEST);
            }
        }

        $data = [
            'parentId'  => null,
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

        if (is_null($layer->fileId) && is_null($layer->componentId)) {
            return Output::error(trans('common.server_is_busy'), 50201, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!is_null($layer->fileId)) {
            $file = File::where('id', $layer->fileId)->where('status', File::STATUS_NORMAL)->first();

            if (is_null($file)) {
                return Output::error(trans('common.server_is_busy'), 50202, [], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            if ($file->userId != $request->user()->id) {
                return Output::error(trans('common.layer_not_found', ['param' => $id]), 50203, [],
                    Response::HTTP_BAD_REQUEST);
            }
        } else {
            $component = Component::where('id', $layer->componentId)->where('status',
                Component::STATUS_NORMAL)->first();

            if (is_null($component)) {
                return Output::error(trans('common.server_is_busy'), 50204, [], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            if ($component->userId != $request->user()->id) {
                return Output::error(trans('common.layer_not_found', ['param' => $id]), 50205, [],
                    Response::HTTP_BAD_REQUEST);
            }
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
            if ($inputs['parent'] == $id) {
                return Output::error(trans('common.server_is_busy'), 50204, [], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $parent = Layer::where('fileId', $layer->fileId)->where('status',
                Layer::STATUS_NORMAL)->find($inputs['parent']);

            if (is_null($parent)) {
                return Output::error(trans('common.layer_not_found', ['param' => $inputs['parent']]), 50205, [],
                    Response::HTTP_BAD_REQUEST);
            }
        } elseif ($inputs['parent'] == 0) {
            if (!is_null($layer->componentId)) {
                $topCnt = Layer::where('componentId', $layer->componentId)->where('status', Layer::STATUS_NORMAL)
                    ->where('parentId', 0)->where('id', '!=', $id)->count();

                if ($topCnt > 0) {
                    return Output::error(trans('common.illegal_operation', ['param' => $inputs['parent']]), 50206, [],
                        Response::HTTP_BAD_REQUEST);
                }
            }
        }

        $before = null;

        if ($inputs['before'] > 0) {
            $before = Layer::where('fileId', $layer->fileId)->where('parentId', $inputs['parent'])->where('status',
                Layer::STATUS_NORMAL)->find($inputs['before']);

            if (is_null($before)) {
                return Output::error(trans('common.layer_not_found', ['param' => $inputs['before']]), 50207, [],
                    Response::HTTP_BAD_REQUEST);
            }
        }

        $beforePosition = is_null($before) ? 0 : $before->position;

        $after = Layer::where('fileId', $layer->fileId)
            ->where('parentId', $inputs['parent'])
            ->where('position', '>', $beforePosition)
            ->where('status', Layer::STATUS_NORMAL)
            ->where('id', '!=', $id)
            ->orderBy('position', 'ASC')
            ->first();

        $afterPosition = is_null($after) ? $beforePosition + 10 : $after->position;

        $data = [
            'name'        => $inputs['name'],
            'parentId'    => $inputs['parent'],
            'position'    => ($beforePosition + $afterPosition) / 2,
            'data'        => '{}',
            'referenceTo' => null,
            'styles'      => isset($inputs['styles']) && !is_null(json_decode($inputs['styles'])) ? $inputs['styles'] : '{}',
            'updatedAt'   => date('Y-m-d H:i:s')
        ];

        if (isset($inputs['data']) && !is_null($inputData = json_decode($inputs['data'], true))) {
            $data['data'] = $inputs['data'];

            if ($layer->type == Layer::getTypeIdByName('slot') && isset($inputData['referenceTo']) && is_numeric($inputData['referenceTo'])) {
                $inputData['referenceTo'] = intval($inputData['referenceTo']);

                if ($id == $inputData['referenceTo']) {
                    return Output::error(trans('common.illegal_operation'), 50208, [], Response::HTTP_BAD_REQUEST);
                }

                $component = Component::where('status', Component::STATUS_NORMAL)->find($inputData['referenceTo']);

                if (is_null($component) || $component->userId != $request->user()->id) {
                    return Output::error(trans('common.component_not_found'), 50209, [], Response::HTTP_BAD_REQUEST);
                }

                $data['referenceTo'] = $inputData['referenceTo'];
            }
        }

        try {
            $affected = Layer::where('id', $id)->where('status', Layer::STATUS_NORMAL)->update($data);

            if ($affected == 0) {
                throw new \Exception('Update layer failed');
            }

            if (!is_null($layer->fileId)) {
                if (!is_null($layer->referenceTo) && $layer->referenceTo != $data['referenceTo']) {
                    FileComponent::where('layerId', $layer->id)->where('componentId', $layer->referenceTo)
                        ->update(['status' => FileComponent::STATUS_DELETED]);
                }

                if (!is_null($data['referenceTo']) && $layer->referenceTo != $data['referenceTo']) {
                    $params = [
                        $layer->fileId,
                        $id,
                        $data['referenceTo'],
                        FileComponent::STATUS_NORMAL,
                        FileComponent::STATUS_NORMAL
                    ];

                    DB::statement("INSERT INTO `FileComponent` (`fileId`, `layerId`, `componentId`, `status`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `status` = ?",
                        $params);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            self::log($e);

            return Output::error(trans('common.server_is_busy'), 50210, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $layer = Layer::where('status', Layer::STATUS_NORMAL)->find($layer->id)->toArray();

        if (is_null($layer)) {
            return Output::error(trans('common.server_is_busy'), 50211, [], Response::HTTP_INTERNAL_SERVER_ERROR);
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
        $layers = Layer::filterLayers($layers);

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

        foreach ($layers as &$layer) {
            $layer = Layer::filterLayers($layer);
        }

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
        } elseif ($inputs['parent'] == 0) {
            $topCnt = Layer::where('componentId', $id)->where('status', Layer::STATUS_NORMAL)->where('parentId',
                0)->count();

            if ($topCnt > 0) {
                return Output::error(trans('common.illegal_operation', ['param' => $inputs['parent']]), 50504, [],
                    Response::HTTP_BAD_REQUEST);
            }
        }

        $before = null;

        if ($inputs['before'] > 0) {
            $before = Layer::where('componentId', $id)->where('parentId', $inputs['parent'])->where('status',
                Layer::STATUS_NORMAL)->find($inputs['before']);

            if (is_null($before)) {
                return Output::error(trans('common.layer_not_found', ['param' => $inputs['before']]), 50505, [],
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

        $layer->data = '{}';
        $layer->referenceTo = null;
        if (isset($inputs['data']) && !is_null($data = json_decode($inputs['data'], true))) {
            $layer->data = $inputs['data'];

            if ($layer->type == Layer::getTypeIdByName('slot') && isset($data['referenceTo']) && is_numeric($data['referenceTo'])) {
                $data['referenceTo'] = intval($data['referenceTo']);

                if ($id == $data['referenceTo']) {
                    return Output::error(trans('common.illegal_operation'), 50506, [], Response::HTTP_BAD_REQUEST);
                }

                $component = Component::where('status', Component::STATUS_NORMAL)->find($data['referenceTo']);
                if (is_null($component) || $component->userId != $request->user()->id) {
                    return Output::error(trans('common.component_not_found'), 50507, [], Response::HTTP_BAD_REQUEST);
                }

                $layer->referenceTo = $data['referenceTo'];
            }
        }

        $layer->styles = isset($inputs['styles']) && !is_null(json_decode($inputs['styles'])) ? $inputs['styles'] : '{}';
        $layer->status = Layer::STATUS_NORMAL;
        $layer->createdAt = date('Y-m-d H:i:s');

        if (!$layer->save()) {
            return Output::error(trans('common.server_is_busy'), 50508, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $layer = Layer::where('componentId', $id)->where('status', Layer::STATUS_NORMAL)->find($layer->id)->toArray();

        if (is_null($layer)) {
            return Output::error(trans('common.server_is_busy'), 50508, [], Response::HTTP_INTERNAL_SERVER_ERROR);
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
        $layers = Layer::filterLayers($layers);

        return Output::ok($layers);
    }

    /**
     * layer 与 component 互转
     *
     * @param Request $request
     * @param int $id layer id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addTransform(Request $request, $id)
    {
        $layer = Layer::where('status', Layer::STATUS_NORMAL)->find($id);

        if (is_null($layer)) {
            return Output::error(trans('common.layer_not_found', ['param' => $id]), 50700, [],
                Response::HTTP_BAD_REQUEST);
        }

        if (is_null($layer->fileId) && is_null($layer->componentId)) {
            return Output::error(trans('common.server_is_busy'), 50701, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!is_null($layer->fileId)) {
            $file = File::where('id', $layer->fileId)->where('status', File::STATUS_NORMAL)->first();

            if (is_null($file)) {
                return Output::error(trans('common.server_is_busy'), 50702, [], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            if ($file->userId != $request->user()->id) {
                return Output::error(trans('common.layer_not_found', ['param' => $id]), 50703, [],
                    Response::HTTP_BAD_REQUEST);
            }
        } else {
            $component = Component::where('id', $layer->componentId)->where('status',
                Component::STATUS_NORMAL)->first();

            if (is_null($component)) {
                return Output::error(trans('common.server_is_busy'), 50704, [], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            if ($component->userId != $request->user()->id) {
                return Output::error(trans('common.layer_not_found', ['param' => $id]), 50705, [],
                    Response::HTTP_BAD_REQUEST);
            }
        }

        $jobId = sha1(uniqid() . microtime(true) . $request->user()->id . rand(1, 99999999) . $id);

        try {
            $this->dispatch(new TransformJob($id, $jobId, $request->user()->id));
            Redis::set('job:' . $jobId, 'WAITING', 'EX', 3600);
        } catch (\Exception $e) {
            static::log($e);
            return Output::error(trans('common.server_is_busy'), 50706, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return Output::ok(['job' => $jobId]);
    }
}
