<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Users\Services\PermissionService;

class PermissionController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index(Request $request)
    {
        try {
            $permissions = $this->permissionService->getAllPermissions($request);
            return response()->json([
                'success' => true,
                'message' => trans('users::messages.permissions.retrieved_success'),
                'data' => $permissions
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('users::messages.permissions.failed_to_retrieve', ['error' => $e->getMessage()])
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|unique:permissions|string|max:100',
            'name' => 'required|unique:permissions|max:100',
            'description' => 'nullable|string|max:255',
            'status' => 'in:active,inactive',
        ], [
            'title.required' =>  __('validation.required', ['attribute' => trans('users::attr.permissions.name')]),
            'title.unique' =>  __('validation.unique', ['attribute' => trans('users::attr.permissions.name')]),
            'title.string' =>  __('validation.string', ['attribute' => trans('users::attr.permissions.name')]),
            'title.max' =>  __('validation.max', ['attribute' => trans('users::attr.permissions.name')]),
            'name.required' =>  __('validation.required', ['attribute' => trans('users::attr.permissions.name_code')]),
            'name.unique' => __('validation.unique', ['attribute' => trans('users::attr.permissions.name_code')]),
            'description.nullable' => __('validation.nullable', ['attribute' => trans('users::attr.permissions.description')]),
            'description.string' => __('validation.string', ['attribute' => trans('users::attr.permissions.description')]),
            'description.max' => __('validation.max', ['attribute' => trans('users::attr.permissions.description')]),
            'status.in' => __('validation.in', ['attribute' => trans('users::attr.permissions.status')]),
        ]);
        $data['guard_name'] = 'api';
        try {
            $permission = $this->permissionService->createPermission($data);
            return response()->json([
                'success' => true,
                'message' => trans('users::messages.permissions.created_success'),
                'data' => $permission
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('users::messages.permissions.failed_to_create', ['error' => $e->getMessage()])
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            return response()->json($this->permissionService->getPermission($id));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('users::messages.permissions.failed_to_find', [
                    'error' => $e->getMessage(),
                    'id' => $id
                    ])
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'title' => 'required|string|max:100|unique:permissions,title,' . $id,
            'name' => 'required|unique:permissions,name,' . $id,
            'description' => 'nullable|string|max:255',
            'status' => 'in:active,inactive',
        ], [
            'title.required' =>  __('validation.required', ['attribute' => trans('users::attr.permissions.name')]),
            'title.unique' =>  __('validation.unique', ['attribute' => trans('users::attr.permissions.name')]),
            'title.string' =>  __('validation.string', ['attribute' => trans('users::attr.permissions.name')]),
            'title.max' =>  __('validation.max', ['attribute' => trans('users::attr.permissions.name')]),
            'name.required' =>  __('validation.required', ['attribute' => trans('users::attr.permissions.name_code')]),
            'name.unique' => __('validation.unique', ['attribute' => trans('users::attr.permissions.name_code')]),
            'description.nullable' => __('validation.nullable', ['attribute' => trans('users::attr.permissions.description')]),
            'description.string' => __('validation.string', ['attribute' => trans('users::attr.permissions.description')]),
            'description.max' => __('validation.max', ['attribute' => trans('users::attr.permissions.description')]),
            'status.in' => __('validation.in', ['attribute' => trans('users::attr.permissions.status')]),
        ]);

        try {
            $permission = $this->permissionService->updatePermission($id, $data);
            return response()->json([
                'success' => true,
                'message' => trans('users::messages.permissions.updated_success'),
                'data' => $permission
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('users::messages.permissions.failed_to_update', ['error' => $e->getMessage()])
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            // Validate danh sách các IDs cần xóa
            $validated = $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|exists:permissions,id',
            ], [
                'ids.required' => __('validation.required'),
                'ids.array' => __('validation.array_required', ['attribute' => trans('users::attr.permissions.name')]),
                'ids.*.exists' => __('validation.not_found', ['attribute' => trans('users::attr.permissions.name')]),
            ]);
            // Xóa tất cả các menu trong danh sách
            foreach ($validated['ids'] as $id) {
                $this->permissionService->deletePermission($id);
            }
    
            $message = count($validated['ids']) === 1 
                ? __('messages.deleted_one_success', ['attribute' => trans('users::attr.permissions.name_only')]) 
                : __('messages.deleted_many_success', [
                    'attribute' => trans('users::attr.permissions.name_only'),
                    'count' => count($validated['ids'])
            ]);
            
            return response()->json([
                'success' => true,
                'message' => $message
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('users::messages.permissions.failed_to_delete', ['error' => $e->getMessage()])
            ], 500);
        }
    }
}