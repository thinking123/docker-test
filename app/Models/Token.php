<?php

namespace App\Models;

use DB;
use Log;

class Token extends Base
{
    const STATUS_DELETED = '0';
    const STATUS_NORMAL = '1';

    protected $table = 'Token';

    /**
     * 生成新的 refresh token
     *
     * @param int $userId
     * @param string $salt
     * @return string
     */
    public static function genRefreshToken($userId, $salt)
    {
        return sha1($userId . uniqid() . $salt);
    }

    /**
     * 根据 refresh token 获取 access token
     *
     * @param $userId
     * @param $salt
     * @param $refreshToken
     * @return Token|null
     */
    public static function genAccessToken($userId, $salt, $refreshToken)
    {
        $accessToken = sha1($userId . uniqid() . $salt . $refreshToken);

        $data = [
            'accessToken'          => $accessToken,
            'accessTokenExpiredAt' => date('Y-m-d H:i:s', time() + config('app.access_token_max_lifetime')),
            'updatedAt'            => date('Y-m-d H:i:s')
        ];

        $affected = static::where('refreshToken', $refreshToken)
            ->where('refreshTokenExpiredAt', '>', date('Y-m-d H:i:s'))
            ->where('status', static::STATUS_NORMAL)
            ->update($data);

        if ($affected <= 0) {
            return;
        }

        return static::where('refreshToken', $refreshToken)->first();
    }

    /**
     * 生成一组 token
     *
     * @param int $userId
     * @param string $salt
     * @return Token|false
     */
    public static function genToken($userId, $salt)
    {
        $token = new static;

        $token->userId = $userId;
        $token->refreshToken = static::genRefreshToken($userId, $salt);
        $token->accessToken = sha1($userId . uniqid() . $salt . $token->refreshToken);
        $token->createdAt = $token->updatedAt = date('Y-m-d H:i:s');
        $token->refreshTokenExpiredAt = date('Y-m-d H:i:s', time() + config('app.refresh_token_max_lifetime'));
        $token->accessTokenExpiredAt = date('Y-m-d H:i:s', time() + config('app.access_token_max_lifetime'));

        return $token->save() ? $token : false;
    }

    /**
     * 根据 access token 获取用户
     *
     * @param string $accessToken
     * @return User|null
     */
    public static function getUserByAccessToken($accessToken)
    {
        $token = static::where('accessToken', $accessToken)->where('accessTokenExpiredAt', '>',
            date('Y-m-d H:i:s'))->where('status', static::STATUS_NORMAL)->first();

        if (!is_null($token)) {
            return User::find($token->userId);
        }
    }
}