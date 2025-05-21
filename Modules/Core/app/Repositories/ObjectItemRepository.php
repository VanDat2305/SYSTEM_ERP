<?php

namespace Modules\Core\Repositories;

use Modules\Core\Models\ObjectItem;
use Modules\Core\Interfaces\ObjectItemRepositoryInterface;

class ObjectItemRepository implements ObjectItemRepositoryInterface
{
    public function allWithMeta($request)
    {
        $query = ObjectItem::with('meta');
        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->input('object_type_id')) {
            $query->where('object_type_id', $request->input('object_type_id'));
        }
        if ($request->input('code')) {
            $query->where('code', 'like', '%' . $request->input('code') . '%');
        }
        if ($request->input('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }
        return $query->orderBy('order')->get();
       
    }

    public function filterByStatus($status = 'active')
    {
        $query = ObjectItem::with('meta');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return $query->get();
    }
    public function findById($id, $status = 'active')
    {
        $query = ObjectItem::with('meta')->where('id', $id);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return $query->firstOrFail();
    }

    public function all()
    {
        return ObjectItem::all();
    }
    public function find($id)
    {
        return ObjectItem::findOrFail($id);
    }
    public function create(array $data)
    {
        return ObjectItem::create($data);
    }
    public function update($id, array $data)
    {
        $m = ObjectItem::findOrFail($id);
        $m->update($data);
        return $m;
    }
    public function delete($id)
    {
        return ObjectItem::findOrFail($id)->delete();
    }
}
