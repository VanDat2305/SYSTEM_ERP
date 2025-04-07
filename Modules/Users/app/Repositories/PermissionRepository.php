<?php

namespace Modules\Users\Repositories;

use Modules\Users\Interfaces\PermissionRepositoryInterface;
use Spatie\Permission\Models\Permission;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function all()
    {
        return Permission::all();
    }

    public function find($id)
    {
        return Permission::findOrFail($id);
    }

    public function create(array $data)
    {
        return Permission::create($data);
    }

    public function update($id, array $data)
    {
        $permission = $this->find($id);
        $permission->update($data);
        return $permission;
    }

    public function delete($id)
    {
        $permission = $this->find($id);
        return $permission->delete();
    }
}