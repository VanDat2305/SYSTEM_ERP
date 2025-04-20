<?php

namespace Modules\Users\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Modules\Users\Repositories\UserRepository;
use Illuminate\Validation\ValidationException;
use Modules\Users\Models\User;

class AuthService
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(array $data): User
    {
        return $this->userRepository->create($data);
    }

    public function login(array $credentials)
    {
        $user = $this->userRepository->findByEmail($credentials['email']);
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('messages.login.credentials_incorrect')],
            ]);
        }
        $user->update(['last_login_at' => Carbon::now()]);

        return [
            'token' => $user->createToken('api-token')->plainTextToken,
            'user' => $user
        ];
    }
}