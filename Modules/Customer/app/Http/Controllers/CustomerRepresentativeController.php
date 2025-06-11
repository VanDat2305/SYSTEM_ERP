<?php

namespace Modules\Customer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customer\Services\CustomerRepresentativeService;

class CustomerRepresentativeController extends Controller
{
    protected CustomerRepresentativeService $representativeService;

    public function __construct(CustomerRepresentativeService $representativeService)
    {
        $this->representativeService = $representativeService;
    }

    public function index(string $customerId): JsonResponse
    {
        try {
            $representatives = $this->representativeService->getCustomerRepresentatives($customerId);
            
            return response()->json([
                'success' => true,
                'message' => __('customer::messages.representatives_retrieved'),
                'data' => $representatives
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_retrieving_representatives'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'full_name' => 'required|string|max:255',
            'position' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'identity_type' => 'nullable|in:CCCD,CMND,PP',
            'identity_number' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:100'
        ]);

        try {
            $representative = $this->representativeService->createRepresentative($request->all());
            
            return response()->json([
                'success' => true,
                'message' => __('customer::messages.representative_created'),
                'data' => $representative
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_creating_representative'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'full_name' => 'string|max:255',
            'position' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'identity_type' => 'nullable|in:CCCD,CMND,PP',
            'identity_number' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:100'
        ]);

        try {
            $representative = $this->representativeService->updateRepresentative($id, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => __('customer::messages.representative_updated'),
                'data' => $representative
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_updating_representative'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->representativeService->deleteRepresentative($id);
            
            return response()->json([
                'success' => true,
                'message' => __('customer::messages.representative_deleted')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_deleting_representative'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}