<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Users\Services\AuthService;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'password_confirmation' => 'required|same:password',
        ]);

        $user = $this->authService->register($data);
        
        return response()->json([
            'message' => __('messages.register.success'),
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $authData = $this->authService->login($credentials);

        return response()->json([
            'message' => __('messages.login.success'),
            'token' => $authData['token'] ?? null,
            'access_token' => $authData['access_token'] ?? null,
            'refresh_token' => $authData['refresh_token'] ?? null,
            'user' => $authData['user'],
            'roles' => $authData['roles'],
            'permissions' => $authData['permissions'],
            'menu' => $authData['menu'],
        ]);
    }
    public function logout(Request $request)
    {
        try {
            $this->authService->logout($request->user());
            return response()->json([
                'message' => __('messages.logout.success')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('messages.logout.failed')
            ], 500);
        }
    }
    public function refreshToken(Request $request)
    {
        // Kiểm tra user đã xác thực chưa
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }
        try {
            $user = $request->user();

            // Kiểm tra token có scope refresh không
            if (!$user->currentAccessToken()->can('refresh')) {
                throw new \Exception('Invalid token scope');
            }

            // Revoke token hiện tại
            $user->currentAccessToken()->delete();
            $request->user()->tokens()
                ->where('expires_at', '<', now())
                ->delete();

            // Tạo token mới
            $newAccessToken = $user->createToken(
                'access_token',
                ['access:full'],
                now()->addMinutes(config('sanctum.expiration'))
            )->plainTextToken;

            $newRefreshToken = $user->createToken(
                'refresh_token',
                ['refresh'],
                now()->addDays(30)
            )->plainTextToken;

            return response()->json([
                'token' => $newAccessToken,
                'refresh_token' => $newRefreshToken,
            ]);
        } catch (\Exception $e) {
            // Xóa tất cả token nếu có lỗi
            $request->user()?->tokens()?->delete();

            return response()->json([
                'message' => 'Refresh token failed',
                'error' => $e->getMessage()
            ], 401);
        }
    }
}
