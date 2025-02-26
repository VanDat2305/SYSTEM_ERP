<?php

namespace Modules\Users\Repositories;


use Modules\Users\Interfaces\UserRepositoryInterface;
use Modules\Users\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function getAll()
    {
        return User::all();
    }

    public function findById($id)
    {
        return User::findOrFail($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update($id, array $data)
    {
        $user = User::findOrFail($id);
        $user->update($data);
        return $user;
    }

    public function delete($id)
    {
        return User::destroy($id);
    }
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}