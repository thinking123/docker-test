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
        $user = User::where('id', $request->user()->id)->first()->toArray();

        unset($user['id'], $user['googleId'], $user['salt']);

        $user['createdAt'] = strtotime($user['createdAt']);
        $user['updatedAt'] = strtotime($user['updatedAt']);

        return Output::ok($user);
    }
}
