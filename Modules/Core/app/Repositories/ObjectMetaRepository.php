<?php

namespace Modules\Core\Repositories;

use Modules\Core\Models\ObjectMeta;
use Modules\Core\Interfaces\ObjectMetaRepositoryInterface;

class ObjectMetaRepository implements ObjectMetaRepositoryInterface
{
    public function all() { return ObjectMeta::all(); }
    public function find($id) { return ObjectMeta::findOrFail($id); }
    public function create(array $data) { return ObjectMeta::create($data); }
    public function update($id, array $data) { $m = ObjectMeta::findOrFail($id); $m->update($data); return $m; }
    public function delete($id) { return ObjectMeta::findOrFail($id)->delete(); }
}