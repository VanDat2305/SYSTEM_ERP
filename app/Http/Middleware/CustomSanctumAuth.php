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
    
        // 1. Kiểm tra token tồn tại
        if (!$token) {
            return response()->json([
                'message' => __('messages.auth.unauthenticated'),
            ], 401);
        }
    
        // 2. Tìm token trong database
        $accessToken = PersonalAccessToken::findToken($token);
        if (!$accessToken) {
            return response()->json([
                'message' => __('messages.auth.invalid_token'),
            ], 401);
        }
    
        // 3. Kiểm tra nếu token có scope '2fa:verify' nhưng không phải route two-factor-challenge

        $isTwoFactorVerifyToken = is_array($accessToken->abilities) && in_array('2fa:verify', $accessToken->abilities);
        $isTwoFactorChallengeRoute = $request->is('two-factor-challenge'); // Hoặc điều kiện route cụ thể
        if ($isTwoFactorVerifyToken && !$isTwoFactorChallengeRoute) {
            return response()->json([
                'message' => __('messages.auth.two_factor_token_restricted'),
            ], 403); // HTTP 403 Forbidden
        }
    
        return $next($request);
    }
}
