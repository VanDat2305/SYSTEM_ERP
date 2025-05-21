<?php

namespace Modules\Core\Services;

use Modules\Core\Interfaces\ObjectMetaRepositoryInterface;

class ObjectMetaService
{
    public function __construct(protected ObjectMetaRepositoryInterface $repo) {}
    public function list()
    {
        return $this->repo->all();
    }
    public function get($id)
    {
        return $this->repo->find($id);
    }
    public function store(array $data)
    {
        return $this->repo->create($data);
    }
    public function update($id, array $data)
    {
        return $this->repo->update($id, $data);
    }
    public function destroy($id)
    {
        return $this->repo->delete($id);
    }
}
