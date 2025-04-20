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

    public function destroy(string $id)
    {
        try {
            $this->userService->deleteUser($id);

            return response()->json([
                'status' => true,
                'message' =>  __('messages.crud.delete', ['model' => __('users::attr.users.user')]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('users.delete_failed'),
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }
}
