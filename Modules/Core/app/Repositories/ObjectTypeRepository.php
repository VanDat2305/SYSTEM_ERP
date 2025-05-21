<?php

namespace Modules\Core\Repositories;

use Modules\Core\Models\ObjectType;
use Modules\Core\Interfaces\ObjectTypeRepositoryInterface;

class ObjectTypeRepository implements ObjectTypeRepositoryInterface
{
    public function all()
    {
        return ObjectType::all();
    }
    public function allWithObjects($request)
    {
        $query = ObjectType::with('object_items');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        return $query->get();
    }
    public function find($id)
    {
        return ObjectType::findOrFail($id);
    }
    public function findById($id, $status = 'active')
    {
        $query = ObjectType::with('object_items')->where('id', $id);

        if ($status !== 'all') {
            $query->where('status', $status);
        }
        return $query->firstOrFail();
    }
    public function create(array $data)
    {
        return ObjectType::create($data);
    }
    public function update($id, array $data)
    {
        $objectType = ObjectType::findOrFail($id);
        $objectType->update($data);
        return $objectType;
    }
    public function delete($id)
    {
        $objectType = ObjectType::findOrFail($id);
        return $objectType->delete();
    }
}
