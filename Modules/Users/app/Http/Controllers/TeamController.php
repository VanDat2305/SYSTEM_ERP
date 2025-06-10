<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Users\Models\Team;
use Modules\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Users\Services\TeamService;

class TeamController extends Controller
{
    protected $service;

    public function __construct(TeamService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'user_id', 'role', 'is_active']);
        $perPage = $request->input('per_page', 10);

        $teams = $this->service->getTeams($filters, $perPage);

        return response()->json($teams);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|unique:teams,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ], [
            'name.required' => __('validation.required', ['attribute' => __('users::team.name')]),
            'name.unique' => __('validation.unique', ['attribute' => __('users::team.name')]),
            'description.string' => __('validation.string', ['attribute' => __('users::team.description')])
        ]);
        $data['id'] = Str::uuid();
        $team = $this->service->createTeam($data);
        return response()->json([
            'message' => __('users::team.created'),
            'data' => $team
        ], 201);
    }

    public function show(Team $team)
    {
        return response()->json($team->load('users'));
    }

    public function update(Request $request, $id)
    {
        $team = $this->service->getTeamById($id);
        if (!$team) {
            return response()->json(["messages" => __('users::team.not_found')], 404);
        }
        $data = $request->validate([
            'name' => 'required|unique:teams,name,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ], [
            'name.required' => __('validation.required', ['attribute' => __('users::team.name')]),
            'name.unique' => __('validation.unique', ['attribute' => __('users::team.name')]),
            'description.string' => __('validation.string', ['attribute' => __('users::team.description')])
        ]);
        $team = $this->service->updateTeam($team, $data);
        return response()->json([
            'message' => __('users::team.updated'),
            'data' => $team
        ]);
    }

    public function destroy($id)
    {
        $team = $this->service->getTeamById($id);
        if (!$team) {
            return response()->json([
                "message" => __('users::team.not_found')
            ], 404);
        }
        $this->service->deleteTeam($team);
        return response()->json([
            'message' => __('users::team.deleted')
        ]);
    }

    public function addUser(Request $request, $id)
    {
        $team = $this->service->getTeamById($id);
        if (!$team) {
            return response()->json(["message" => __('users::team.not_found')], 404);
        }
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|string'
        ], [
            'user_id.required' => __('validation.required', ['attribute' => __('users::team.user_id')]),
            'user_id.exists' => __('validation.exists', ['attribute' => __('users::team.user_id')]),
            'role.string' => __('validation.string', ['attribute' => __('users::team.role')])
        ]);
        $this->service->addUserToTeam($team, $data['user_id'], $data['role'] ?? 'member');
        return response()->json([
            'message' => __('users::team.user_added')
        ]);
    }

    public function removeUser(Team $team, User $user)
    {
        $this->service->removeUserFromTeam($team, $user->id);
        return response()->json([
            'message' => __('users::team.user_removed')
        ]);
    }
    public function updateUserTeam(Request $request, Team $team, User $user)
    {
        $data = $request->validate([
            'role' => 'nullable|string'
        ], [
            'role.string' => __('validation.string', ['attribute' => __('users::team.role')])
        ]);
        
        if ($data['role'] ?? null) {
            $this->service->addUserToTeam($team, $user->id, $data['role']);
        } else {
            $this->service->removeUserFromTeam($team, $user->id);
        }

        return response()->json([
            'message' => __('users::team.user_updated')
        ]);
    }
}
