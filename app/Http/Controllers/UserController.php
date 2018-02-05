<?php

namespace App\Http\Controllers;

use Output;
use Log;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Token;

class UserController extends Controller
{
    /**
     * Get user's basic profile info
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfile(Request $request)
    {
        $user = User::getBasicProfile($request->user()->id);

        if (is_null($user)) {

            Log::info(trans('common.user_not_found'), [
                'userId' => $request->user()->id
            ]);

            return Output::error(trans('common.user_not_found'), 20000);
        }

        return Output::ok($user);
    }

    /**
     * Get user's online devices
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDevices(Request $request)
    {
        $tokens = Token::getUserTokens($request->user()->id);

        $list = [];

        foreach ($tokens as $token) {
            $list[] = [
                'agent'        => $token->agent,
                'ip'           => $token->ip,
                'city'         => $token->city,
                'country'      => $token->country,
                'timezone'     => $token->timezone,
                'token'        => $token->accessToken,
                'createdAt'    => strtotime($token->createdAt),
                'expiredAt'    => strtotime($token->accessTokenExpiredAt),
                'lastAccessAt' => strtotime($token->updatedAt)
            ];
        }

        foreach ($list as & $item) {
            if (!is_null($item['timezone'])) {
                date_default_timezone_set($token['timezone']);
                $item['lastAccessLocalTime'] = date('Y-m-d H:i:s', $item['lastAccessAt']);
            } else {
                $item['lastAccessLocalTime'] = null;
            }
        }

        return Output::ok([
            'devices' => $list
        ]);
    }
}
