<?php

namespace Modules\Users\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Users\Interfaces\UserRepositoryInterface;
use App\Exceptions\CustomException;

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
                unset($data['roles']);

                $user = $this->userRepository->create($data);

                if ($roles) {
                    $user->roles()->sync($roles);
                }

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
                $user = $this->userRepository->update($id, $data);

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
            'roles' => $user->roles->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ]),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];
    }
}
