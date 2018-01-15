<?php

namespace App\Http\Controllers;

use Output;
use Log;
use DB;
use App\Models\File;
use App\Models\Layer;
use App\Models\Component;
use App\Models\FileComponent;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ComponentController extends Controller
{
    /**
     * 新建组件
     *
     * @param Request $request
     * @param int $id File ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function createComponent(Request $request, $id)
    {
        $file = File::where('id', $id)->where('status', File::STATUS_NORMAL)->first();

        if (is_null($file)) {
            return Output::error(trans('common.file_not_found'), 60000, [], Response::HTTP_BAD_REQUEST);
        }

        if ($file->userId != $request->user()->id) {
            return Output::error(trans('common.illegal_operation'), 60001, [], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($request->input('name', 'untitled'));
        $public = $request->input('public', 'false');

        $nameLen = mb_strlen($name, 'UTF-8');

        if ($nameLen <= 0 || $name > 100) {
            return Output::error(trans('common.invalid_component_name'), 60002, [], Response::HTTP_BAD_REQUEST);
        }

        if (!is_bool($public) && !is_numeric($public)) {
            $public = strtolower($public) === 'true';
        }

        $public = intval(boolval($public));

        try {
            $component = Component::createComponent($name, $request->user()->id, null, $public, $id);
        } catch (\Exception $e) {
            static::log($e);
            return Output::error(trans('common.server_is_busy'), 60003, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (is_null($component)) {
            Log::info('create component error', [
                'user' => [
                    'userId' => $request->user()->id
                ],
                'file' => [
                    'name'   => $name,
                    'public' => $public
                ]
            ]);

            return Output::error(trans('common.server_is_busy'), 60004, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $component = [
            'id'        => $component->id,
            'name'      => $component->name,
            'userId'    => $component->userId,
            'teamId'    => $component->teamId,
            'access'    => $component->access == 1 ? 'PUBLIC' : 'PRIVATE',
            'fileId'    => intval($id),
            'editable'  => $component->userId == $request->user()->id,
            'deletable' => $component->userId == $request->user()->id,
            'createdAt' => strtotime($component->createdAt),
            'updatedAt' => is_null($component->updatedAt) ? null : strtotime($component->updatedAt)
        ];

        return Output::ok($component);
    }

    /**
     * 删除组件
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteComponent(Request $request, $id)
    {
        $component = Component::where('id', $id)->where('status', Component::STATUS_NORMAL)->first();

        if (is_null($component)) {
            return Output::error(trans('common.component_not_found'), 60100, [], Response::HTTP_BAD_REQUEST);
        }

        if ($component->userId !== $request->user()->id) {
            return Output::error(trans('common.illegal_operation'), 60101, [], Response::HTTP_BAD_REQUEST);
        }

        $data = [
            'status'    => Component::STATUS_DELETED,
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        try {
            $affected = Component::where('id', $id)
                ->where('userId', $request->user()->id)
                ->where('status', Component::STATUS_NORMAL)->update($data);
        } catch (\Exception $e) {
            static::log($e);
            return Output::error(trans('common.server_is_busy'), 60102, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($affected > 0) {
            return Output::ok();
        }

        return Output::error(trans('common.operation_failed'), 60103);
    }

    /**
     * 获取组件
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getComponent(Request $request, $id)
    {
        $component = Component::where('id', $id)->where('status', Component::STATUS_NORMAL)->first();

        if (is_null($component)) {
            return Output::error(trans('common.component_not_found'), 60200, [], Response::HTTP_BAD_REQUEST);
        }

        if ($component->userId !== $request->user()->id && $component->access != Component::ACCESS_PUBLIC) {
            return Output::error(trans('common.component_not_found'), 60201, [], Response::HTTP_BAD_REQUEST);
        }

        $component = [
            'id'        => $component->id,
            'name'      => $component->name,
            'userId'    => $component->userId,
            'teamId'    => $component->teamId,
            'access'    => $component->access == 1 ? 'PUBLIC' : 'PRIVATE',
            'editable'  => $component->userId == $request->user()->id,
            'deletable' => $component->userId == $request->user()->id,
            'createdAt' => strtotime($component->createdAt),
            'updatedAt' => is_null($component->updatedAt) ? null : strtotime($component->updatedAt)
        ];

        return Output::ok($component);
    }

    /**
     * 获取用户组件列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getComponents(Request $request)
    {
        $offset = (int)$request->input('offset', 0);
        $limit = (int)$request->input('limit', Component::DEFAULT_LIST_COUNT);

        $offset = $offset < 0 ? 0 : $offset;
        $limit = ($offset < 0 || $offset > Component::DEFAULT_LIST_COUNT) ? Component::DEFAULT_LIST_COUNT : $limit;

        $components = Component::getUserComponents($request->user()->id, $offset, $limit);

        return Output::ok([
            'components' => $components
        ]);
    }

    /**
     * 获取文件相关组件列表
     *
     * @param Request $request
     * @param int $id file id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFileComponents(Request $request, $id)
    {
        $file = File::where('id', $id)->where('status', File::STATUS_NORMAL)->first();

        if (is_null($file)) {
            return Output::error(trans('common.file_not_found'), 60500, [], Response::HTTP_BAD_REQUEST);
        }

        if ($file->userId != $request->user()->id && $file->access != File::ACCESS_PUBLIC) {
            return Output::error(trans('common.file_not_found'), 60501, [], Response::HTTP_BAD_REQUEST);
        }

        $offset = (int)$request->input('offset', 0);
        $limit = (int)$request->input('limit', Component::DEFAULT_LIST_COUNT);

        $offset = $offset < 0 ? 0 : $offset;
        $limit = ($offset < 0 || $offset > Component::DEFAULT_LIST_COUNT) ? Component::DEFAULT_LIST_COUNT : $limit;

        $builder = Component::where('fileId', $id)
            ->where('status', Component::STATUS_NORMAL)
            ->orderBy('id', 'DESC');

        if ($offset > 0) {
            $builder->where('id', '<', $offset);
        }

        $components = $builder->limit($limit)->get()->toArray();

        if (empty($components)) {
            return Output::ok([]);
        }

        foreach ($components as & $component) {
            $component['access'] = $component['access'] == 1 ? 'PUBLIC' : 'PRIVATE';
            $component['createdAt'] = strtotime($component['createdAt']);
            $component['updatedAt'] = is_null($component['updatedAt']) ? null : strtotime($component['updatedAt']);

            unset($component['status']);
        }

        $components = array_column($components, null, 'id');
        $componentIds = array_keys($components);

        foreach ($componentIds as $componentId) {
            $component = Component::where('id', $componentId)->where('status', Component::STATUS_NORMAL)->first();

            if (is_null($component)) {
                return Output::error(trans('common.component_not_found'), 60502, [], Response::HTTP_BAD_REQUEST);
            }

            $layers = Layer::getComponentLayers($componentId);
            $layers = Layer::filterLayers($layers);

            $components[$componentId]['layer'] = $layers;
        }

        return Output::ok(array_values($components));
    }

    /**
     * 更新组件
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateComponent(Request $request, $id)
    {
        $component = Component::where('id', $id)->where('status', Component::STATUS_NORMAL)->first();

        if (is_null($component)) {
            return Output::error(trans('common.component_not_found'), 60400, [], Response::HTTP_BAD_REQUEST);
        }

        if ($component->userId != $request->user()->id) {
            return Output::error(trans('common.illegal_operation'), 60401, [], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($request->input('name', ''));
        $public = trim($request->input('public', ''));

        if ($name == '' && $public == '') {
            return Output::error(trans('common.bad_request'), 60402, [], Response::HTTP_BAD_REQUEST);
        }

        $data = [
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        if ('' !== $name) {
            $data['name'] = $name;
        }

        if ('' !== $public) {
            if (!is_bool($public) && !is_numeric($public)) {
                $public = strtolower($public) === 'true';
            }

            $public = intval(boolval($public));
            $data['access'] = "{$public}";
        }

        try {
            $affected = Component::where('id', $id)
                ->where('userId', $request->user()->id)
                ->where('status', Component::STATUS_NORMAL)
                ->update($data);
        } catch (\Exception $e) {
            static::log($e);
            return Output::error(trans('common.server_is_busy'), 60403, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($affected > 0) {
            return Output::ok();
        }

        return Output::error(trans('common.operation_failed'), 60404);
    }
}
