<?php

namespace App\Http\Controllers;

use Output;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Get user's profile info
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfile(Request $request)
    {
        $user = User::getBasicProfile($request->user()->id);

        if (is_null($user)) {
            return Output::error(trans('common.user_not_found'), 20000);
        }

        return Output::ok($user);
    }
}
