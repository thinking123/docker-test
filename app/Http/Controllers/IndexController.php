<?php

namespace App\Http\Controllers;

use Output;
use Redis;
use Validator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\Token;
use SendGrid;
use SendGrid\Mail;
use SendGrid\Email;
use SendGrid\Content;
use Victorybiz\GeoIPLocation\GeoIPLocation;

class IndexController extends Controller
{
    /**
     * Login page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('login');
    }

    /**
     * Login
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
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

        $agent = $request->header('user-agent', '');

        try {
            $token = Token::genToken($user->id, $user->salt, $agent, $request->getClientIp());
        } catch (\Exception $e) {
            static::log($e);
            return Output::error($e->getMessage(), 10103, [], Response::HTTP_INTERNAL_SERVER_ERROR);
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
        $refreshToken = trim($request->input('refresh_token', ''));

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

    /**
     * 删除一个 token
     *
     * @param Request $request
     * @param $accessToken
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAccessToken(Request $request, $accessToken)
    {
        $deleted = Token::deleteToken($request->user()->id, $accessToken);

        if ($deleted < 1) {
            return Output::error(trans('common.operation_failed'));
        }

        return Output::ok();
    }

    /**
     * 发送一个 magic link
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createMagicLink(Request $request)
    {
        $email = trim($request->input('email', ''));

        if ('' === $email) {
            return Output::error(trans('common.param_required', ['param' => 'email']), 10300);
        }

        $data = [
            'email' => $email
        ];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Output::error(trans('common.invalid_email_address', ['param' => $email]), 10301, $data);
        }

        $email = strtolower($email);
        $user = User::getUserByEmail($email);

        if (is_null($user)) {
            return Output::error(trans('common.email_not_registered'), 10302, $data);
        }

        $magicToken = sha1($user['email'] . uniqid() . $user['salt'] . microtime(true));

        $key = 'magicToken:' . $magicToken;

        $magicHash = [
            'email'    => $email,
            'verified' => 0
        ];

        if ('OK' != Redis::hMset($key, $magicHash)) {
            return Output::error(trans('common.server_is_busy'), 10303, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!Redis::expire($key, config('app.magic_max_lifetime'))) {
            Redis::del($key);
            return Output::error(trans('common.server_is_busy'), 10304, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $params = [
            'email' => $email,
            'token' => $magicToken
        ];

        $from = new Email('Bento', "noreply@makebento.com");
        $subject = 'Bento Login Verification';
        $to = new Email($user['name'], $user['email']);
        $content = new Content("text/plain", 'https://app.makebento.com/verification?' . http_build_query($params));
        $mail = new Mail($from, $subject, $to, $content);
        $sg = new SendGrid(config('app.sendgrid_api_key'));
        $response = $sg->client->mail()->send()->post($mail);

        if (202 != $response->statusCode()) {
            return Output::error(trans('common.server_is_busy'), 10305, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $requestToken = sha1($user['email'] . microtime(true) . rand(1000000, 9999999) . uniqid() . $user['salt']);

        if ('OK' != Redis::setEx('requestToken:' . $requestToken, config('app.magic_max_lifetime'), $magicToken)) {
            return Output::error(trans('common.server_is_busy'), 10306, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return Output::ok([
            'token' => $requestToken
        ]);
    }

    /**
     * Magic login
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function magicLogin(Request $request)
    {
        $inputs = $request->only('email', 'token');

        $rules = [
            'email' => 'required|email',
            'token' => 'required'
        ];

        $messages = [
            'email.required' => trans('common.param_required', ['param' => 'email']),
            'email.email'    => trans('common.invalid_email_address', ['param' => 'email']),
            'token.required' => trans('common.param_required', ['param' => 'token']),
        ];

        $validator = Validator::make($inputs, $rules, $messages);

        if ($validator->fails()) {
            return Output::error($validator->errors()->first(), 10400, $inputs, Response::HTTP_BAD_REQUEST);
        }

        $magicToken = Redis::get('requestToken:' . $inputs['token']);

        if (is_null($magicToken)) {
            return Output::error(trans('common.magic_link_expired'), 10401, [], Response::HTTP_BAD_REQUEST);
        }

        $magicHash = Redis::hGetAll('magicToken:' . $magicToken);

        if (!isset($magicHash['email']) || !isset($magicHash['verified'])) {
            return Output::error(trans('common.magic_link_expired'), 10402, [], Response::HTTP_BAD_REQUEST);
        }

        if ($magicHash['email'] !== $inputs['email']) {
            return Output::error(trans('common.magic_link_expired'), 10403, [], Response::HTTP_BAD_REQUEST);
        }

        if ($magicHash['verified'] != 1) {
            return Output::error(trans('common.waiting_for_confirm'), 10404, [], Response::HTTP_BAD_REQUEST);
        }

        Redis::del('requestToken:' . $inputs['token'], 'magicToken:' . $magicToken);

        $user = User::getUserByEmail($magicHash['email']);

        if (is_null($user)) {
            return Output::error(trans('common.server_is_busy'), 10405, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $agent = $request->header('user-agent', '');

        try {
            $token = Token::genToken($user['id'], $user['salt'], $agent, $request->getClientIp());
        } catch (\Exception $e) {
            static::log($e);
            return Output::error($e->getMessage(), 10406, [], Response::HTTP_INTERNAL_SERVER_ERROR);
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
     * 确认一次登录
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function confirmMagicLogin(Request $request)
    {
        $inputs = $request->only('email', 'token');

        $rules = [
            'email' => 'required|email',
            'token' => 'required'
        ];

        $messages = [
            'email.required' => trans('common.param_required', ['param' => 'email']),
            'email.email'    => trans('common.invalid_email_address', ['param' => 'email']),
            'token.required' => trans('common.param_required', ['param' => 'token']),
        ];

        $validator = Validator::make($inputs, $rules, $messages);

        if ($validator->fails()) {
            return Output::error($validator->errors()->first(), 10500, $inputs, Response::HTTP_BAD_REQUEST);
        }

        $key = 'magicToken:' . $inputs['token'];

        $magicHash = Redis::hGetAll($key);

        if (!isset($magicHash['email']) || !isset($magicHash['verified'])) {
            return Output::error(trans('common.magic_link_expired'), 10501, [], Response::HTTP_BAD_REQUEST);
        }

        if ($magicHash['email'] !== $inputs['email']) {
            return Output::error(trans('common.magic_link_expired'), 10502, [], Response::HTTP_BAD_REQUEST);
        }

        if ($magicHash['verified'] == 1) {
            return Output::error(trans('common.magic_link_expired'), 10503, [], Response::HTTP_BAD_REQUEST);
        }

        if (false === Redis::hSet($key, 'verified', 1)) {
            return Output::error(trans('common.magic_link_expired'), 10504, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return Output::ok();
    }

    /**
     * 发送一个 quick login link
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createQuickLink(Request $request)
    {
        $email = trim($request->input('email', ''));

        if ('' === $email) {
            return Output::error(trans('common.param_required', ['param' => 'email']), 10600);
        }

        $data = [
            'email' => $email
        ];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Output::error(trans('common.invalid_email_address', ['param' => $email]), 10601, $data);
        }

        $quickLoginToken = sha1($email . uniqid() . microtime(true));

        $key = 'quickLoginToken:' . $quickLoginToken;

        $quickLoginData = [
            'email' => $email
        ];

        if ('OK' != Redis::hMset($key, $quickLoginData)) {
            return Output::error(trans('common.server_is_busy'), 10602, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!Redis::expire($key, 10 * config('app.quick_login_max_lifetime'))) {
            Redis::del($key);
            return Output::error(trans('common.server_is_busy'), 10603, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $params = [
            'email' => $email,
            'token' => $quickLoginToken
        ];

        $username = explode('@', $email)[0];
        $link = 'https://app.makebento.com/verification?' . http_build_query($params);

        $from = new Email('Bento', "noreply@makebento.com");
        $to = new Email($username, $email);
        $subject = 'Bento Login Verification';
        $content = new Content("text/html", ' ');

        $mail = new Mail($from, $subject, $to, $content);

        $geoip = new GeoIPLocation();
        $location = $geoip->getCountry();

        if (!is_null($city = $geoip->getCity())) {
            $location = $city . ', ' . $location;
        }

        /**
         * @see https://github.com/sendgrid/sendgrid-php/blob/master/USE_CASES.md#transactional-templates
         */
        $mail->setTemplateId('19a3677a-1359-41de-859a-5228450c3b29');
        $mail->personalization[0]->addSubstitution("-username-", $username);
        $mail->personalization[0]->addSubstitution("-location-", $location);
        $mail->personalization[0]->addSubstitution("-link-", $link);

        $sg = new SendGrid(config('app.sendgrid_api_key'));

        try {
            $response = $sg->client->mail()->send()->post($mail);
        } catch (\Exception $e) {
            static::log($e);
            return Output::error(trans('common.server_is_busy'), 10604, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (202 != $response->statusCode()) {
            return Output::error(trans('common.server_is_busy'), 10605, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return Output::ok();
    }

    /**
     * quick login
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function quickLogin(Request $request)
    {
        $inputs = $request->only(['email', 'token']);

        $rules = [
            'email' => 'required|email',
            'token' => 'required'
        ];

        $messages = [
            'email.required' => trans('common.param_required', ['param' => 'email']),
            'email.email'    => trans('common.invalid_email_address', ['param' => 'email']),
            'token.required' => trans('common.param_required', ['param' => 'token']),
        ];

        $validator = Validator::make($inputs, $rules, $messages);

        if ($validator->fails()) {
            return Output::error($validator->errors()->first(), 10700, $inputs, Response::HTTP_BAD_REQUEST);
        }

        $key = 'quickLoginToken:' . $inputs['token'];

        try {
            $quickLoginData = Redis::hGetAll($key);
        } catch (\Exception $e) {
            return Output::error(trans('common.server_is_busy'), 10701, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!isset($quickLoginData['email']) || $quickLoginData['email'] !== $inputs['email']) {
            return Output::error(trans('common.magic_link_expired'), 10702, [], Response::HTTP_BAD_REQUEST);
        }

        if (Redis::del($key) != 1) {
            return Output::error(trans('common.server_is_busy'), 10703, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $username = explode('@', $quickLoginData['email'])[0];

        $user = User::newOrUpdateGoogleUser(null, $quickLoginData['email'], $username, null, null, null);

        if (false === $user) {
            return Output::error(trans('common.server_is_busy'), 10704, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $agent = $request->header('user-agent', '');

        try {
            $token = Token::genToken($user->id, $user->salt, $agent, $request->getClientIp());
        } catch (\Exception $e) {
            static::log($e);
            return Output::error($e->getMessage(), 10705, [], Response::HTTP_INTERNAL_SERVER_ERROR);
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
     * Google fonts
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFontList(Request $request)
    {
        $key = 'google_fonts:all2';

        $fonts = Redis::get($key);

        if (!is_null($fonts)) {
            $fonts = json_decode($fonts, true);
            return Output::ok($fonts);
        }

        $url = 'https://www.googleapis.com/webfonts/v1/webfonts?sort=alpha&key=' . config('app.google_fonts_api_key');

        try {
            $fonts = @file_get_contents($url);
            $fonts = @json_decode($fonts, true);

            if (!is_array($fonts) || empty($fonts)) {
                throw new \Exception(trans('common.server_is_busy'));
            }

            Redis::set($key, json_encode($fonts));
        } catch (\Exception $e) {
            static::log($e);
            return Output::error(trans('common.server_is_busy'), 10800, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return Output::ok($fonts);
    }
}
