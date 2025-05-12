<?php

namespace Modules\Users\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        $roles = $user->roles->where('status', 'active')->pluck('name');
        
        $permissions = $user->getAllPermissions()->where('status', 'active')->pluck('name');

        $menu = $this->buildMenu($user);
        if ($user->two_factor_enabled) {
            $token = $user->createToken('pre_2fa_token', ['2fa:verify'])->plainTextToken;
        } else {
            $token = $user->createToken('api-token', ['access:full'])->plainTextToken;
        }
        $user = $this->transformUser($user);
        return [
            'token' => $token,
            'user' => $user,
            'menu' => $menu,
            'roles' => $roles,
            'permissions' => $permissions,
        ];
    }
    public function logout(User $user)
    {
        try {
            $user->currentAccessToken()->delete();
        } catch (\Exception $e) {
            // Log the error message
            Log::error('Logout failed: ' . $e->getMessage());
            throw new \Exception(__('messages.logout.failed'), 500);
        }
        
    }
    protected function buildMenu(User $user)
    {
        $menu = [
            [
                'title' => __('Dashboard'),
                'icon' => 'mdi-view-dashboard',
                'route' => 'dashboard',
                'routeName' => 'dashboard',
                'permission' => 'dashboard.view',

            ],
            [
                'title' => __('System'),
                'icon' => 'mdi-system-group',
                'isDefault' => true,
                'children' => [
                    [
                        'title' => __('Users'),
                        'route' => 'system/users',
                        'routeName' => 'system.users',
                        'permission' => 'users.read',
                    ],
                    [
                        'title' => __('Roles'),
                        'route' => 'system/roles',
                        'routeName' => 'system.roles',
                        'permission' => 'roles.read',
                    ],
                ],
            ],
        ];
        return $this->filterMenu($menu, $user);
    }

    protected function filterMenu(array $menu, User $user)
    {
        return array_values(array_filter(array_map(function ($item) use ($user) {
            // Nếu có children, đệ quy filter
            if (isset($item['children'])) {
                $filteredChildren = $this->filterMenu($item['children'], $user);
                if (count($filteredChildren) > 0) {
                    $item['children'] = $filteredChildren;
                    return $item;
                }
                return null;
            }
    
            // Kiểm tra permission
            if (isset($item['permission']) && !$user->can($item['permission'])) {
                return null;
            }
    
            return $item;
        }, $menu)));
    }
    protected function transformUser($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status->value,
            'status_label' => $user->status->getLabel(),
            'last_login_at' => $user->last_login_at,
            'two_factor_enabled' => $user->two_factor_enabled     
        ];
    }
}