<?php

use App\Http\Middleware\CustomSanctumAuth;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\Modules\Core\Http\Middleware\FormatApiResponse::class);
        $middleware->append(SetLocale::class);      
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class,);      
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Custom cho AccessDeniedHttpException
        $exceptions->renderable(
            fn(Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, Illuminate\Http\Request $request) =>
                response()->json([
                    'status' => false,
                    'message' => __('messages.exceptions.access_denied'),
                    'code' => 403,
                ], 403)
        );

        // Custom cho ValidationException
        $exceptions->renderable(
            fn(Illuminate\Validation\ValidationException $e, Illuminate\Http\Request $request) =>
                response()->json([
                    'status' => false,
                    'message' => __('messages.exceptions.invalid_data'),
                    'errors' => $e->errors(),
                    'code' => 422,
                ], 422)
        );

        // Custom cho ModelNotFoundException
        $exceptions->renderable(
            fn(Illuminate\Database\Eloquent\ModelNotFoundException $e, Illuminate\Http\Request $request) =>
                response()->json([
                    'status' => false,
                    'message' => __('messages.exceptions.data_not_found'),
                    'code' => 404,
                ], 404)
        );
        $exceptions->renderable(
            fn(App\Exceptions\CustomException $e, Illuminate\Http\Request $request) =>
                response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                    'code' => $e->getStatusCode(),
                ], $e->getStatusCode())
        );
    })->create();
