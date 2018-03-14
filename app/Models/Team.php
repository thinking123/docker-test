<?php

namespace App\Models;

use DB;
use Log;

class Team extends Base
{
    const STATUS_DELETED = '0';
    const STATUS_NORMAL = '1';

    protected $table = 'Team';

    public function owner()
    {
        return $this->hasOne('App\Models\User', 'id', 'ownerId');
    }

    public function creator()
    {
        return $this->hasOne('App\Models\User', 'id', 'createdBy');
    }

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
            $team->status = static::STATUS_NORMAL;
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

    /**
     * 获取 Team 相关详情
     *
     * @param int $id
     * @return array|null
     */
    public static function getTeam($id)
    {
        $team = static::with('owner')->with('creator')->where('status', static::STATUS_NORMAL)->find($id);

        if (is_null($team)) {
            return;
        }

        return $team->toArray();
    }

    /**
     * 格式化
     *
     * @param array $team
     */
    public static function filter(& $team)
    {
        $owner = !isset($team['owner']) || empty($team['owner']) ? null : [
            'id'     => $team['owner']['id'],
            'name'   => $team['owner']['name'],
            'avatar' => $team['owner']['avatar'],
            'email'  => $team['owner']['email'],
        ];

        $creator = !isset($team['creator']) || empty($team['creator']) ? null : [
            'id'     => $team['creator']['id'],
            'name'   => $team['creator']['name'],
            'avatar' => $team['creator']['avatar'],
            'email'  => $team['creator']['email'],
        ];

        $team = [
            'id'        => $team['id'],
            'name'      => $team['name'],
            'owner'     => $owner,
            'creator'   => $creator,
            'createdAt' => strtotime($team['createdAt']),
            'updatedAt' => is_null($team['updatedAt']) ? null : strtotime($team['updatedAt'])
        ];
    }

    /**
     * 格式化数组
     *
     * @param array $teams
     */
    public static function filterTokens(& $teams)
    {
        foreach ($teams as &$team) {
            static::filter($team);
        }
    }
}