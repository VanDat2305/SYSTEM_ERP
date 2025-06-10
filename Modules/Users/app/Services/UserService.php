<?php

namespace Modules\Users\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Users\Interfaces\UserRepositoryInterface;
use App\Exceptions\CustomException;
use App\Helpers\ActivityLogger;
use Illuminate\Support\Arr;
use Modules\Users\Models\User;
use Modules\Users\Notifications\VerifyEmailNotification;
use Spatie\Activitylog\Models\Activity;


class UserService
{
    protected UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getAllUsers()
    {
        try {
            $users = $this->userRepository->getAll();
            return $users->map(fn($user) => $this->transformUser($user));
        } catch (\Throwable $e) {
            Log::error('Get all users failed: ' . $e->getMessage());
            throw new CustomException(__('messages.crud.failure',[
                'model' => __('users::attr.users.user'),
                'action' => __('messages.action.retrieved')
            ]), 500);
        }
    }

    public function getUserById($id)
    {
        try {
            $user = $this->userRepository->findById($id);
            if (!$user) {
                throw new CustomException(__('messages.crud.failure',[
                    'model' => __('users::attr.users.user'),
                    'action' => __('messages.action.retrieved')
                ]), 404);
            }
            return $this->transformUser($user);
        } catch (\Throwable $e) {
            Log::warning("User not found: ID {$id}. " . $e->getMessage());
            throw new CustomException($e->getMessage(), $e->getCode() ?: 404);
        }
    }

    public function createUser(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $roles = $data['roles'] ?? null;
                $permissions = $data['permissions'] ?? null;
                unset($data['roles']);
                unset($data['permissions']);

                $user = $this->userRepository->create($data);
                if ($roles) {
                    $user->assignRole($roles);
                }
                if ($permissions) {
                    $user->givePermissionTo($permissions);
                }
                // Gửi mail sau khi commit
                $user->notify(new VerifyEmailNotification());
                return $this->transformUser($user);
            });
        } catch (\Throwable $e) {
            Log::error('Create user failed: ' . $e->getMessage());
            throw new CustomException(__('messages.crud.failure', [
                'model' => __('users::attr.users.user'),
                'action' => __('messages.action.created')
            ]), 400);
        }
    }

    public function updateUser($id, array $data)
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $roles = $data['roles'] ?? null;
                $permissions = $data['permissions'] ?? null;
                unset($data['roles'], $data['permissions']);

                $user = $this->userRepository->findById($id);

                $fieldsToLog = ['name', 'email', 'password', /* thêm các trường khác */];

                $oldData = $user->only($fieldsToLog);

                User::withoutEvents(function () use ($user, $data) {
                    $user->update($data);
                });

                $user->syncRoles($roles ?: []);
                $user->syncPermissions($permissions ?: []);

                $newData = $user->only($fieldsToLog);

                $this->logModelUpdateWithRolesPermissions(
                    $user,
                    $oldData,
                    $newData,
                    $roles,
                    $permissions,
                    '',
                    'updated'
                );

                return $this->transformUser($user);
            });
        } catch (\Throwable $e) {
            Log::error("Update user failed (ID {$id}): " . $e->getMessage());
            throw new CustomException(__('messages.crud.failure', [
                'model' => __('users::attr.users.user'),
                'action' => __('messages.action.updated')
            ]), 400);
        }
    }

    public function deleteUser($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $deleted = $this->userRepository->delete($id);
                return [
                    'data' => null,
                    'message' => __('messages.crud.deleted', ['model' => __('users::attr.users.user')]),
                    'code' => 200,
                ];
            });
        } catch (\Throwable $e) {
            Log::error("Delete user failed (ID {$id}): " . $e->getMessage());
            throw new CustomException(__('messages.crud.failure', [
                'model' => __('users::attr.users.user'),
                'action' => __('messages.action.deleted')
            ]), 400);
        }
    }
    protected function transformUser($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status->value,
            'status_label' => $user->status->getLabel(),
            'two_factor_enabled' => $user->two_factor_enabled,
            'roles' => $user->roles->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ]),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'created_at' => $user->created_at,
        ];
    }
    /**
     * Ghi log update model với các trường thay đổi,
     * đặc biệt xử lý password và thêm roles + permissions vào properties.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $oldData Mảng dữ liệu cũ (chỉ các trường quan tâm)
     * @param array $newData Mảng dữ liệu mới (chỉ các trường quan tâm)
     * @param array|null $roles Mảng roles (tên hoặc id) hoặc null nếu không có
     * @param array|null $permissions Mảng permissions hoặc null nếu không có
     * @param string|null $note Ghi chú bổ sung
     * @param string $logName Tên log activity, mặc định 'updated'
     * @param \Illuminate\Foundation\Auth\User|null $causer Người thực hiện, mặc định auth user
     */
    function logModelUpdateWithRolesPermissions(
        $model,
        array $oldData,
        array $newData,
        ?array $roles = null,
        ?array $permissions = null,
        ?string $note = null,
        $causer = null,
    ) {
        $changedAttributes = [];
        $oldAttributes = [];

        foreach ($newData as $key => $value) {
            $oldValue = $oldData[$key] ?? null;
            if ($oldValue !== $value) {
                if ($key === 'password') {
                    $changedAttributes[$key] = 'Password changed';
                    $oldAttributes[$key] = '******';
                } else {
                    $changedAttributes[$key] = $value;
                    $oldAttributes[$key] = $oldValue;
                }
            }
        }

        $currentRoles = $model->roles->pluck('name')->toArray();
        $currentPermissions = $model->permissions->pluck('name')->toArray();

        if ($roles !== null) {
            $newRoles = Arr::wrap($roles); // đảm bảo là mảng
            // So sánh khác biệt (có thể dùng sort nếu cần thứ tự không quan trọng)
            if (array_diff($newRoles, $currentRoles) || array_diff($currentRoles, $newRoles)) {
                $changedAttributes['roles'] = $newRoles;
                $oldAttributes['roles'] = $currentRoles;
            }
        }

        if ($permissions !== null) {
            $newPermissions = Arr::wrap($permissions);
            if (array_diff($newPermissions, $currentPermissions) || array_diff($currentPermissions, $newPermissions)) {
                $changedAttributes['permissions'] = $newPermissions;
                $oldAttributes['permissions'] = $currentPermissions;
            }
        }
        if (!empty($changedAttributes)) {
            ActivityLogger::log('users.updated', 'Thay đổi thông tin người dùng',
                $model,
                [
                    'changed_attributes' => $changedAttributes,
                    'old_attributes' => $oldAttributes,
                    'note' => $note,
                    'roles' => $roles,
                    'permissions' => $permissions,
                ],
                $causer
            );
        }
    }
}
