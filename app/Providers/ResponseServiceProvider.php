<?php

namespace App\Providers;

use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;

class ResponseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        ResponseFactory::macro('error', function (string $message, int $code) {
            return ResponseFactory::json([
                'status' => false,
                'code' => $code,
                'message' => $message,
                'data' => [],
            ], $code);
        });

        ResponseFactory::macro('error_server', function () {
            return ResponseFactory::json([
                'status' => false,
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Internal Server Error',
                'data' => [],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });

        ResponseFactory::macro('error_unauthenticated', function () {
            return response()->json([
                'status' => false,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Unauthenticated',
                'data' => [],
            ], Response::HTTP_UNAUTHORIZED);
        });

        ResponseFactory::macro('success', function (string $message, int $code, $data = []) {
            return ResponseFactory::json([
                'status' => true,
                'code' => $code,
                'message' => $message,
                'data' => $data,
            ], $code);
        });
    }
}
