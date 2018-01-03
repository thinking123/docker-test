<?php

namespace App\Http\Controllers;

use Output;
use Log;
use Illuminate\Http\Request;

class JobController extends Controller
{
    /**
     * 查询任务完成情况
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJob(Request $request, $id)
    {

        $data = [
            'job'    => $id,
            'status' => 'DONE'
        ];

        return Output::ok($data);
    }
}
