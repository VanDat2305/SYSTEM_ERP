<?php

namespace Modules\Core\Interfaces;

interface ObjectItemRepositoryInterface
{
    public function all();
    public function allWithMeta($request);
    public function filterByStatus($status = 'active');
    public function find($id);
    public function findById($id, $status = 'active');
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
