<?php

namespace App\Http\Controllers;

use Output;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
     * Login with google account
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function googleLogin(Request $request)
    {
        $idToken = $request->input('id_token', '');

        $client = new \Google_Client(['client_id' => config('app.google_client_id')]);
        $payload = $client->verifyIdToken($idToken);

        if (false === $payload) {
            return Output::error(trans('common.invalid_parameter_value', [
                'param' => 'id_token'
            ]), 1001, [], Response::HTTP_UNAUTHORIZED);
        }

        dd($payload);
    }
}
