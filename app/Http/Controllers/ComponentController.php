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
    public function createComponent($request)
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
}
