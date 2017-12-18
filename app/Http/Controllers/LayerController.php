<?php

namespace App\Http\Controllers;

use Output;
use Log;
use Validator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LayerController extends Controller
{
    /**
     * 创建 Layer
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createLayer(Request $request)
    {
        $inputs = $request->only(['name', 'position', 'type', 'render', 'data', 'styles']);

        $rules = [
            'name'     => 'required',
            'position' => 'required|number',
            'type'     => 'required|in:box,div',
            'render'   => 'required|boolean'
        ];

        $messages = [
            'name.required'     => trans('common.param_required', ['param' => 'name']),
            'position.required' => trans('common.param_required', ['param' => 'position']),
            'type.required'     => trans('common.param_required', ['param' => 'type']),
            'type.in'           => trans('common.invalid_parameter_value', ['param' => 'type']),
            'render.required'   => trans('common.param_required', ['param' => 'render']),
        ];

        $validator = Validator::make($inputs, $rules, $messages);

        if ($validator->fails()) {
            return Output::error($validator->errors()->first(), 50000, $inputs, Response::HTTP_BAD_REQUEST);
        }
    }
}
