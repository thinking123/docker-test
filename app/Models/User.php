<?php

namespace App\Models;

use DB;
use Log;

class User extends Base
{
    protected $table = 'User';

    /**
     * Add or update a google user
     *
     * @param string $googleId
     * @param string $email
     * @param string $name
     * @param string $givenName
     * @param string $familyName
     * @param string $avatar
     * @return User|false
     */
    public static function newOrUpdateGoogleUser($googleId, $email, $name, $givenName, $familyName, $avatar)
    {
        try {
            DB::beginTransaction();

            $user = User::where('googleId', $googleId)->first();

            if (!is_null($user)) {
                $user->avatar = $avatar;
                $user->updatedAt = date('Y-m-d H:i:s');
                $user->save();
            } else {
                $user = new User();

                $user->name = $name;
                $user->givenName = $givenName;
                $user->familyName = $familyName;
                $user->avatar = $avatar;
                $user->email = $email;
                $user->googleId = $googleId;
                $user->salt = sha1(microtime(true));
                $user->createdAt = date('Y-m-d H:i:s');

                if (!$user->save()) {
                    throw new \Exception('insert google user data into table User failed');
                }
            }

            DB::commit();

            return $user;
        } catch (\Exception $e) {
            DB::rollback();

            static::log($e);

            return false;
        }
    }

    /**
     * 获取用户基本资料
     *
     * @param int $userId
     * @return array|null
     */
    public static function getBasicProfile($userId)
    {
        $user = static::where('id', $userId)->first()->toArray();

        if (is_null($user)) {
            return null;
        }

        unset($user['id'], $user['googleId'], $user['salt']);

        $user['createdAt'] = strtotime($user['createdAt']);
        $user['updatedAt'] = strtotime($user['updatedAt']);

        return $user;
    }

    /**
     * 根据电子邮件地址获取用户信息
     *
     * @param string $email
     * @return array|null
     */
    public static function getUserByEmail($email)
    {
        $user = User::where('email', $email)->first();

        return is_null($user) ? null : $user->toArray();
    }
}