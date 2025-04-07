<?php

namespace Modules\Users\Repositories;

use Modules\Users\Interfaces\RoleRepositoryInterface;
use Spatie\Permission\Models\Role;

class RoleRepository implements RoleRepositoryInterface
{
    public function all()
    {
        return Role::all();
    }

    public function find($id)
    {
        return Role::findOrFail($id);
    }

    public function create(array $data)
    {
        return Role::create($data);
    }

    public function update($id, array $data)
    {
        $role = $this->find($id);
        $role->update($data);
        return $role;
    }

    public function delete($id)
    {
        $role = $this->find($id);
        return $role->delete();
    }

    public function assignPermissions($roleId, array $permissions)
    {
        $role = $this->find($roleId);
        $role->syncPermissions($permissions);
        return $role;
    }
    
}