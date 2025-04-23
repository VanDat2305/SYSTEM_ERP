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
            'password' => 'required|min:6'
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
            'token' => $authData['token'],
            'user' => $authData['user']
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
}
