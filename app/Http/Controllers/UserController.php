<?php

namespace App\Http\Controllers;

use Output;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * refresh access token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfile(Request $request)
    {
        $user = User::where('id', $request->user()->id)->first()->toArray();

        unset($user['googleId'], $user['salt']);

        return Output::ok($user);
    }
}
