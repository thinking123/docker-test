<?php

namespace App\Http\Controllers;

use Output;
use Illuminate\Http\Request;
use App\Models\File;

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
}
