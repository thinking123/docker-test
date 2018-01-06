<?php

namespace App\Http\Controllers;

use Output;
use Log;
use Redis;
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
        $key = 'job:' . $id;

        $status = Redis::get($key);

        if (is_null($status)) {
            $status = 'UNKNOWN';
        }

        $data = [
            'job'    => $id,
            'status' => $status
        ];

        return Output::ok($data);
    }
}
