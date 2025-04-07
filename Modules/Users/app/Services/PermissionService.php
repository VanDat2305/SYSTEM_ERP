<?php

namespace Modules\Users\Services;

use Exception;
use Modules\Users\Interfaces\PermissionRepositoryInterface;

class PermissionService
{
    protected $permissionRepository;

    public function __construct(PermissionRepositoryInterface $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

    public function getAllPermissions()
    {
        $permissions = $this->permissionRepository->all();
        if ($permissions->isEmpty()) {
            throw new Exception(trans('users::messages.permissions.failed_to_retrieve_empty'));
        }
        return $permissions;
    }

    public function getPermission($id)
    {
        try {
            return $this->permissionRepository->find($id);
        } catch (Exception $e) {
            throw new Exception(trans('users::messages.permissions.failed_to_find', ['id' => $id]));
        }
    }

    public function createPermission(array $data)
    {
        return $this->permissionRepository->create($data);
    }

    public function updatePermission($id, array $data)
    {
        return $this->permissionRepository->update($id, $data);
    }

    public function deletePermission($id)
    {
        return $this->permissionRepository->delete($id);
    }
}