<?php

namespace Modules\Users\Interfaces;

use Modules\Users\Models\User;

interface UserRepositoryInterface
{
    public function getAll();
    public function findById($id);
    public function create(array $data): User;
    public function update($id, array $data);
    public function delete($id);
    public function findByEmail(string $email): ?User;
}

