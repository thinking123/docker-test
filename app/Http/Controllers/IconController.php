<?php

namespace App\Http\Controllers;

use App\Models\Icon;
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
     * @return \Illuminate\Http\JsonResponse
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

    /**
     * 删除 icon lib
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteIconLib(Request $request, $id)
    {
        $userId = $request->user()->id;

        $lib = IconLib::getIconLib($id, $userId);

        if (is_null($lib) || $lib['status'] != IconLib::STATUS_NORMAL || $lib['accountId'] != $userId || $lib['accountType'] != IconLib::ACCOUNT_TYPE_PERSONAL) {
            return Output::error(trans('common.icon_lib_not_found'), 120300, [], Response::HTTP_NOT_FOUND);
        }

        if (isset($lib['icons']) && !empty($lib['icons'])) {
            return Output::error(trans('common.icons_exist_in_lib'), 120301);
        }

        $data = [
            'status'    => IconLib::STATUS_DELETED,
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        $result = IconLib::where('id', $id)->where('accountId', $userId)->where('accountType',
            IconLib::ACCOUNT_TYPE_PERSONAL)->where('status', IconLib::STATUS_NORMAL)->update($data);

        if ($result) {
            return Output::ok();
        }

        return Output::error(trans('common.server_is_busy'), 120302, [], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 创建新 icon
     *
     * @param Request $request
     * @param int $libId
     * @return \Illuminate\Http\JsonResponse
     */
    public function createIcon(Request $request, $libId)
    {
        $name = $request->input('name', '');
        $tags = $request->input('tags', '');
        $path = $request->input('path', '');

        if ('' === $name) {
            return Output::error(trans('common.invalid_icon_lib_name'), 120400);
        }

        $tags = @json_decode($tags, true);
        $tags = is_null($tags) ? [] : $tags;
        $tags = json_encode($tags);

        $pieces = parse_url($path);
        if (!isset($pieces['host']) || !in_array($pieces['host'], Icon::getAllowedHost())) {
            return Output::error(trans('common.invalid_icon_lib_path'), 120401);
        }

        $userId = $request->user()->id;

        $lib = IconLib::getIconLib($libId, $userId);

        if (is_null($lib) || $lib['status'] != IconLib::STATUS_NORMAL || $lib['accountId'] != $userId || $lib['accountType'] != IconLib::ACCOUNT_TYPE_PERSONAL) {
            return Output::error(trans('common.icon_lib_not_found'), 120402, [], Response::HTTP_NOT_FOUND);
        }

        $another = Icon::where('iconLibId', $libId)->where('name', $name)->where('status',
            Icon::STATUS_NORMAL)->first();

        if (!is_null($another)) {
            return Output::error(trans('common.icon_with_same_name_exists'), 120403);
        }

        $icon = new Icon();

        $icon->name = $name;
        $icon->tags = $tags;
        $icon->path = $path;
        $icon->iconLibId = $libId;
        $icon->status = Icon::STATUS_NORMAL;
        $icon->createdBy = $userId;
        $icon->createdAt = $icon->updatedAt = date('Y-m-d H:i:s');

        if ($icon->save()) {
            return Output::ok();
        }

        return Output::error(trans('common.server_is_busy'), 120404, [], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 更新 icon
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateIcon(Request $request, $id)
    {
        $name = $request->input('name', '');
        $tags = $request->input('tags', '');
        $path = $request->input('path', '');

        if ('' === $name) {
            return Output::error(trans('common.invalid_icon_lib_name'), 120500);
        }

        $tags = @json_decode($tags, true);
        $tags = is_null($tags) ? [] : $tags;
        $tags = json_encode($tags);

        $pieces = parse_url($path);
        if (!isset($pieces['host']) || !in_array($pieces['host'], Icon::getAllowedHost())) {
            return Output::error(trans('common.invalid_icon_lib_path'), 120501);
        }

        $icon = Icon::where('id', $id)->where('status', Icon::STATUS_NORMAL)->with('iconLib')->first();

        if (is_null($icon) || is_null($icon->iconLib)) {
            return Output::error(trans('common.icon_not_found'), 120502, [], Response::HTTP_NOT_FOUND);
        }

        $lib = $icon->iconLib;

        $userId = $request->user()->id;

        if ($lib->accountId != $userId || $lib->accountType != IconLib::ACCOUNT_TYPE_PERSONAL) {
            return Output::error(trans('common.icon_not_found'), 120503, [], Response::HTTP_NOT_FOUND);
        }

        $another = Icon::where('iconLibId', $lib->id)->where('id', '!=', $id)->where('name', $name)->where('status',
            Icon::STATUS_NORMAL)->first();

        if (!is_null($another)) {
            return Output::error(trans('common.icon_with_same_name_exists'), 120504);
        }

        $icon->name = $name;
        $icon->tags = $tags;
        $icon->path = $path;
        $icon->updatedAt = date('Y-m-d H:i:s');

        if ($icon->save()) {
            return Output::ok();
        }

        return Output::error(trans('common.server_is_busy'), 120505, [], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
