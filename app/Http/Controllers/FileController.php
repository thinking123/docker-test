<?php

namespace App\Http\Controllers;

use Output;
use Log;
use Illuminate\Http\Request;
use App\Models\File;
use Symfony\Component\HttpFoundation\Response;

class FileController extends Controller
{
    /**
     * 获取用户文件列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFiles(Request $request)
    {
        $offset = (int)$request->input('offset', 0);
        $limit = (int)$request->input('limit', File::DEFAULT_LIST_COUNT);

        $offset = $offset < 0 ? 0 : $offset;
        $limit = ($offset < 0 || $offset > File::DEFAULT_LIST_COUNT) ? File::DEFAULT_LIST_COUNT : $limit;

        $files = File::getUserFiles($request->user()->id, $offset, $limit);

        return Output::ok([
            'files' => $files
        ]);
    }

    /**
     * 新建文件
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createFile(Request $request)
    {
        $name = trim($request->input('name', 'untitled'));
        $public = $request->input('public', 'false');

        $nameLen = mb_strlen($name, 'UTF-8');

        if ($nameLen <= 0 || $name > 100) {
            return Output::error(trans('common.invalid_file_name'), 30000, [], Response::HTTP_BAD_REQUEST);
        }

        if (!is_bool($public) && !is_numeric($public)) {
            $public = strtolower($public) === 'true';
        }

        $public = intval(boolval($public));

        try {
            $file = File::createFile($name, $request->user()->id, null, $public);
        } catch (\Exception $e) {
            static::log($e);
            return Output::error(trans('common.server_is_busy'), 30001, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (is_null($file)) {
            Log::info('create file error', [
                'user' => [
                    'userId' => $request->user()->id
                ],
                'file' => [
                    'name'   => $name,
                    'public' => $public
                ]
            ]);

            return Output::error(trans('common.server_is_busy'), 30002, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $file = File::with('owner')->find($file->id);

        if (is_null($file)) {
            return Output::error(trans('common.server_is_busy'), 30003, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $file = [
            'id'        => $file->id,
            'name'      => $file->name,
            'teamId'    => $file->teamId,
            'access'    => $file->access == 1 ? 'PUBLIC' : 'PRIVATE',
            'editable'  => $file->userId == $request->user()->id,
            'deletable' => $file->userId == $request->user()->id,
            'createdAt' => strtotime($file->createdAt),
            'updatedAt' => is_null($file->updatedAt) ? null : strtotime($file->updatedAt),
            'owner'     => [
                'id'     => $file->owner->id,
                'name'   => $file->owner->name,
                'avatar' => $file->owner->avatar,
                'email'  => $file->owner->email,
            ]
        ];

        return Output::ok($file);
    }

    /**
     * 更新文件
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateFile(Request $request, $id)
    {
        $file = File::where('id', $id)->where('status',
            File::STATUS_NORMAL)->first();

        if (is_null($file)) {
            return Output::error(trans('common.file_not_found'), 30100, [], Response::HTTP_BAD_REQUEST);
        }

        if ($file->userId != $request->user()->id) {
            return Output::error(trans('common.illegal_operation'), 30101, [], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($request->input('name', ''));
        $public = $request->input('public', '');

        if ($name == '' && $public == '') {
            return Output::error(trans('common.bad_request'), 30102, [], Response::HTTP_BAD_REQUEST);
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
            $affected = File::where('id', $id)
                ->where('userId', $request->user()->id)
                ->where('status', File::STATUS_NORMAL)
                ->update($data);
        } catch (\Exception $e) {
            static::log($e);
            return Output::error(trans('common.server_is_busy'), 30103, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($affected > 0) {

            return Output::ok();
        }

        return Output::error(trans('common.operation_failed'), 30104);
    }

    /**
     * 获取一个文件
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFile(Request $request, $id)
    {
        $file = File::with('owner')->where('id', $id)->where('status', File::STATUS_NORMAL)->first();

        if (is_null($file)) {
            return Output::error(trans('common.file_not_found'), 30200, [], Response::HTTP_BAD_REQUEST);
        }

        if ($file->userId !== $request->user()->id && $file->access != File::ACCESS_PUBLIC) {
            return Output::error(trans('common.file_not_found'), 30201, [], Response::HTTP_BAD_REQUEST);
        }

        $file = [
            'id'        => $file->id,
            'name'      => $file->name,
            'teamId'    => $file->teamId,
            'access'    => $file->access == 1 ? 'PUBLIC' : 'PRIVATE',
            'editable'  => $file->userId == $request->user()->id,
            'deletable' => $file->userId == $request->user()->id,
            'createdAt' => strtotime($file->createdAt),
            'updatedAt' => is_null($file->updatedAt) ? null : strtotime($file->updatedAt),
            'owner'     => [
                'id'     => $file->owner->id,
                'name'   => $file->owner->name,
                'avatar' => $file->owner->avatar,
                'email'  => $file->owner->email,
            ]
        ];

        return Output::ok($file);
    }

    /**
     * 删除一个文件
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteFile(Request $request, $id)
    {
        $file = File::where('id', $id)->where('status', File::STATUS_NORMAL)->first();

        if (is_null($file)) {
            return Output::error(trans('common.file_not_found'), 30300, [], Response::HTTP_BAD_REQUEST);
        }

        if ($file->userId !== $request->user()->id) {
            return Output::error(trans('common.illegal_operation'), 30301, [], Response::HTTP_BAD_REQUEST);
        }

        $data = [
            'status'    => File::STATUS_DELETED,
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        try {
            $affected = File::where('id', $id)
                ->where('userId', $request->user()->id)
                ->where('status', File::STATUS_NORMAL)->update($data);
        } catch (\Exception $e) {
            static::log($e);
            return Output::error(trans('common.server_is_busy'), 30302, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($affected > 0) {
            return Output::ok();
        }

        return Output::error(trans('common.operation_failed'), 30303);
    }
}
