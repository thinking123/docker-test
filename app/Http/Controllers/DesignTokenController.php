<?php

namespace App\Http\Controllers;

use App\Models\DesignToken;
use Output;
use Log;
use Illuminate\Http\Request;
use App\Models\File;

class DesignTokenController extends Controller
{
    /**
     * 创建 Design Token
     *
     * @param Request $request
     * @param $id
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
            return Output::error(trans('common.server_is_busy'), 700003, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $dt = DesignToken::with('creator')->find($dt->id);

        if (is_null($dt)) {
            return Output::error(trans('common.server_is_busy'), 700004, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $dt = $dt->toArray();

        DesignToken::filter($dt);

        return Output::ok($dt);
    }
}
