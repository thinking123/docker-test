<?php

namespace App\Http\Controllers;

use App\Models\IconLib;
use Output;
use Log;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IconController extends Controller
{
    /**
     * 获取某个人账户下全部可用的 icons
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIcons(Request $request)
    {
        $userId = $request->user()->id;

        $libs = IconLib::getIconLibs($userId);

        IconLib::filterLibs($libs);

        $data = [
            'libs' => $libs
        ];

        return Output::ok($data);
    }

    /**
     * 创建个人账户 icon lib
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createIconLib(Request $request)
    {
        $name = $request->input('name', '');
        $name = trim($name);

        if ('' === $name) {
            return Output::error(trans('common.invalid_icon_lib_name'), 120100);
        }

        $userId = $request->user()->id;

        $lib = IconLib::where('accountId', $userId)->where('accountType', IconLib::ACCOUNT_TYPE_PERSONAL)->where('name',
            $name)->where('status', IconLib::STATUS_NORMAL)->first();

        if (!is_null($lib)) {
            return Output::error(trans('common.icon_lib_with_same_name_exists'), 120101);
        }

        $lib = new IconLib();

        $lib->name = $name;
        $lib->accountId = $userId;
        $lib->accountType = IconLib::ACCOUNT_TYPE_PERSONAL;
        $lib->status = IconLib::STATUS_NORMAL;
        $lib->createdBy = $userId;
        $lib->createdAt = $lib->updatedAt = date('Y-m-d H:i:s');

        if (!$lib->save()) {
            return Output::error(trans('common.server_is_busy'), 120101, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $lib = IconLib::getIconLib($lib->id, $userId);

        IconLib::filter($lib);

        return Output::ok($lib);
    }

    /**
     * 更新 icon lib
     *
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    public function updateIconLib(Request $request, $id)
    {
        $name = $request->input('name', '');
        $name = trim($name);

        if ('' === $name) {
            return Output::error(trans('common.invalid_icon_lib_name'), 120200);
        }

        $userId = $request->user()->id;

        $lib = IconLib::where('id', $id)->where('status', IconLib::STATUS_NORMAL)->first();

        if (is_null($lib) || $lib->status != IconLib::STATUS_NORMAL || $lib->accountId != $userId || $lib->accountType != IconLib::ACCOUNT_TYPE_PERSONAL) {
            return Output::error(trans('common.icon_lib_not_found'), 120201, [], Response::HTTP_NOT_FOUND);
        }

        $another = IconLib::where('accountId', $userId)->where('accountType',
            IconLib::ACCOUNT_TYPE_PERSONAL)->where('name', $name)->where('id', '!=', $id)->where('status',
            IconLib::STATUS_NORMAL)->first();

        if (!is_null($another)) {
            return Output::error(trans('common.icon_lib_with_same_name_exists'), 120203);
        }

        $lib->name = $name;
        $lib->updatedAt = date('Y-m-d H:i:s');

        if (!$lib->save()) {
            return Output::error(trans('common.server_is_busy'), 120204, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $lib = IconLib::getIconLib($id, $userId);

        IconLib::filter($lib);

        return Output::ok($lib);
    }
}
