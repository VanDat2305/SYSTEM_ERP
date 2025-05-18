<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Users\Services\RoleService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index()
    {
        try {
            $roles = $this->roleService->getAllRoles();
            return response()->json([
                'success' => true,
                'message' => trans('users::messages.roles.retrieved_success'),
                'data' => $roles->load('permissions')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() // Lấy thông điệp từ RoleService
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|unique:roles,title|string|max:100',
            'name' => 'required|unique:roles,name|max:100',
            'permissions' => 'nullable|array',
            'description' => 'nullable|string|max:255',
            'permissions.*' => 'exists:permissions,name',
            'status' => 'in:active,inactive'
        ],  [
            'title.required' =>  __('validation.required', ['attribute' => trans('users::attr.roles.title')]),
            'title.unique' =>  __('validation.unique', ['attribute' => trans('users::attr.roles.title')]),
            'title.string' =>  __('validation.string', ['attribute' => trans('users::attr.roles.title')]),
            'title.max' =>  __('validation.max', ['attribute' => trans('users::attr.roles.title')]),
            'name.required' =>  __('validation.required', ['attribute' => trans('users::attr.roles.name_code')]),
            'name.unique' => __('validation.unique', ['attribute' => trans('users::attr.roles.name_code')]),
            'name.max' => __('validation.max', ['attribute' => trans('users::attr.roles.name_code')]),
            'permissions.array' => __('validation.array_required', ['attribute' => trans('users::attr.permissions.name_only')]),
            'permissions.*.exists' => __('validation.exists_permissions'),
            'description.string' => __('validation.string', ['attribute' => trans('users::attr.roles.description')]),
            'description.max' => __('validation.max.string', ['attribute' => trans('users::attr.roles.description'), 'max' => 255]),
            'status.in' => __('validation.in', ['attribute' => trans('users::attr.roles.status')]),
        ]);
        try {
            $role = $this->roleService->createRoleWithPermissions($validated);
            return response()->json([
                'success' => true,
                'message' => trans('users::messages.roles.created_success'),
                'data' => $role->load('permissions')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100|unique:roles,title,' . $id,
            'name' => 'required|max:100|unique:roles,name,' . $id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
            'description' => 'nullable|string|max:255',
            'status' => 'in:active,inactive'
        ],  [
            'title.title' =>  __('validation.requiredstring', ['attribute' => trans('users::attr.roles.title')]),
            'title.string' =>  __('validation.string', ['attribute' => trans('users::attr.roles.title')]),
            'title.max' =>  __('validation.max', ['attribute' => trans('users::attr.roles.title')]),
            'title.unique' =>  __('validation.unique', ['attribute' => trans('users::attr.roles.title')]),
            'name.required' =>  __('validation.required', ['attribute' => trans('users::attr.roles.name_code')]),
            'name.unique' => __('validation.unique', ['attribute' => trans('users::attr.roles.name_code')]),
            'name.max' => __('validation.max', ['attribute' => trans('users::attr.roles.name_code')]),
            'permissions.array' => __('validation.array_required', ['attribute' => trans('users::attr.permissions.name_only')]),
            'permissions.*.exists' => __('validation.exists_permissions'),
            'description.string' => __('validation.string', ['attribute' => trans('users::attr.roles.description')]),
            'description.max' => __('validation.max.string', ['attribute' => trans('users::attr.roles.description'), 'max' => 255]),
            'status.in' => __('validation.in', ['attribute' => trans('users::attr.roles.status')]),
        ]
        );

        try {
            $role = $this->roleService->updateRoleWithPermissions($id, $validated);
            return response()->json([
                'success' => true,
                'message' => trans('users::messages.roles.updated_success'),
                'data' => $role->load('permissions')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            // Validate danh sách các IDs cần xóa
            $validated = $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|exists:roles,id',
            ], [
                'ids.required' => __('validation.required'),
                'ids.array' => __('validation.array_required', ['attribute' => trans('users::attr.roles.name')]),
                'ids.*.exists' => __('validation.not_found', ['attribute' => trans('users::attr.roles.name')]),
            ]);
            // Xóa tất cả các menu trong danh sách
            foreach ($validated['ids'] as $id) {
                $this->roleService->deleteRole($id);
            }
    
            $message = count($validated['ids']) === 1 
                ? __('messages.deleted_one_success', ['attribute' => trans('users::attr.roles.name_only')]) 
                : __('messages.deleted_many_success', [
                    'attribute' => trans('users::attr.roles.name_only'),
                    'count' => count($validated['ids'])
            ]);
            
            return response()->json([
                'success' => true,
                'message' => $message
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('users::messages.roles.failed_to_delete', ['error' => $e->getMessage()])
            ], 500);
        }
    }

    public function assignPermissions(Request $request, $id)
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        try {
            $role = $this->roleService->assignPermissionsToRole($id, $validated['permissions']);
            return response()->json([
                'success' => true,
                'message' => trans('users::messages.roles.assigned_permissions_success'),
                'data' => $role->load('permissions')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}