<?php

namespace App\Models;

use DB;
use Log;

class DesignToken extends Base
{
    const STATUS_DELETED = '0';
    const STATUS_NORMAL = '1';

    protected $table = 'DesignToken';

    public function creator()
    {
        return $this->hasOne('App\Models\User', 'id', 'userId');
    }

    /**
     * 格式化 Design Token
     *
     * @param DesignToken $dt
     */
    public static function filter(& $dt)
    {
        $dt['createdAt'] = isset($dt['createdAt']) ? strtotime($dt['createdAt']) : null;
        $dt['updatedAt'] = isset($dt['updatedAt']) ? strtotime($dt['updatedAt']) : null;

        if (isset($dt['creator']) && !empty($dt['creator'])) {
            $dt['creator'] = [
                'id'     => $dt['creator']['id'],
                'name'   => $dt['creator']['name'],
                'avatar' => $dt['creator']['avatar'],
                'email'  => $dt['creator']['email'],
            ];
        }

        unset($dt['status'], $dt['userId']);
    }
}