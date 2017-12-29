<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Output;
use Log;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * 创建一个组
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTeam(Request $request)
    {
        $name = trim($request->input('name', ''));

        if ('' === $name) {
            return Output::error(trans('common.invalid_team_name'), 40000, [], Response::HTTP_BAD_REQUEST);
        }

        try {
            $team = Team::createTeam($name, $request->user()->id);
        } catch (\Exception $e) {
            static::log($e);
            return Output::error(trans('common.server_is_busy'), 40001, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (is_null($team)) {
            Log::info('create team error', [
                'user' => [
                    'userId' => $request->user()->id
                ],
                'team' => [
                    'name' => $name
                ]
            ]);

            return Output::error(trans('common.server_is_busy'), 40002, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $team = Team::getTeam($team->id);

        if (is_null($team)) {
            return Output::error(trans('common.server_is_busy'), 40003, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

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

        return Output::ok($team);
    }
}
