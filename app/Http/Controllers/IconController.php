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
}
