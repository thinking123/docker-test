<?php

namespace App\Models;

use DB;
use Log;

class Team extends Base
{
    protected $table = 'Team';

    /**
     * 创建一个组
     *
     * @param string $name
     * @param int $createdBy
     * @return Team|null
     */
    public static function createTeam($name, $createdBy)
    {

        try {
            DB::beginTransaction();

            $team = new static;

            $team->name = $name;
            $team->ownerId = $createdBy;
            $team->createdBy = $createdBy;
            $team->createdAt = date('Y-m-d H:i:s');

            if (!$team->save()) {
                throw new \Exception('create team error');
            }

            $teamUser = new TeamUser;

            $teamUser->userId = $createdBy;
            $teamUser->teamId = $team->id;
            $teamUser->status = TeamUser::STATUS_NORMAL;
            $teamUser->createdAt = $team->createdAt;

            if (!$teamUser->save()) {
                throw new \Exception('create teamUser error');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            static::log($e);

            return null;
        }

        return $team;
    }
}