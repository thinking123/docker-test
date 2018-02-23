<?php

namespace App\Http\Controllers;

use App\Models\ContentToken;
use Output;
use Log;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\File;

class ContentTokenController extends Controller
{
    /**
     * 创建 Content Token
     *
     * @param Request $request
     * @param int $id file id
     * @return \Illuminate\Http\JsonResponse
     */
    public function createToken(Request $request, $id)
    {
        $file = File::where('id', $id)->where('status', File::STATUS_NORMAL)->first();

        if (is_null($file)) {
            return Output::error(trans('common.file_not_found'), 80000, [], Response::HTTP_BAD_REQUEST);
        }

        if ($file->userId != $request->user()->id) {
            return Output::error(trans('common.illegal_operation'), 80001, [], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($request->input('name', 'Untitled'));
        $value = $request->input('value', '');

        if ($name == '') {
            return Output::error(trans('common.invalid_content_token_name'), 80002, [], Response::HTTP_BAD_REQUEST);
        }

        $ct = new ContentToken();
        $ct->name = $name;
        $ct->value = $value;
        $ct->fileId = $id;
        $ct->userId = $request->user()->id;
        $ct->status = ContentToken::STATUS_NORMAL;
        $ct->createdAt = date('Y-m-d H:i:s');

        if (!$ct->save()) {
            return Output::error(trans('common.server_is_busy'), 80003, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $ct = ContentToken::with('creator')->where('status', ContentToken::STATUS_NORMAL)->find($ct->id);

        if (is_null($ct)) {
            return Output::error(trans('common.server_is_busy'), 80004, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $ct = $ct->toArray();

        ContentToken::filter($ct);

        return Output::ok($ct);
    }

    /**
     * 获取文件 Content Token 列表
     *
     * @param Request $request
     * @param int $id file id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getContentTokens(Request $request, $id)
    {
        $file = File::where('id', $id)->where('status', File::STATUS_NORMAL)->first();

        if (is_null($file)) {
            return Output::error(trans('common.file_not_found'), 80100, [], Response::HTTP_BAD_REQUEST);
        }

        if ($file->userId != $request->user()->id) {
            return Output::error(trans('common.illegal_operation'), 80101, [], Response::HTTP_BAD_REQUEST);
        }

        $offset = (int)$request->input('offset', 0);
        $limit = (int)$request->input('limit', ContentToken::DEFAULT_PAGE_SIZE);

        $offset = $offset < 0 ? 0 : $offset;
        $limit = ($offset < 0 || $offset > ContentToken::DEFAULT_PAGE_SIZE) ? ContentToken::DEFAULT_PAGE_SIZE : $limit;

        $contentTokens = ContentToken::getFileTokens($id, $offset, $limit);

        return Output::ok([
            'contentTokens' => $contentTokens
        ]);
    }

    /**
     * 编辑 Content Token
     *
     * @param Request $request
     * @param $id content token id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateToken(Request $request, $id)
    {
        $ct = ContentToken::where('id', $id)->where('status', ContentToken::STATUS_NORMAL)->first();

        if (is_null($ct)) {
            return Output::error(trans('common.content_token_not_found'), 80200, [], Response::HTTP_BAD_REQUEST);
        }

        if ($ct->userId != $request->user()->id) {
            return Output::error(trans('common.illegal_operation'), 80201, [], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($request->input('name', 'Untitled'));
        $value = $request->input('value', '');

        if ($name == '') {
            return Output::error(trans('common.invalid_content_token_name'), 80202, [], Response::HTTP_BAD_REQUEST);
        }

        $ct->name = $name;
        $ct->value = $value;
        $ct->updatedAt = date('Y-m-d H:i:s');

        if (!$ct->save()) {
            return Output::error(trans('common.server_is_busy'), 80203, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $ct = ContentToken::with('creator')->where('status', ContentToken::STATUS_NORMAL)->find($ct->id);

        if (is_null($ct)) {
            return Output::error(trans('common.server_is_busy'), 80204, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $ct = $ct->toArray();

        ContentToken::filter($ct);

        return Output::ok($ct);
    }

    /**
     * 删除 Content Token
     *
     * @param Request $request
     * @param $id content token id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteToken(Request $request, $id)
    {
        $ct = ContentToken::where('id', $id)->where('status', ContentToken::STATUS_NORMAL)->first();

        if (is_null($ct)) {
            return Output::error(trans('common.content_token_not_found'), 80300, [], Response::HTTP_BAD_REQUEST);
        }

        if ($ct->userId != $request->user()->id) {
            return Output::error(trans('common.illegal_operation'), 80301, [], Response::HTTP_BAD_REQUEST);
        }

        $data = [
            'status'    => ContentToken::STATUS_DELETED,
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        try {
            $affected = ContentToken::where('id', $id)
                ->where('userId', $request->user()->id)
                ->where('status', ContentToken::STATUS_NORMAL)->update($data);
        } catch (\Exception $e) {
            static::log($e);
            return Output::error(trans('common.server_is_busy'), 80302, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($affected > 0) {
            return Output::ok();
        }

        return Output::error(trans('common.operation_failed'), 80303);
    }
}
