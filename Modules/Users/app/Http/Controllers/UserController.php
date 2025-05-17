<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Users\Enums\UserStatus;
use Modules\Users\Http\Requests\CreateUserRequest;
use Modules\Users\Http\Requests\UpdateUserRequest;
use Modules\Users\Services\UserService;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        $users = $this->userService->getAllUsers();

        return response()->json($users);
    }

    public function show($id)
    {
        return response()->json($this->userService->getUserById($id));
    }

    public function store(CreateUserRequest $request)
    {
        try {
            $user = $this->userService->createUser($request->validated());

            return response()->json([
                'status' => true,
                'message' => __('messages.crud.created', ['model' => __('users::attr.users.user')]),
                'data' => $user,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => __('validation.failed'),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function update(UpdateUserRequest $request, string $id)
    {
        try {
            $user = $this->userService->updateUser($id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => __('messages.crud.updated', ['model' => __('users::attr.users.user')]),
                'data' => $user,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => __('validation.failed'),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(Request $request)
    {
        try {
            // Validate danh sách các IDs cần xóa
            $validated = $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|exists:users,id',
            ], [
                'ids.required' => __('validation.required'),
                'ids.array' => __('validation.array_required', ['attribute' => trans('users::attr.users.name')]),
                'ids.*.exists' => __('validation.not_found', ['attribute' => trans('users::attr.users.name')]),
            ]);
            // Xóa tất cả các menu trong danh sách
            foreach ($validated['ids'] as $id) {
                $this->userService->deleteUser($id);
            }

            $message = count($validated['ids']) === 1 
                ? __('messages.deleted_one_success', ['attribute' => trans('users::attr.users.name_only')]) 
                : __('messages.deleted_many_success', [
                    'attribute' => trans('users::attr.users.name_only'),
                    'count' => count($validated['ids'])
            ]);
            
            return response()->json([
                'success' => true,
                'message' => $message
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('users::messages.users.delete_failed'),
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }
}
