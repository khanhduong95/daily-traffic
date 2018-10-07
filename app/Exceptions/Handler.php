<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Handler extends ExceptionHandler
{
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
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        $cause = $e->getPrevious();
        if ($cause != null) {
            $e = $cause;
        }
        
        $status = null;
        $message = null;
        if ($e instanceof HttpResponseException) {
            $status = $e->getResponse()->getStatusCode();
        } elseif ($e instanceof ModelNotFoundException) {
            $e = new NotFoundHttpException($e->getMessage(), $e);
        } elseif ($e instanceof AuthorizationException) {
            $e = new HttpException(403, $e->getMessage());
        } elseif ($e instanceof AuthenticationException) {
            $e = new UnauthorizedHttpException('Bearer realm="Please enter your valid API token."', $e->getMessage());
        } elseif ($e instanceof ValidationException && $e->getResponse()) {
            $status = $e->getResponse()->getStatusCode();
            $message = $e->validator->errors()->first();
        }

        $fe = FlattenException::create($e);

        return response()->json([
            'code' => $e->getCode(),
            'message' => $message ? $message : $e->getMessage(),
            'type' => get_class($e),
        ], $status ? $status : $fe->getStatusCode(), $fe->getHeaders());

        // return parent::render($request, $e);
    }
}
