<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Http\Requests\ObjectItemRequest;
use Modules\Core\Services\ObjectItemService;
use Modules\Core\Http\Resources\ObjectItemResource;

class ObjectItemController extends Controller
{
    protected $service;

    public function __construct(ObjectItemService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        // $status = $request->get('status', 'active');
        // return ObjectItemResource::collection(
        //     $this->service->filterByStatus($status)
        // );
        return ObjectItemResource::collection($this->service->list($request));
    }
    public function show(Request $request, $id)
    {

        return new ObjectItemResource($this->service->getById($id, $request));
    }
    public function store(ObjectItemRequest $request)
    {
        return new ObjectItemResource($this->service->store($request->validated()));
    }
    public function update(ObjectItemRequest $request, $id)
    {
        return new ObjectItemResource($this->service->update($id, $request->validated()));
    }
    public function destroy(Request $request,$id)
    {
         try {
            // Validate danh sách các IDs cần xóa
            $validated = $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|exists:objects,id',
            ], [
                'ids.required' => __('validation.required'),
                'ids.array' => __('validation.array_required', ['attribute' => trans('core::object_item.name')]),
                'ids.*.exists' => __('validation.not_found', ['attribute' => trans('core::object_item.name')]),
            ]);
            // Xóa tất cả các menu trong danh sách
            foreach ($validated['ids'] as $id) {
                 $this->service->destroy($id);
            }

            $message = count($validated['ids']) === 1 
                ? __('messages.deleted_one_success', ['attribute' => trans('core::object_item.attributes.code')]) 
                : __('messages.deleted_many_success', [
                    'attribute' => trans('core::object_item.attributes.code'),
                    'count' => count($validated['ids'])
            ]);
            
            return response()->json([
                'success' => true,
                'message' => $message
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('core::object_item.delete_failed'),
                'errors' => [$e->getMessage()],
            ], 500);    
        }
        
    }
}
