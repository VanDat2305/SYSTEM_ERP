<?php

namespace Modules\Customer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customer\Http\Requests\CreateCustomerRequest;
use Modules\Customer\Http\Requests\UpdateCustomerRequest;
use Modules\Customer\Services\CustomerLogService;
use Modules\Customer\Services\CustomerService;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    protected CustomerService $customerService;
    protected array $statusMap;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
        $this->statusMap = [
            'new' => 'Đăng ký mới',
            'in_progress' => 'Đang chăm sóc',
            'converted' => 'Đã chuyển đổi',
            'inactive' => 'Ngưng giao dịch',
            'unqualified' => 'Không tiềm năng',
        ];
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


    // Đánh dấu "Không tiềm năng" - Single
    public function markUnqualified(Request $request, $id)
    {
        $customer = $this->customerService->getCustomerById($id);

        // Điều kiện: chỉ cho phép nếu chưa từng có đơn hàng
        if ($customer->orders()->exists()) {
            return response()->json(['message' => 'Khách hàng đã phát sinh đơn, không thể chuyển về Không tiềm năng!'], 400);
        }
        if ($customer->status === 'unqualified') {
            return response()->json(['message' => 'Khách hàng đã ở trạng thái Không tiềm năng!'], 400);
        }

        $oldStatus = $customer->status;
        $customer->status = 'unqualified';
        $customer->save();

        // Ghi nhật ký
        app(CustomerLogService::class)->createLog([
            'object_type' => 'customer',
            'object_id'   => $customer->id,
            'action'      => 'Đánh dấu Không tiềm năng',
            'note'        => 'Chuyển sang Không tiềm năng. Lý do: ' . $customer->unqualified_reason,
            'old_value'   => json_encode(['status' => $oldStatus]),
            'new_value'   => json_encode(['status' => 'unqualified'])
        ]);

        return response()->json(['message' => 'Đã chuyển sang Không tiềm năng thành công!']);
    }

    // Đánh dấu "Không tiềm năng" - Bulk
    public function markUnqualifiedBulk(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:customers,id'
        ]);

        $ids = $request->input('ids');

        try {
            DB::beginTransaction();

            $customers = $this->customerService->getCustomersByIds($ids);
            $processedCustomers = [];

            foreach ($customers as $customer) {
                // Kiểm tra điều kiện cho từng khách hàng
                if ($customer->orders()->exists()) {
                    throw new \Exception("Khách hàng {$customer->customer_code} đã phát sinh đơn, không thể chuyển về Không tiềm năng!");
                }
                if ($customer->status === 'unqualified') {
                    throw new \Exception("Khách hàng {$customer->customer_code}  đã ở trạng thái Không tiềm năng!");
                }

                $oldStatus = $customer->status;
                $customer->status = 'unqualified';
                $customer->save();

                // Ghi nhật ký
                app(CustomerLogService::class)->createLog([
                    'object_type' => 'customer',
                    'object_id'   => $customer->id,
                    'action'      => 'Cập nhật',
                    'note'        => 'Chuyển sang Không tiềm năng. Lý do: ' . $customer->unqualified_reason,
                    'old_value'   => json_encode(['status' => $oldStatus]),
                    'new_value'   => json_encode(['status' => 'unqualified'])
                ]);

                $processedCustomers[] = $customer->id;
            }

            DB::commit();

            return response()->json([
                'message' => 'Đã chuyển ' . count($processedCustomers) . ' khách hàng sang Không tiềm năng thành công!',
                'processed_ids' => $processedCustomers
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // Đánh dấu "Ngưng giao dịch" - Single
    public function markInactive(Request $request, $id)
    {
        $customer = $this->customerService->getCustomerById($id);

        // Điều kiện: chỉ cho phép nếu khách đã từng có đơn hàng
        if (!$customer->orders()->exists()) {
            return response()->json(['message' => 'Chỉ khách đã từng có đơn mới được chuyển sang Ngưng giao dịch!'], 400);
        }
        if ($customer->status === 'inactive') {
            return response()->json(['message' => 'Khách hàng đã ở trạng thái Ngưng giao dịch!'], 400);
        }

        $oldStatus = $customer->status;
        $customer->status = 'inactive';
        $customer->save();

        // Ghi nhật ký
        app(CustomerLogService::class)->createLog([
            'object_type' => 'customer',
            'object_id'   => $customer->id,
            'action'      => 'Đánh dấu Ngưng giao dịch',
            'note'        => 'Chuyển sang Ngưng giao dịch. Lý do: ' . $customer->inactive_reason,
            'old_value'   => json_encode(['status' => $oldStatus]),
            'new_value'   => json_encode(['status' => 'inactive']),
        ]);

        return response()->json(['message' => 'Đã chuyển sang Ngưng giao dịch thành công!']);
    }

    // Đánh dấu "Ngưng giao dịch" - Bulk
    public function markInactiveBulk(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:customers,id'
        ]);

        $ids = $request->input('ids');

        try {
            DB::beginTransaction();

            $customers = $this->customerService->getCustomersByIds($ids);
            $processedCustomers = [];

            foreach ($customers as $customer) {
                // Kiểm tra điều kiện cho từng khách hàng
                if (!$customer->orders()->exists()) {
                    throw new \Exception("Khách hàng {$customer->customer_code} chưa từng có đơn hàng, không thể chuyển sang Ngưng giao dịch!");
                }
                if ($customer->status === 'inactive') {
                    throw new \Exception("Khách hàng {$customer->customer_code} đã ở trạng thái Ngưng giao dịch!");
                }

                $oldStatus = $customer->status;
                $customer->status = 'inactive';
                $customer->save();

                // Ghi nhật ký
                app(CustomerLogService::class)->createLog([
                    'object_type' => 'customer',
                    'object_id'   => $customer->id,
                    'action'      => 'Cập nhật',
                    'note'        => 'Chuyển sang Ngưng giao dịch. Lý do: ' . $customer->inactive_reason,
                    'old_value'   => json_encode(['status' => $oldStatus]),
                    'new_value'   => json_encode(['status' => 'inactive']),
                ]);

                $processedCustomers[] = $customer->id;
            }

            DB::commit();

            return response()->json([
                'message' => 'Đã chuyển ' . count($processedCustomers) . ' khách hàng sang Ngưng giao dịch thành công!',
                'processed_ids' => $processedCustomers
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // Khôi phục khách hàng - Single
    public function restore(Request $request, $id)
    {
        $customer = $this->customerService->getCustomerById($id);
        if (!$customer) {
            return response()->json(['message' => 'Khách hàng không tồn tại!'], 404);
        }

        // Điều kiện: chỉ khôi phục được nếu đang là inactive hoặc unqualified
        if (!in_array($customer->status, ['inactive', 'unqualified'])) {
            return response()->json(['message' => 'Chỉ khôi phục được từ trạng thái Không tiềm năng hoặc Ngưng giao dịch!'], 400);
        }

        $oldStatus = $customer->status;
        // Nếu từng có đơn hàng → chuyển về 'converted' (hoặc 'active'), ngược lại về 'in_progress' (đang chăm sóc)
        if ($customer->orders()->exists()) {
            $customer->status = 'converted';
        } else {
            $customer->status = 'in_progress';
        }
        $customer->save();

        // Ghi nhật ký
        app(CustomerLogService::class)->createLog([
            'object_type' => 'customer',
            'object_id'   => $customer->id,
            'action'      => 'Khôi phục',
            'note'        => 'Khôi phục khách hàng từ trạng thái ' . $this->statusMap[$oldStatus] . ' về ' .
                $this->statusMap[$customer->status] . '.',
            'old_value'   => json_encode(['status' => $oldStatus]),
            'new_value'   => json_encode(['status' => $customer->status]),
        ]);

        return response()->json(['message' => 'Khôi phục khách hàng thành công!']);
    }

    // Khôi phục khách hàng - Bulk
    public function restoreBulk(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:customers,id'
        ]);

        $ids = $request->input('ids');

        try {
            DB::beginTransaction();

            $customers = $this->customerService->getCustomersByIds($ids);
            $processedCustomers = [];

            foreach ($customers as $customer) {
                // Kiểm tra điều kiện cho từng khách hàng
                if (!in_array($customer->status, ['inactive', 'unqualified'])) {
                    throw new \Exception("Khách hàng{$customer->customer_code} không ở trạng thái Không tiềm năng hoặc Ngưng giao dịch, không thể khôi phục!");
                }

                $oldStatus = $customer->status;
                // Nếu từng có đơn hàng → chuyển về 'converted', ngược lại về 'in_progress'
                if ($customer->orders()->exists()) {
                    $customer->status = 'in_progress';
                } else {
                    $customer->status = 'new';
                }
                $customer->save();

                // Ghi nhật ký
                app(CustomerLogService::class)->createLog([
                    'object_type' => 'customer',
                    'object_id'   => $customer->id,
                    'action'      => 'Khôi phục',
                    'note'        => 'Khôi phục khách hàng từ trạng thái ' . $this->statusMap[$oldStatus] . ' về ' .
                        $this->statusMap[$customer->status] . '.',
                    'old_value'   => json_encode(['status' => $oldStatus]),
                    'new_value'   => json_encode(['status' => $customer->status]),
                ]);

                $processedCustomers[] = $customer->id;
            }

            DB::commit();

            return response()->json([
                'message' => 'Đã khôi phục ' . count($processedCustomers) . ' khách hàng thành công!',
                'processed_ids' => $processedCustomers
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
