<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Core\Interfaces\ObjectTypeRepositoryInterface;

class ObjectTypeService
{
    protected $repo;

    public function __construct(ObjectTypeRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function list()
    {
        return $this->repo->all();
    }
    public function listWithObjects($request)
    {
        return $this->repo->allWithObjects($request);
    }
    public function get($id)
    {
        return $this->repo->find($id);
    }
    public function getById($id, $request)
    {

        $status = $request->get('status', 'active');
        
        return $this->repo->findById($id, $status);
    }
    public function store(array $data)
    {
        $data['created_by'] = Auth::guard('sanctum')->user()->id ?? null;
        $data['status'] = $data['status'] ?? 'active';
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
