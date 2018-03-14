<?php

namespace App\Models;

class TeamUser extends Base
{
    const STATUS_DELETED = '0';
    const STATUS_NORMAL = '1';

    protected $table = 'TeamUser';

    /**
     * 检查成员和组之间的关系
     *
     * @param $teamId
     * @param $userId
     * @return bool
     */
    public static function checkTeamUser($teamId, $userId)
    {
        $row = static::where('userId', $userId)->where('teamId', $teamId)->where('status',
            static::STATUS_NORMAL)->first();

        return !is_null($row);
    }
}