<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $levels = [
    ];

    protected $dontReport = [
        BaseException::class,
        NotFoundException::class,
        ValidationException::class,
        BusinessException::class,
        UnauthorizedException::class,
        ForbiddenException::class,
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
        });

        $this->renderable(function (BaseException $e, $request) {
            return $this->renderBaseException($e);
        });

        $this->renderable(function (LaravelValidationException $e, $request) {
            return $this->renderLaravelValidationException($e);
        });

        $this->renderable(function (ModelNotFoundException $e, $request) {
            return $this->renderModelNotFound($e);
        });

        $this->renderable(function (AuthenticationException $e, $request) {
            return $this->renderAuthenticationException($e);
        });

        $this->renderable(function (AuthorizationException $e, $request) {
            return $this->renderAuthorizationException($e);
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            return $this->renderNotFoundHttp($e);
        });

        $this->renderable(function (MethodNotAllowedHttpException $e, $request) {
            return $this->renderMethodNotAllowed($e);
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            if ($e instanceof HttpExceptionInterface && !$this->isHandledException($e)) {
                return $this->renderHttpException($e);
            }
            if (!$this->isHandledException($e) && !($e instanceof HttpExceptionInterface)) {
                return $this->renderGenericException($e);
            }
        }

        return parent::render($request, $e);
    }

    protected function isHandledException(Throwable $e): bool
    {
        return $e instanceof BaseException
            || $e instanceof LaravelValidationException
            || $e instanceof ModelNotFoundException
            || $e instanceof AuthenticationException
            || $e instanceof AuthorizationException;
    }

    protected function renderBaseException(BaseException $e): \Illuminate\Http\JsonResponse
    {
        return response()->json($e->toArray(), $e->getStatusCode());
    }

    protected function renderLaravelValidationException(LaravelValidationException $e): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'VALIDATION_ERROR',
            'message' => $e->getMessage() ?: 'The given data was invalid.',
            'status_code' => 422,
            'errors' => $e->errors(),
        ], 422);
    }

    protected function renderModelNotFound(ModelNotFoundException $e): \Illuminate\Http\JsonResponse
    {
        $model = class_basename($e->getModel());
        return response()->json([
            'success' => false,
            'error' => 'NOT_FOUND',
            'message' => "{$model} not found",
            'status_code' => 404,
        ], 404);
    }

    protected function renderAuthenticationException(AuthenticationException $e): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'UNAUTHENTICATED',
            'message' => $e->getMessage() ?: 'Unauthenticated.',
            'status_code' => 401,
        ], 401);
    }

    protected function renderAuthorizationException(AuthorizationException $e): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'FORBIDDEN',
            'message' => $e->getMessage() ?: 'This action is unauthorized.',
            'status_code' => 403,
        ], 403);
    }

    protected function renderNotFoundHttp(NotFoundHttpException $e): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'ENDPOINT_NOT_FOUND',
            'message' => $e->getMessage() ?: 'The requested endpoint was not found.',
            'status_code' => 404,
        ], 404);
    }

    protected function renderMethodNotAllowed(MethodNotAllowedHttpException $e): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'METHOD_NOT_ALLOWED',
            'message' => $e->getMessage() ?: 'Method not allowed.',
            'status_code' => 405,
        ], 405);
    }

    protected function renderHttpException(HttpExceptionInterface $e): \Illuminate\Http\JsonResponse
    {
        $statusCode = $e->getStatusCode();
        return response()->json([
            'success' => false,
            'error' => 'HTTP_ERROR',
            'message' => $e->getMessage() ?: 'HTTP error occurred.',
            'status_code' => $statusCode,
        ], $statusCode, $e->getHeaders());
    }

    protected function renderGenericException(Throwable $e): \Illuminate\Http\JsonResponse
    {
        $statusCode = 500;
        $debug = config('app.debug', false);

        $response = [
            'success' => false,
            'error' => 'INTERNAL_ERROR',
            'message' => $debug ? $e->getMessage() : 'An internal error occurred.',
            'status_code' => $statusCode,
        ];

        if ($debug) {
            $response['exception'] = get_class($e);
            $response['file'] = $e->getFile();
            $response['line'] = $e->getLine();
            $response['trace'] = collect($e->getTrace())->take(10)->map(function ($trace) {
                return [
                    'file' => $trace['file'] ?? null,
                    'line' => $trace['line'] ?? null,
                    'function' => $trace['function'] ?? null,
                    'class' => $trace['class'] ?? null,
                ];
            })->toArray();
        }

        return response()->json($response, $statusCode);
    }
}
