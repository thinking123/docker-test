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
     * @return bool
     */
    public static function newOrUpdateGoogleUser($googleId, $email, $name, $givenName, $familyName, $avatar)
    {
        try {
            DB::beginTransaction();

            $user = User::where('googleId', $googleId)->first();

            if (!is_null($user)) {
                $user->avatar = $avatar;
                $user->updated = date('Y-m-d H:i:s');
                $user->save();
            } else {
                $user = new User();
                $user->name = $name;
                $user->givenName = $givenName;
                $user->familyName = $familyName;
                $user->avatar = $avatar;
                $user->email = $email;
                $user->googleId = $googleId;
                $user->createdAt = date('Y-m-d H:i:s');
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ' in file ' . $e->getFile() . ' on line ' . $e->getLine());

            DB::rollback();

            return false;
        }
    }
}