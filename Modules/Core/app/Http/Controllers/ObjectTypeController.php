<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Http\Requests\ObjectTypeRequest;
use Modules\Core\Services\ObjectTypeService;
use Modules\Core\Http\Resources\ObjectTypeResource;

class ObjectTypeController extends Controller
{
    protected $service;

    public function __construct(ObjectTypeService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return ObjectTypeResource::collection(
            $this->service->listWithObjects($request)
        );
    }
    public function show(Request $request, $id)
    {
        if ($request->has('object_items')) {
            return new ObjectTypeResource($this->service->getById($id, $request));
        }
        return new ObjectTypeResource($this->service->get($id));
    }
    public function store(ObjectTypeRequest $request)
    {
        return new ObjectTypeResource($this->service->store($request->validated()));
    }
    public function update(ObjectTypeRequest $request, $id)
    {
        return new ObjectTypeResource($this->service->update($id, $request->validated()));
    }
    public function destroy(Request $request,$id)
    {
         try {
            // Validate danh sách các IDs cần xóa
            $validated = $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|exists:object_types,id',
            ], [
                'ids.required' => __('validation.required'),
                'ids.array' => __('validation.array_required', ['attribute' => trans('users::object_type.name')]),
                'ids.*.exists' => __('validation.not_found', ['attribute' => trans('users::object_type.name')]),
            ]);
            // Xóa tất cả các menu trong danh sách
            foreach ($validated['ids'] as $id) {
                 $this->service->destroy($id);
            }

            $message = count($validated['ids']) === 1 
                ? __('messages.deleted_one_success', ['attribute' => trans('core::object_type.attributes.code')]) 
                : __('messages.deleted_many_success', [
                    'attribute' => trans('core::object_type.attributes.code'),
                    'count' => count($validated['ids'])
            ]);
            
            return response()->json([
                'success' => true,
                'message' => $message
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('core::object_type.delete_failed'),
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }
}
