<?php

namespace App\Exceptions;

use Exception;
use Output;
use App\Services\Utils\Log AS LogUtil;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    use LogUtil;

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception $e
     * @throws \Exception
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if (env('APP_DEBUG', false)) {
            return parent::render($request, $e);
        }

        if ($this->shouldReport($e) && ($e instanceof Exception)) {
            $this->alert($e);
            static::log($e);
        }

        return Output::error(trans('common.server_is_busy'), -1001, [], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Report a warning
     *
     * @param Exception $e
     * @return null
     */
    public function alert(Exception $e)
    {

    }
}
