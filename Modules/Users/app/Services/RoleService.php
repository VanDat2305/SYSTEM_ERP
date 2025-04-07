<?php

namespace Modules\Users\Services;

use Modules\Users\Interfaces\RoleRepositoryInterface;
use Exception;

class RoleService
{
    protected $roleRepository;

    public function __construct(RoleRepositoryInterface $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function getAllRoles()
    {
        $roles = $this->roleRepository->all();
        if ($roles->isEmpty()) {
            throw new Exception(trans('users::messages.roles.failed_to_retrieve_empty'));
        }
        return $roles;
    }

    public function getRole($id)
    {
        try {
            return $this->roleRepository->find($id);
        } catch (Exception $e) {
            throw new Exception(trans('users::messages.roles.failed_to_find', ['id' => $id]));
        }
    }

    public function createRole(array $data)
    {
        if (empty($data['name'])) {
            throw new Exception(trans('users::messages.roles.failed_to_create_empty_name'));
        }
        return $this->roleRepository->create($data);
    }

    public function updateRole($id, array $data)
    {
        if (empty($data['name'])) {
            throw new Exception(trans('users::messages.roles.failed_to_update_empty_name'));
        }
        return $this->roleRepository->update($id, $data);
    }

    public function deleteRole($id)
    {
        try {
            return $this->roleRepository->delete($id);
        } catch (Exception $e) {
            throw new Exception(trans('users::messages.roles.failed_to_delete', ['id' => $id]));
        }
    }

    public function assignPermissionsToRole($roleId, array $permissions)
    {
        try {
            return $this->roleRepository->assignPermissions($roleId, $permissions);
        } catch (Exception $e) {
            throw new Exception(trans('users::messages.roles.failed_to_assign_permissions', ['id' => $roleId]));
        }
    }

    public function createRoleWithPermissions(array $data)
    {
        if (empty($data['name'])) {
            throw new Exception(trans('users::messages.roles.failed_to_create_empty_name'));
        }
    
        try {
            // Tạo role với guard_name là 'api'
            $role = $this->roleRepository->create([
                'name' => $data['name'],
                'guard_name' => 'api'
            ]);
    
            // Gán permissions nếu có
            if (!empty($data['permissions'])) {
                $this->roleRepository->assignPermissions($role->id, $data['permissions']);
            }
    
            return $role;
        } catch (Exception $e) {
            // Ném lại ngoại lệ với thông điệp dịch
            throw new Exception(trans('users::messages.roles.failed_to_create', ['error' => $e->getMessage()]));
        }
    }

    public function updateRoleWithPermissions($id, array $data)
    {
        if (empty($data['name'])) {
            throw new Exception(trans('users::messages.roles.failed_to_update_empty_name'));
        }
    
        try {
            // Cập nhật thông tin role
            $role = $this->roleRepository->update($id, ['name' => $data['name']]);
    
            // Đồng bộ permissions nếu có trong data
            if (array_key_exists('permissions', $data)) {
                $this->roleRepository->assignPermissions($role->id, $data['permissions'] ?? []);
            }
    
            return $role;
        } catch (Exception $e) {
            throw new Exception(trans('users::messages.roles.failed_to_update', ['error' => $e->getMessage()]));
        }
    }
}