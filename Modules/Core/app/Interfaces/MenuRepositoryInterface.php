<?php

namespace Modules\Core\Interfaces;

interface MenuRepositoryInterface
{
    public function all();

    public function find(string $id);

    public function create(array $data);

    public function update(string $id, array $data);

    public function delete(string $id);
}
