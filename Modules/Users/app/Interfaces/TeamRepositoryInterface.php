<?php

namespace Modules\Users\Interfaces;

use Modules\Users\Models\Team;

interface TeamRepositoryInterface
{
    public function all();
    public function getAll(array $filters = [], int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    public function find(string $id): ?Team;
    public function create(array $data): Team;
    public function update(Team $team, array $data): Team;
    public function delete(Team $team): bool;
    public function addUser(Team $team, string $userId, ?string $role = 'member'): void;
    public function removeUser(Team $team, string $userId): void;
}
