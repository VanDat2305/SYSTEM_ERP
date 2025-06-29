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
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderDetail;

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
    public function customerPackagesList(Request $request, $customerId)
    {
        $pageSize = $request->input('per_page', 10);

        $orderDetails = OrderDetail::with('features')
            ->whereHas('order', function ($q) use ($customerId) {
                $q->where('customer_id', $customerId)
                    ->where('order_status', '!=', 'draft');
            })
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->orderByDesc('created_at')
            ->paginate($pageSize);

        $renewedDetailIds = OrderDetail::whereNotNull('renewed_from_detail_id')
            ->pluck('renewed_from_detail_id')
            ->toArray();

        $data = $orderDetails->map(function ($detail) use ($renewedDetailIds) {
            return $this->formatPackageDetail($detail, $renewedDetailIds);
        });

        return response()->json($data);
    }

    public function customerPackagesSummary(Request $request, $customerId)
    {
        // Tổng số đơn hàng đã tạo (đơn không draft)
        $totalOrders = Order::where('customer_id', $customerId)
            ->whereNotIn('order_status', ['draft'])
            ->count();

        // Lấy tất cả order_details đã mua
        $orderDetails = OrderDetail::with('features')
            ->whereHas('order', function ($q) use ($customerId) {
                $q->where('customer_id', $customerId)
                    ->where('payment_status', 'paid')
                    ->whereNotIn('order_status', ['draft', 'cancelled']);
            })
            ->get();

        // Thống kê số gói sắp hết hạn và đã hết hạn
        $renewedDetailIds = OrderDetail::whereNotNull('renewed_from_detail_id')
            ->pluck('renewed_from_detail_id')
            ->toArray();

        $expiringSoon = 0;
        $expired = 0;

        foreach ($orderDetails as $detail) {
            $status = $this->determinePackageStatus($detail, $renewedDetailIds);

            if ($status === 'expired') {
                $expired++;
            } elseif ($status === 'warning') {
                $expiringSoon++;
            }
        }

        // Phân loại số lượng gói theo loại dịch vụ
        $serviceTypeOptions = app(\Modules\Core\Services\ObjectItemService::class)->getActiveObjectsByTypeCode('service_type');
        $packagesByType = [];
        foreach ($serviceTypeOptions as $type) {
            $count = $orderDetails->where('service_type', $type['code'])->count();
            $packagesByType[] = [
                'type'  => $type['code'],
                'label' => $type['name'],
                'count' => $count
            ];
        }

        // Đơn gần nhất
        $lastOrder = Order::where('customer_id', $customerId)
            ->whereNotIn('order_status', ['draft', 'cancelled'])
            ->orderByDesc('created_at')->first();

        return response()->json([
            'totalOrders'     => $totalOrders,
            'totalPackages'   => $orderDetails->count(),
            'totalAmount'     => number_format(Order::where('customer_id', $customerId)
                ->whereNotIn('order_status', ['draft', 'cancelled'])
                ->where('payment_status', 'paid')
                ->sum('total_amount'), 0, '', '.'),
            'lastOrderDate'   => $lastOrder ? $lastOrder->created_at->format('d/m/Y') : null,
            'lastOrderCode'   => $lastOrder ? $lastOrder->order_code : null,
            'packagesByType'  => $packagesByType,
            'expiringSoon'    => $expiringSoon,
            'expired'         => $expired,
        ]);
    }

    // Hàm helper để xác định trạng thái package
    protected function determinePackageStatus($detail, $renewedDetailIds)
    {
        $now = now();
        $feature = $detail->features->first();
        $totalQuota = $feature ? ($feature->limit_value * $detail->quantity) : null;
        $used = $feature ? $feature->used_count : null;
        $remain = $feature ? ($totalQuota - $used) : null;
        $end = $detail->end_date ? \Carbon\Carbon::parse($detail->end_date) : null;
        $start = $detail->start_date ? \Carbon\Carbon::parse($detail->start_date) : null;

        if (in_array($detail->id, $renewedDetailIds)) {
            return 'renewed';
        }

        // Đã hết hạn
        if (($remain !== null && $remain <= 0) || ($end && $end->isPast())) {
            return 'expired';
        }

        // Chỉ xét sắp hết hạn nếu đã qua ngày bắt đầu
        if ($start && $start->isPast()) {
            // Sắp hết quota (dưới 10%)
            $isLowQuota = $totalQuota && $remain !== null && $totalQuota > 0 && ($remain / $totalQuota) < 0.1;

            // Sắp hết thời gian (dưới 60 ngày)
            $isNearExpiry = $end && $end->isFuture() && $now->diffInDays($end) < 60;
            if ($isLowQuota || $isNearExpiry) {
                return 'warning';
            }
        }

        return 'active';
    }

    // Hàm helper để format dữ liệu package detail
    protected function formatPackageDetail($detail, $renewedDetailIds)
    {
        $status = $this->determinePackageStatus($detail, $renewedDetailIds);
        $feature = $detail->features->first();
        $totalQuota = $feature ? ($feature->limit_value * $detail->quantity) : null;
        $used = $feature ? $feature->used_count : null;
        $remain = $feature ? ($totalQuota - $used) : null;

        return [
            'id'           => $detail->id,
            'package_code' => $detail->package_code,
            'package_name' => $detail->package_name,
            'service_type' => $detail->service_type,
            'total_quota'  => $totalQuota !== null ? number_format($totalQuota, 0, '', '.') : null,
            'used'         => $used !== null ? number_format($used, 0, '', '.') : null,
            'remain'       => $remain !== null ? number_format($remain, 0, '', '.') : null,
            'start_date'   => $detail->start_date,
            'end_date'     => $detail->end_date,
            'status'       => $status,
        ];
    }
}
