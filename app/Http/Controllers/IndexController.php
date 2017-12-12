<?php

namespace App\Http\Controllers;

use Output;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\Token;

class IndexController extends Controller
{
    /**
     * Login page
     *
     * @return \Illuminate\View\View
     */
    public function login()
    {
        return view('login');
    }

    /**
     * Login
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function doLogin(Request $request)
    {
        $source = trim($request->input('source', ''));

        if (empty($source)) {
            return Output::error(trans('common.invalid_parameter_value', [
                'param' => 'source'
            ]), 10000, [
                'source' => $source
            ], Response::HTTP_BAD_REQUEST);
        }

        $method = $source . 'Login';

        if (!method_exists($this, $method)) {
            return Output::error(trans('common.invalid_parameter_value', [
                'param' => 'source'
            ]), 10001, [
                'source' => $source
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->$method($request);
    }

    /**
     * Login with google account
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function googleLogin(Request $request)
    {
        $idToken = trim($request->input('id_token', ''));

        if (empty($idToken)) {
            return Output::error(trans('common.invalid_parameter_value', [
                'param' => 'id_token'
            ]), 10100, [
                'id_token' => $idToken
            ], Response::HTTP_BAD_REQUEST);
        }

        $client = new \Google_Client(['client_id' => config('app.google_client_id')]);
        $payload = $client->verifyIdToken($idToken);

        if (false === $payload) {
            return Output::error(trans('common.invalid_parameter_value', [
                'param' => 'id_token'
            ]), 10101, [
                'id_token' => $idToken
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = User::newOrUpdateGoogleUser($payload['sub'], $payload['email'], $payload['name'],
            $payload['given_name'], $payload['family_name'], $payload['picture']);

        if (false === $user) {
            return Output::error(trans('common.server_is_busy'), 10102, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $token = Token::genToken($user->id, $user->salt);

        if (false === $token) {
            return Output::error(trans('common.server_is_busy'), 10103, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $data = [
            'accessToken'           => $token->accessToken,
            'refreshToken'          => $token->refreshToken,
            'accessTokenExpiredAt'  => strtotime($token->accessTokenExpiredAt),
            'refreshTokenExpiredAt' => strtotime($token->refreshTokenExpiredAt)
        ];

        return Output::ok($data);
    }

    /**
     * refresh access token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshAccessToken(Request $request)
    {
        $refreshToken = $request->input('refresh_token', '');
        $refreshToken = trim($refreshToken);

        if (empty($refreshToken)) {
            return Output::error(trans('common.invalid_parameter_value', [
                'param' => 'refresh_token'
            ]), 10200, [
                'refresh_token' => $refreshToken
            ], Response::HTTP_BAD_REQUEST);
        }

        $token = Token::genAccessToken($request->user()->id, $request->user()->salt, $refreshToken);

        if (is_null($token)) {
            return Output::error(trans('common.operation_failed'), 10201);
        }

        $data = [
            'accessToken'          => $token->accessToken,
            'accessTokenExpiredAt' => strtotime($token->accessTokenExpiredAt)
        ];

        return Output::ok($data);
    }
}
