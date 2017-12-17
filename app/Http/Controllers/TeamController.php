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

        $team = [
            'id'        => $team->id,
            'name'      => $team->name,
            'ownerId'   => $team->ownerId,
            'createdBy' => $team->createdBy,
            'createdAt' => strtotime($team->createdAt),
            'updatedAt' => is_null($team->updatedAt) ? null : strtotime($team->updatedAt)
        ];

        return Output::ok($team);
    }
}
