<?php

namespace App\Http\Controllers;

use Output;
use Log;
use App\Models\Component;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ComponentController extends Controller
{
    /**
     * 新建组件
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createComponent(Request $request)
    {
        $name = trim($request->input('name', 'untitled'));
        $public = trim($request->input('public', '0'));

        $nameLen = mb_strlen($name, 'UTF-8');

        if ($nameLen <= 0 || $name > 100) {
            return Output::error(trans('common.invalid_component_name'), 60000, [], Response::HTTP_BAD_REQUEST);
        }

        $public = intval(boolval($public));

        try {
            $component = Component::createComponent($name, $request->user()->id, null, $public);
        } catch (\Exception $e) {
            static::log($e);
            return Output::error(trans('common.server_is_busy'), 60001, [], Response::HTTP_INTERNAL_SERVER_ERROR);
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

            return Output::error(trans('common.server_is_busy'), 60002, [], Response::HTTP_INTERNAL_SERVER_ERROR);
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
}
