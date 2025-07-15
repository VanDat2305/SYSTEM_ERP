<?php

namespace Modules\Users\Repositories;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Users\Interfaces\UserRepositoryInterface;
use Modules\Users\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function getAll()
    {
        return User::with('roles')->get();  
    }

    public function findById($id)
    {
        $user = User::with('roles')->find($id);

        if (!$user) {
            throw ValidationException::withMessages([
                'id' => [__('messages.crud.not_found', ['model' => trans('users::attr.users.user')])],
            ]);
        }
    
        return $user;
    }

    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $user;
    }

    public function update($id, array $data)
    {
        $user = User::findOrFail($id);
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $roles = $data['roles'] ?? null;
        unset($data['roles']);

        $user->update($data);
        if (!empty($roles) && is_array($roles)) {
            $user->syncRoles($roles);
        }

        return $user;
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);
        return $user->delete();
    }
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}