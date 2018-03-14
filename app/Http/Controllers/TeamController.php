<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Output;
use Log;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * 更新一个组
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function editTeam(Request $request, $id)
    {
        $name = trim($request->input('name', ''));

        if ('' === $name) {
            return Output::error(trans('common.invalid_team_name'), 40100, [], Response::HTTP_BAD_REQUEST);
        }

        $team = Team::getTeam($id);

        if (is_null($team)) {
            return Output::error(trans('common.team_not_found'), 40101, [], Response::HTTP_NOT_FOUND);
        }

        if (!isset($team['owner']['id']) || $team['owner']['id'] != $request->user()->id) {
            return Output::error(trans('common.illegal_operation'), 40102, [], Response::HTTP_BAD_REQUEST);
        }

        $data = [
            'name'      => $name,
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        try {
            $affected = Team::where('id', $id)
                ->where('ownerId', $request->user()->id)
                ->where('status', Team::STATUS_NORMAL)
                ->update($data);
        } catch (\Exception $e) {
            static::log($e);
            return Output::error(trans('common.server_is_busy'), 40103, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($affected > 0) {

            return Output::ok();
        }

        return Output::error(trans('common.operation_failed'), 40104);
    }
}
