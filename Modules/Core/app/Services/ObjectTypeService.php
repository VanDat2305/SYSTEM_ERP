<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Interfaces\ObjectTypeRepositoryInterface;
use Modules\Core\Models\ObjectType;

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
        $repo = $this->repo->create($data);
        $this->refreshObjectTypeCache();
        return $repo;
    }
    public function update($id, array $data)
    {
        $repo = $this->repo->update($id, $data);
        $this->refreshObjectTypeCache();
        return $repo;
    }
    public function destroy($id)
    {
        return $this->repo->delete($id);
    }
    public function getCachedObjectTypes()
    {
        $cacheKey = 'core:objects:types';

        return Cache::remember($cacheKey, now()->addHours(2), function () {
            return ObjectType::orderBy('order')->get(); // hoặc thêm `->where('status', 'active')`
        });
    }

    public function refreshObjectTypeCache()
    {
        $cacheKey = 'core:objects:types';
        Cache::forget($cacheKey);

        return $this->getCachedObjectTypes();
    }

}
