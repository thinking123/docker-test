<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;

class Controller extends BaseController
{
    /**
     * Controller constructor.
     *
     */
    public function __construct()
    {
        app('translator')->setLocale('en');
    }

    /**
     * Response client with data
     *
     * @param array $data
     * @param int $status
     * @param array $headers
     * @param int $options
     * @return \Illuminate\Http\JsonResponse
     */
    protected static function ok(array $data, $status = Response::HTTP_OK, array $headers = [], $options = 0)
    {
        $data = [
            'status' => true,
            'data'   => $data
        ];

        return response()->json($data, $status, $headers, $options);
    }

    /**
     * Response client with an error
     *
     * @param string $message
     * @param int $code
     * @param array $data
     * @param int $status
     * @param array $headers
     * @param int $options
     * @return \Illuminate\Http\JsonResponse
     */
    protected static function error(
        $message = '',
        $code = 0,
        array $data = [],
        $status = Response::HTTP_OK,
        array $headers = [],
        $options = 0
    ) {
        $data = [
            'status'  => false,
            'code'    => $code,
            'message' => $message,
            'data'    => $data
        ];

        return response()->json($data, $status, $headers, $options);
    }
}
