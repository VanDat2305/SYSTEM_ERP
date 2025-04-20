<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class CustomSanctumAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token || !PersonalAccessToken::findToken($token)) {
            return response()->json([
                'message' => __('messages.auth.unauthenticated'),
            ], 401);
        }

        return $next($request);
    }
}
