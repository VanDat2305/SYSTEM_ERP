<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Http\Requests\ObjectMetaRequest;
use Modules\Core\Services\ObjectMetaService;
use Modules\Core\Http\Resources\ObjectMetaResource;

class ObjectMetaController extends Controller
{
    protected $service;

    public function __construct(ObjectMetaService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return ObjectMetaResource::collection($this->service->list());
    }
    public function show($id)
    {
        return new ObjectMetaResource($this->service->get($id));
    }
    public function store(ObjectMetaRequest $request)
    {
        return new ObjectMetaResource($this->service->store($request->validated()));
    }
    public function update(ObjectMetaRequest $request, $id)
    {
        return new ObjectMetaResource($this->service->update($id, $request->validated()));
    }
    public function destroy($id)
    {
        $this->service->destroy($id);
        return response()->json([], 200);
    }
}
