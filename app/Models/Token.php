<?php

namespace App\Models;

use DB;
use Log;
use Victorybiz\GeoIPLocation\GeoIPLocation;

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
     * @param string $agent
     * @param string $ip
     * @throws \Exception
     * @return Token
     */
    public static function genToken($userId, $salt, $agent, $ip)
    {
        if (config('app.token_limit') > 0 && static::countUserTokens($userId) >= config('app.token_limit')) {
            throw new \Exception(trans('common.token_limit_reached'));
        }

        $geoip = new GeoIPLocation();
        $geoip->setIP($ip);

        $latitude = $geoip->getLatitude();
        $longitude = $geoip->getLongitude();

        $timezone = null;
        if (!is_null($latitude) && !is_null($longitude)) {
            $timezoneObject = new \GoogleMapsTimeZone($latitude, $longitude, time(), \GoogleMapsTimeZone::FORMAT_JSON);
            $timezoneObject->setApiKey(config('app.google_map_time_zone_api_key'));
            $timezoneData = $timezoneObject->queryTimeZone();

            if (isset($timezoneData['status']) && 'OK' === $timezoneData['status']) {
                $timezone = $timezoneData['timeZoneId'];
            }
        }

        $token = new static;

        $token->userId = $userId;
        $token->agent = trim($agent);
        $token->ip = $ip;
        $token->city = $geoip->getCity();
        $token->country = $geoip->getCountry();
        $token->timezone = $timezone;
        $token->refreshToken = static::genRefreshToken($userId, $salt);
        $token->accessToken = sha1($userId . uniqid() . $salt . $token->refreshToken);
        $token->createdAt = $token->updatedAt = date('Y-m-d H:i:s');
        $token->refreshTokenExpiredAt = date('Y-m-d H:i:s', time() + config('app.refresh_token_max_lifetime'));
        $token->accessTokenExpiredAt = date('Y-m-d H:i:s', time() + config('app.access_token_max_lifetime'));

        $saved = $token->save();

        if (!$saved) {
            throw new \Exception(trans('common.server_is_busy'));
        }

        return $token;
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

    /**
     * 获取用户已有 token 数量
     *
     * @param int $userId
     * @return int
     */
    public static function countUserTokens($userId)
    {
        $count = static::where('userId', $userId)
            ->where('refreshTokenExpiredAt', '>', date('Y-m-d H:i:s'))
            ->where('status', static::STATUS_NORMAL)
            ->count();

        return $count;
    }

    /**
     * 获取用户全部活动的 token
     *
     * @param int $userId
     * @return array
     */
    public static function getUserTokens($userId)
    {
        $tokens = static::where('userId', $userId)
            ->where('refreshTokenExpiredAt', '>', date('Y-m-d H:i:s'))
            ->where('status', static::STATUS_NORMAL)
            ->get();

        return $tokens;
    }

    /**
     * 删除一个 token
     *
     * @param int $userId
     * @param int $accessToken
     * @return mixed
     */
    public static function deleteToken($userId, $accessToken)
    {
        $data = [
            'status'    => static::STATUS_DELETED,
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        $affected = static::where('userId', $userId)
            ->where('accessToken', $accessToken)
            ->where('accessTokenExpiredAt', '>', date('Y-m-d H:i:s'))
            ->where('status', static::STATUS_NORMAL)
            ->update($data);

        return $affected;
    }
}