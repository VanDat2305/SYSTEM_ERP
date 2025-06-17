<?php

namespace Modules\Customer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customer\Http\Requests\CreateCustomerRequest;
use Modules\Customer\Http\Requests\UpdateCustomerRequest;
use Modules\Customer\Services\CustomerService;

class CustomerController extends Controller
{
    protected CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only([
                'customer_type',
                'customer_code',
                'team_id',
                'assigned_to',
                'search',
                'status',
                'query',
                'field',
                'sort_by',
                'sort_order',
                'created_at'
            ]);
            
            $customers = $this->customerService->getCustomersPaginated($perPage, $filters);
            
            // return response()->json([
            //     'success' => true,
            //     'message' => __('customer::messages.customers_retrieved'),
            //     'data' => $customers
            // ]);
            return response()->json($customers, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_retrieving_customers'),
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $customer = $this->customerService->getCustomerById($id);
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => __('customer::messages.customer_not_found')
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => __('customer::messages.customer_retrieved'),
                'data' => $customer
            ]);
            // return response()->json($customer, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_retrieving_customer'),
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function store(CreateCustomerRequest $request): JsonResponse
    {
        try {
            $customer = $this->customerService->createCustomer($request->validated());
            
            // return response()->json([
            //     'success' => true,
            //     'message' => __('customer::messages.customer_created'),
            //     'data' => $customer
            // ], 201);
            return response()->json($customer, 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_creating_customer'),
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function update(UpdateCustomerRequest $request, string $id): JsonResponse
    {
        try {
            $customer = $this->customerService->updateCustomer($id, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => __('customer::messages.customer_updated'),
                'data' => $customer
            ]);
            // return response()->json($customer, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_updating_customer'),
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->customerService->deleteCustomer($id);
            
            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => __('customer::messages.customer_not_found')
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => __('customer::messages.customer_deleted')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_deleting_customer'),
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $customer = $this->customerService->toggleCustomerStatus($id);
            
            return response()->json([
                'success' => true,
                'message' => __('customer::messages.customer_status_updated'),
                'data' => $customer
            ]);
            // return response()->json($customer, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_updating_status'),
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $keyword = $request->get('q', '');
            
            if (empty($keyword)) {
                return response()->json([
                    'success' => false,
                    'message' => __('customer::messages.search_keyword_required')
                ], 400);
            }
            
            $customers = $this->customerService->searchCustomers($keyword);
            
            // return response()->json([
            //     'success' => true,
            //     'message' => __('customer::messages.search_completed'),
            //     'data' => $customers
            // ]);
            return response()->json($customers, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_searching'),
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function getByType(string $type): JsonResponse
    {
        try {
            $customers = $this->customerService->getCustomersByType($type);
            
            // return response()->json([
            //     'success' => true,
            //     'message' => __('customer::messages.customers_by_type_retrieved'),
            //     'data' => $customers
            // ]);
            return response()->json($customers, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_retrieving_by_type'),
                'errors' => $e->getMessage()
            ], 500);
        }
    }
    public function getByCustomerCode(string $customerCode): JsonResponse
    {
        try {
            $customer = $this->customerService->getCustomerByCode($customerCode);
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => __('customer::messages.customer_not_found')
                ], 404);
            }
    
            return response()->json($customer, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_retrieving_customer'),
                'errors' => $e->getMessage()
            ], 500);
        }
    }
}
