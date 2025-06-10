<?php

namespace Modules\Users\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Users\Interfaces\TeamRepositoryInterface;
use Modules\Users\Models\Team;
use App\Helpers\ActivityLogger;

class TeamService
{
    protected $teams;

    public function __construct(TeamRepositoryInterface $teams)
    {
        $this->teams = $teams;
    }

    public function getAllTeams()
    {
        return $this->teams->all();
    }

    public function getTeams(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->teams->getAll($filters, $perPage);
    }

    public function getTeamById(string $id): ?Team
    {
        return $this->teams->find($id);
    }

    public function createTeam(array $data)
    {
        $team = $this->teams->create($data);

        ActivityLogger::log(
            'Nhóm',
            'Tạo nhóm mới',
            $team,
            ['attributes' => $team->toArray()]
        );

        return $team;
    }

    public function updateTeam(Team $team, array $data)
    {
        $original = $team->getOriginal();
        $updatedTeam = $this->teams->update($team, $data);

        $changes = $updatedTeam->getChanges(); // chỉ các trường bị thay đổi
        $oldValues = array_intersect_key($original, $changes);

        ActivityLogger::log(
            'Nhóm',
            'Cập nhật nhóm',
            $updatedTeam,
            [
                'old_attributes' => $oldValues,
                'changed_attributes' => $changes
            ]
        );

        return $updatedTeam;
    }


    public function deleteTeam(Team $team)
    {
        $teamData = $team->toArray();
        $this->teams->delete($team);

        ActivityLogger::log(
            'Nhóm',
            'Xóa nhóm',
            $team,
            ['attributes' => $teamData]
        );

        return true;
    }

    public function addUserToTeam(Team $team, string $userId, ?string $role = 'member')
    {
        $result = $this->teams->addUser($team, $userId, $role);

        ActivityLogger::log(
            'Nhóm',
            'Thêm người dùng vào nhóm',
            $team,
            ['user_id' => $userId, 'role' => $role]
        );

        return $result;
    }

    public function removeUserFromTeam(Team $team, string $userId)
    {
        $result = $this->teams->removeUser($team, $userId);

        ActivityLogger::log(
            'Nhóm',
            'Xóa người dùng khỏi nhóm',
            $team,
            ['user_id' => $userId]
        );

        return $result;
    }
}
