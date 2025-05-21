<?php

namespace Modules\Core\Interfaces;

interface ObjectTypeRepositoryInterface
{
    public function all();
    public function allWithObjects($request);
    public function find($id);
    public function findById($id, $status = 'active');
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
