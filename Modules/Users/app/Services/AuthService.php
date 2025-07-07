<?php

namespace Modules\Users\Services;

use App\Helpers\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Users\Repositories\UserRepository;
use Illuminate\Validation\ValidationException;
use Modules\Users\Models\User;
use Modules\Users\Notifications\VerifyEmailNotification;
use Illuminate\Auth\Events\Registered;

class AuthService
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(array $data): User
    {
        try {
            DB::beginTransaction();

            $user = $this->userRepository->create($data);

            DB::commit();

            // Gửi mail sau khi commit
            $user->notify(new VerifyEmailNotification());

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('regist failed', ['error' => $e->getMessage()]);
            throw new \Exception(__('messages.register.failed'), 500);
        }
    }

    public function login(array $credentials)
    {
        $user = $this->userRepository->findByEmail($credentials['email']);
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('messages.login.credentials_incorrect')],
            ]);
        }
        if ($user->status->value !== 'active') {
            throw ValidationException::withMessages([
                'email' => [__('messages.login.account_inactive')],
            ]);
        }
        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => [__('users::messages.users.email_not_verified')],
            ]);
        }
        $user->update(['last_login_at' => Carbon::now()]);
        $roles = $user->roles->where('status', 'active')->pluck('name');

        $permissions = $user->getAllPermissions()->where('status', 'active')->pluck('name');

        $menu = $this->buildMenu($user);
        if (empty($menu)) {
            throw ValidationException::withMessages([
                'email' => [__('messages.login.account_no_permission')],
            ]);
        }
        if ($user->two_factor_enabled) {
            // Token xác thực 2FA (5 phút)
            $token = $user->createToken(
                'pre_2fa_token',
                ['2fa:verify'],
                now()->addMinutes(5)
            )->plainTextToken;

            return [
                'token' => $token,
                'user' => $this->transformUser($user),
                'menu' => $menu,
                'roles' => $roles,
                'permissions' => $permissions,
            ];
        }

        // Tạo access token và refresh token khi không có 2FA
        $accessToken = $user->createToken(
            'access_token',
            ['*'], // Access token hết hạn sau 15 phút
        )->plainTextToken;

        $refreshToken = $user->createToken(
            'refresh_token',
            ['refresh'],
            now()->addDays(30) // Refresh token hết hạn sau 30 ngày
        )->plainTextToken;
        ActivityLogger::log(
            'login',
            "Đăng nhập thành công",
            $user,
            [],
            $user
        );

        return [
            'token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => $this->transformUser($user),
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
                'title'      => __('Dashboard'),
                'icon'       => 'mdi-view-dashboard',
                'route'      => 'dashboard',
                'routeName'  => 'dashboard',
                'permission' => 'dashboard.view',
            ],
            [
                'title'      => __('System'),
                'icon'       => 'mdi-system-group',
                'children'   => [

                    [
                        'title'      => __('Roles'),
                        'icon'       => 'mdi-account-key',
                        'route'      => 'system/roles',
                        'routeName'  => 'system.roles',
                        'permission' => 'roles.view',
                    ],
                    [
                        'title'      => __('Users'),
                        'icon'       => 'mdi-account',
                        'route'      => 'system/users',
                        'routeName'  => 'system.users',
                        'permission' => 'users.view',
                    ],

                    [
                        'title'      => __('Permissions'),
                        'icon'       => 'mdi-shield-key',
                        'route'      => 'system/permissions',
                        'routeName'  => 'system.permissions',
                        'permission' => 'permissions.view',
                    ],
                    [
                        'title'      => __('Activity Logs'),
                        'icon'       => 'mdi-format-list-bulleted',
                        'route'      => 'system/logs',
                        'routeName'  => 'system.logs',
                        'permission' => 'logs.view',
                    ],
                    [
                        'title'      => __('Teams'),
                        'icon'       => 'mdi-account-multiple',
                        'route'      => 'system/teams',
                        'routeName'  => 'system.teams',
                        'permission' => 'teams.view',
                    ],
                ],
            ],
            [
                'title'      => __('Customers'),
                'icon'       => 'mdi-account-group',
                'route'      => 'customers',
                'routeName'  => 'customers.list',
                'permission' => 'customers.view',
            ],
            [
                'title'      => __('Orders'),
                'icon'       => 'mdi-cart',
                'route'      => 'orders',
                'routeName'  => 'orders.list',
                'permission' => 'orders.view',
            ],
            [
                'title'      => __('Service Packages'),
                'icon'       => 'mdi-package-variant',
                'route'      => 'service-packages',
                'routeName'  => 'service_packages.name',
                'permission' => 'service_packages.view',
            ],
            [
                'title'      => __('File Manager'),
                'icon'       => 'mdi-file-document',
                'route'      => 'filemanager',
                'routeName'  => 'filemanager.name',
                'permission' => 'files.list',
            ],
            [
                'title'      => __('Account Panel'),
                'icon'       => 'mdi-account-circle',
                'route'      => 'settings/account',
                'routeName'  => 'settings.account',
                'permission' => 'account.panel',
            ],
            [
                'title'      => __('Dynamic Lists'),
                'icon'       => 'mdi-playlist-edit',
                'route'      => 'settings/dynamic-lists',
                'routeName'  => 'settings.dynamiclists',
                'permission' => 'objects.view',
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
