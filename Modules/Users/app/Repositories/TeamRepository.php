<?php

namespace Modules\Users\Repositories;

use Modules\Users\Interfaces\TeamRepositoryInterface;
use Modules\Users\Models\Team;

class TeamRepository implements TeamRepositoryInterface
{
    public function __construct(private Team $model)
    {
    }
    public function all()
    {
        return Team::with('users')->get();
    }
    public function getAll(array $filters = [], int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->model
            ->with('users')
            ->when(isset($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['user_id']), function ($query) use ($filters) {
                $userId = $filters['user_id'];
                $query->whereHas('users', function ($q) use ($userId) {
                    $q->where('users.id', $userId);
                });
            })
            ->when(isset($filters['role']), function ($query) use ($filters) {
                $role = $filters['role'];
                $query->whereHas('users', function ($q) use ($role) {
                    $q->wherePivot('role', $role);
                });
            })
            ->when(array_key_exists('is_active', $filters), function ($query) use ($filters) {
                $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }


    public function find(string $id): ?Team
    {
        return Team::with('users')->find($id);
    }

    public function create(array $data): Team
    {
        return Team::create($data);
    }

    public function update(Team $team, array $data): Team
    {
        $team->update($data);
        return $team;
    }

    public function delete(Team $team): bool
    {
        return $team->delete();
    }

    public function addUser(Team $team, string $userId, ?string $role = 'member'): void
    {
        $team->users()->syncWithoutDetaching([
            $userId => ['role' => $role]
        ]);
    }

    public function removeUser(Team $team, string $userId): void
    {
        $team->users()->detach($userId);
    }
}
