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
     * 获取用户全部文件
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
     * 获取用户全部文件
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createFile(Request $request)
    {
        $name = trim($request->input('name', 'untitled'));
        $public = trim($request->input('public', '0'));

        $nameLen = mb_strlen($name, 'UTF-8');

        if ($nameLen <= 0 || $name > 100) {
            return Output::error(trans('common.invalid_file_name'), 30000, [], Response::HTTP_BAD_REQUEST);
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

        $file = [
            'id'        => $file->id,
            'name'      => $file->name,
            'userId'    => $file->userId,
            'teamId'    => $file->teamId,
            'access'    => $file->access == 1 ? 'PUBLIC' : 'PRIVATE',
            'layers'    => [],
            'createdAt' => strtotime($file->createdAt),
            'updatedAt' => is_null($file->updatedAt) ? null : strtotime($file->updatedAt)
        ];

        return Output::ok($file);
    }
}
