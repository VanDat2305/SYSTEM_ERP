<?php

namespace Modules\Order\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Order\Models\OrderDetail;
use Modules\Order\Services\OrderLogService;
use Modules\Order\Services\OrderService;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
        // Basic permissions
        $this->middleware('can:orders.view')->only(['index', 'show']);
        $this->middleware('can:orders.create')->only(['store']);
        $this->middleware('can:orders.update')->only(['update']);
        $this->middleware('can:orders.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $filters = $request->only([
            'order_status',
            'team_id',
            'contract_id',
            'opportunity_id',
            'customer_id',
            'order_code',
            'query',
            'field',
            'sort_by',
            'sort_order',
            'created_at'
        ]);

        $orders = $this->orderService->paginateOrders($perPage, $filters);
        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'order_status' => 'required|string|max:20',
            'team_id' => 'nullable|uuid|exists:teams,id',
            'created_by' => 'nullable|uuid|exists:users,id',
            'order_details' => 'sometimes|array',
            'order_details.*.service_type' => 'required|string',
            'order_details.*.service_package_id' => 'required|uuid|exists:service_packages,id',
            'order_details.*.package_code' => 'required|string|max:20',
            'order_details.*.package_name' => 'required|string|max:100',
            'order_details.*.base_price' => 'required|numeric',
            'order_details.*.quantity' => 'required|integer|min:1',
            'order_details.*.currency' => 'required|string|max:10',
            'order_details.*.start_date' => 'nullable|date',
            'order_details.*.end_date' => 'nullable|date|after:start_date',
            'order_details.*.tax_rate' => 'nullable|numeric',
            'order_details.*.tax_included' => 'sometimes|boolean',
            'order_details.*.features' => 'sometimes|array',
        ]);

        $order = $this->orderService->createOrder(
            $request->except('order_details'),
            $request->input('order_details', [])
        );

        return response()->json($order, 201);
    }

    public function show(string $id)
    {
        $order = $this->orderService->getOrderById($id);

        if (!$order) {
            return response()->json(['message' => __("order::order.not_found")], 404);
        }

        return response()->json($order);
    }

    public function update(Request $request, string $id)
    {
        $orderOld = $this->orderService->getOrderById($id);
        $old_status = $orderOld->order_status;
        // Validate the incoming request, including the new fields you want to update
        $validated = $request->validate([
            'order_status' => 'sometimes|string|max:20',
            'team_id' => 'nullable|uuid|exists:teams,id',
            'contract_id' => 'nullable|uuid|exists:contracts,id',
            'opportunity_id' => 'nullable|uuid|exists:opportunities,id',
            'order_details' => 'sometimes|array',
            'order_details.*.id' => 'nullable|uuid|exists:order_details,id', // Validate order detail ID if updating
            'order_details.*.service_package_id' => 'required|uuid|exists:service_packages,id',
            'order_details.*.package_code' => 'required|string|max:20',
            'order_details.*.package_name' => 'required|string|max:100',
            'order_details.*.base_price' => 'required|numeric',
            'order_details.*.quantity' => 'required|integer|min:1',
            'order_details.*.currency' => 'required|string|max:10',
            'order_details.*.start_date' => 'nullable|date',
            'order_details.*.end_date' => 'nullable|date|after:start_date',
            'order_details.*.tax_rate' => 'nullable|numeric',
            'order_details.*.tax_included' => 'sometimes|boolean',
            'order_details.*.features' => 'sometimes|array',
            'created_by' => 'nullable|uuid|exists:users,id',
        ]);

        // Update the order data excluding 'order_details'
        $orderData = $request->except('order_details');
        $updated = $this->orderService->updateOrder($id, $orderData);
        if (!$updated) {
            return response()->json(['message' => __("order::order.not_found")], 404);
        }

        $order = $this->orderService->getOrderById($id);

        // Process order details
        if ($request->has('order_details')) {
            $existingDetailIds = $this->orderService->getOrderDetails($id)->pluck('id')->toArray();
            $requestDetailIds = [];

            foreach ($request->input('order_details') as $orderDetail) {
                if (isset($orderDetail['id'])) {
                    // Update existing detail
                    $this->orderService->updateOrderDetail($orderDetail['id'], $orderDetail);
                    $requestDetailIds[] = $orderDetail['id'];
                } else {
                    // Add new detail - sửa lại chỗ này
                    $createdDetails = $this->orderService->addOrderDetails($id, [$orderDetail]);
                    if (!empty($createdDetails) && isset($createdDetails[0])) {
                        $requestDetailIds[] = $createdDetails[0]->id;
                    }
                }
            }

            // Delete details not present in the request
            $detailsToDelete = array_diff($existingDetailIds, $requestDetailIds);
            if (!empty($detailsToDelete)) {
                $this->orderService->deleteOrderDetails($detailsToDelete);
            }
        } else {
            // If no order_details in request, delete all existing details
            $this->orderService->deleteAllOrderDetails($id);
        }
        $logService = app(OrderLogService::class);

        $logService->createLog([
            'order_id'   => $order->id,
            'action'     => "Cập nhật đơn",
            'note'       => "Cập nhật đơn hàng {$order->order_code}",
            'file_id'    => null, // Không có file đính kèm trong tạo đơn
            'old_status' => $orderOld->order_status ?? 'null',
            'new_status' => $order->order_status ?? 'null',
        ]);

        return response()->json(['message' => __("order::order.updated_successfully")]);
    }


    public function destroy(string $id)
    {
        $deleted = $this->orderService->deleteOrder($id);

        if (!$deleted) {
            return response()->json(['message' => __("order::order.not_found")], 404);
        }
        $logService = app(OrderLogService::class);

        $logService->createLog([
            'order_id'   => $id,
            'action'     => "Xóa đơn",
            'note'       => "Đã xóa đơn hàng {$id}",
            'file_id'    => null, // Không có file đính kèm trong tạo đơn
            'new_status' => 'deleted',
        ]);

        return response()->json(['message' => __("order::order.deleted_successfully")]);
    }

    public function addOrderDetails(Request $request, string $orderId)
    {
        $validated = $request->validate([
            'order_details' => 'required|array',
            'order_details.*.service_package_id' => 'required|uuid|exists:service_packages,id',
            'order_details.*.package_code' => 'required|string|max:20',
            'order_details.*.package_name' => 'required|string|max:100',
            'order_details.*.base_price' => 'required|numeric',
            'order_details.*.quantity' => 'required|integer|min:1',
            'order_details.*.currency' => 'required|string|max:10',
            'order_details.*.start_date' => 'required|date',
            'order_details.*.end_date' => 'required|date|after:start_date',
            'order_details.*.tax_rate' => 'nullable|numeric',
            'order_details.*.tax_included' => 'sometimes|boolean',
            'order_details.*.features' => 'sometimes|array',
        ]);

        $details = $this->orderService->addOrderDetails($orderId, $request->input('order_details'));

        return response()->json($details, 201);
    }

    public function getOrderDetails(string $orderId)
    {
        $details = $this->orderService->getOrderDetails($orderId);
        return response()->json($details);
    }

    public function updateOrderDetail(Request $request, string $id)
    {
        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'base_price' => 'sometimes|numeric',
            'tax_rate' => 'nullable|numeric',
            'tax_included' => 'sometimes|boolean',
            // 'is_active' => 'sometimes|boolean',
        ]);

        $updated = $this->orderService->updateOrderDetail($id, $validated);

        if (!$updated) {
            return response()->json(['message' => __("order::order_details.not_found")], 404);
        }

        return response()->json(['message' => __("order::order_details.updated")]);
    }

    public function deleteOrderDetail(string $id)
    {
        $deleted = $this->orderService->deleteOrderDetail($id);

        if (!$deleted) {
            return response()->json(['message' => __("order::order_details.not_found")], 404);
        }

        return response()->json(['message' => __("order::order_details.deleted")]);
    }
    public function bulkStatusUpdate(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => 'required|array',
            'status' => 'required|string|max:20',
            'reason' => 'nullable|string|max:255',
        ]);

        $updatedCount = $this->orderService->bulkStatusUpdate($validated['order_ids'], $validated);

        return response()->json(['message' => __("order::order.bulk_status_updated", ['count' => $updatedCount])]);
    }
    public function updateStatus(Request $request, string $orderId)
    {
        $validated = $request->validate([
            'status' => 'required|string|max:20',
            'reason' => 'nullable|string|max:255',
        ]);

        $updated = $this->orderService->updateOrderStatus($orderId, $validated);

        if (!$updated) {
            return response()->json(['message' => __("order::order.not_found")], 404);
        }

        return response()->json(['message' => __("order::order.status_updated")]);
    }
    public function prepareRenew($id)
    {
        $oldDetail = OrderDetail::with(['features'])->findOrFail($id);
        // 2. Clone thông tin (KHÔNG copy id, order_id)
        $newDetail = [
            'service_type'      => $oldDetail->service_type,
            'service_package_id' => $oldDetail->service_package_id,
            'package_code'      => $oldDetail->package_code,
            'package_name'      => $oldDetail->package_name,
            'base_price'        => $oldDetail->base_price,
            'quantity'          => 1,
            'currency'          => $oldDetail->currency,
            'tax_rate'          => $oldDetail->tax_rate,
            'tax_included'      => $oldDetail->tax_included,
            'start_date'        => null,   // hoặc FE tự chọn lại
            'end_date'          => null,
            'is_active'         => false,
            'renewed_from_detail_id' => $oldDetail->id,
        ];

        // 3. Clone features (KHÔNG giữ id)
        $features = [];
        foreach ($oldDetail->features as $feature) {
            $features[] = [
                'feature_key'        => $feature->feature_key,
                'feature_name'       => $feature->feature_name,
                'feature_type'       => $feature->feature_type,
                'unit'               => $feature->unit,
                'limit_value'        => $feature->limit_value,
                'is_optional'        => $feature->is_optional,
                'is_customizable'    => $feature->is_customizable,
                'display_order'      => $feature->display_order,
                'is_active'          => true,
                'original_limit_value' => $feature->limit_value,
                'limit_value_boolean'  => $feature->feature_type === 'boolean' ? (bool)$feature->limit_value : null,
            ];
        }

        $newDetail['features'] = $features;

        return response()->json($newDetail);
    }
}
