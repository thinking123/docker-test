<?php

namespace App\Http\Controllers;

use App\Models\DesignToken;
use Output;
use Log;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\File;

class DesignTokenController extends Controller
{
    /**
     * 创建 Design Token
     *
     * @param Request $request
     * @param int $id file id
     * @return \Illuminate\Http\JsonResponse
     */
    public function createDesignToken(Request $request, $id)
    {
        $file = File::where('id', $id)->where('status', File::STATUS_NORMAL)->first();

        if (is_null($file)) {
            return Output::error(trans('common.file_not_found'), 70000, [], Response::HTTP_BAD_REQUEST);
        }

        if ($file->userId != $request->user()->id) {
            return Output::error(trans('common.illegal_operation'), 70001, [], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($request->input('name', 'Untitled'));
        $value = $request->input('value', '');

        if ($name == '') {
            return Output::error(trans('common.invalid_design_token_name'), 70002, [], Response::HTTP_BAD_REQUEST);
        }

        $dt = new DesignToken();
        $dt->name = $name;
        $dt->value = $value;
        $dt->fileId = $id;
        $dt->userId = $request->user()->id;
        $dt->status = DesignToken::STATUS_NORMAL;
        $dt->createdAt = date('Y-m-d H:i:s');

        if (!$dt->save()) {
            return Output::error(trans('common.server_is_busy'), 70003, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $dt = DesignToken::with('creator')->where('status', DesignToken::STATUS_NORMAL)->find($dt->id);

        if (is_null($dt)) {
            return Output::error(trans('common.server_is_busy'), 70004, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $dt = $dt->toArray();

        DesignToken::filter($dt);

        return Output::ok($dt);
    }

    /**
     * 获取文件 Design Token 列表
     *
     * @param Request $request
     * @param int $id file id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDesignTokens(Request $request, $id)
    {
        $file = File::where('id', $id)->where('status', File::STATUS_NORMAL)->first();

        if (is_null($file)) {
            return Output::error(trans('common.file_not_found'), 70100, [], Response::HTTP_BAD_REQUEST);
        }

        if ($file->userId != $request->user()->id) {
            return Output::error(trans('common.illegal_operation'), 70101, [], Response::HTTP_BAD_REQUEST);
        }

        $offset = (int)$request->input('offset', 0);
        $limit = (int)$request->input('limit', DesignToken::DEFAULT_PAGE_SIZE);

        $offset = $offset < 0 ? 0 : $offset;
        $limit = ($offset < 0 || $offset > DesignToken::DEFAULT_PAGE_SIZE) ? DesignToken::DEFAULT_PAGE_SIZE : $limit;

        $designTokens = DesignToken::getFileDesignTokens($id, $offset, $limit);

        return Output::ok([
            'designTokens' => $designTokens
        ]);
    }

    /**
     * 编辑 Design Token
     *
     * @param Request $request
     * @param $id design token id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDesignToken(Request $request, $id)
    {
        $dt = DesignToken::where('id', $id)->where('status', DesignToken::STATUS_NORMAL)->first();

        if (is_null($dt)) {
            return Output::error(trans('common.design_token_not_found'), 70200, [], Response::HTTP_BAD_REQUEST);
        }

        if ($dt->userId != $request->user()->id) {
            return Output::error(trans('common.illegal_operation'), 70201, [], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($request->input('name', 'Untitled'));
        $value = $request->input('value', '');

        if ($name == '') {
            return Output::error(trans('common.invalid_design_token_name'), 70202, [], Response::HTTP_BAD_REQUEST);
        }

        $dt->name = $name;
        $dt->value = $value;
        $dt->updatedAt = date('Y-m-d H:i:s');

        if (!$dt->save()) {
            return Output::error(trans('common.server_is_busy'), 70203, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $dt = DesignToken::with('creator')->where('status', DesignToken::STATUS_NORMAL)->find($dt->id);

        if (is_null($dt)) {
            return Output::error(trans('common.server_is_busy'), 70204, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $dt = $dt->toArray();

        DesignToken::filter($dt);

        return Output::ok($dt);
    }

    /**
     * 删除 Design Token
     *
     * @param Request $request
     * @param $id design token id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDesignToken(Request $request, $id)
    {
        $dt = DesignToken::where('id', $id)->where('status', DesignToken::STATUS_NORMAL)->first();

        if (is_null($dt)) {
            return Output::error(trans('common.design_token_not_found'), 70300, [], Response::HTTP_BAD_REQUEST);
        }

        if ($dt->userId != $request->user()->id) {
            return Output::error(trans('common.illegal_operation'), 70301, [], Response::HTTP_BAD_REQUEST);
        }

        $data = [
            'status'    => DesignToken::STATUS_DELETED,
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        try {
            $affected = DesignToken::where('id', $id)
                ->where('userId', $request->user()->id)
                ->where('status', DesignToken::STATUS_NORMAL)->update($data);
        } catch (\Exception $e) {
            static::log($e);
            return Output::error(trans('common.server_is_busy'), 70302, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($affected > 0) {
            return Output::ok();
        }

        return Output::error(trans('common.operation_failed'), 70303);
    }
}
