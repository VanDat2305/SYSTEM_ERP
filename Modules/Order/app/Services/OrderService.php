<?php

namespace Modules\Order\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Order\Interfaces\OrderRepositoryInterface;
use Modules\Order\Interfaces\OrderDetailRepositoryInterface;
use Modules\Order\Interfaces\OrderPackageFeatureRepositoryInterface;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;
use Modules\Order\Models\Order;

class OrderService
{
    protected $orderRepository;
    protected $orderDetailRepository;
    protected $orderPackageFeatureRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderDetailRepositoryInterface $orderDetailRepository,
        OrderPackageFeatureRepositoryInterface $orderPackageFeatureRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderDetailRepository = $orderDetailRepository;
        $this->orderPackageFeatureRepository = $orderPackageFeatureRepository;
    }

    // Order CRUD operations
    public function getAllOrders()
    {
        return $this->orderRepository->all();
    }
    public function paginateOrders(int $perPage = 10, array $filters = [])
    {
        return $this->orderRepository->paginate($perPage, $filters);
    }

    public function getOrderById(string $id)
    {
        try {
            return $this->orderRepository->find($id);
        } catch (\Exception $e) {
            Log::error('Error getting order: ' . $e->getMessage());
            return null;
        }
    }

    public function createOrder(array $orderData, array $orderDetailsData = [])
    {
        $customer = Customer::find($orderData['customer_id']);
        if (!$customer) {
            throw new \Exception('Customer not found');
        }
        if ($customer->status == 'inactive') {
            throw new \Exception(__('customer::messages.customer_not_inactive'));
        }
        if (
            Order::where('customer_id', $orderData['customer_id'])
            ->whereIn('order_status', ['draft', 'pending', 'approved', 'processing'])
            ->exists()
        ) {
            throw new \Exception(__('customer::messages.customer_has_active_order'));
        }

        return DB::transaction(function () use ($orderData, $orderDetailsData, $customer) {
            // Generate order code if not provided
            if (!isset($orderData['order_code'])) {
                $orderData['order_code'] = $this->generateOrderCode();
            }
            if (!isset($orderData['created_by'])) {
                $orderData['created_by'] = auth()->id() ?? null;
            }

            $order = $this->orderRepository->create($orderData);

            if (!empty($orderDetailsData)) {
                $this->addOrderDetails($order->id, $orderDetailsData);
            }


            if ($customer && $customer->status == 'new' || $customer->status == 'unqualified') {
                $customer->status = 'in_progress';
                $customer->save();
            }
            $logService = app(OrderLogService::class);

            $logService->createLog([
                'order_id'   => $order->id,
                'action'     => "Tạo đơn",
                'note'       => "Đơn hàng được tạo bởi " . ($orderData['created_by'] ? auth()->user()->name : 'Hệ thống'),
                'file_id'    => null, // Không có file đính kèm trong tạo đơn
                // old_status, new_status có thể truyền từ client nếu muốn lưu
                'old_status' => null,
                'new_status' => $order->order_status ?? 'draft',
            ]);

            return $order;
        });
    }

    public function updateOrder(string $id, array $orderData)
    {
        return $this->orderRepository->update($id, $orderData);
    }

    public function deleteOrder(string $id)
    {
        // First delete all order details and features
        $this->orderDetailRepository->deleteByOrder($id);

        // Then delete the order
        return $this->orderRepository->delete($id);
    }

    // Order Details operations
    public function addOrderDetails(string $orderId, array $orderDetailsData)
    {
        $totalAmount = 0;
        $currency = null;
        $createdDetails = [];

        foreach ($orderDetailsData as $detailData) {
            // Set order_id
            $detailData['order_id'] = $orderId;

            // Calculate total price if not provided
            if (!isset($detailData['total_price'])) {
                $detailData['total_price'] = $detailData['base_price'] * $detailData['quantity'];
            }

            // Calculate tax if applicable
            if (isset($detailData['tax_rate']) && $detailData['tax_rate'] > 0) {
                $detailData['tax_amount'] = $detailData['total_price'] * ($detailData['tax_rate'] / 100);
                $detailData['total_with_tax'] = $detailData['tax_included']
                    ? $detailData['total_price']
                    : $detailData['total_price'] + $detailData['tax_amount'];
            } else {
                $detailData['tax_amount'] = 0;
                $detailData['total_with_tax'] = $detailData['total_price'];
            }
            $detailData['is_active'] = false;
            // Create the order detail
            $orderDetail = $this->orderDetailRepository->create($detailData);
            $createdDetails[] = $orderDetail;

            // Add to total amount
            $totalAmount += $detailData['total_with_tax'];
            $currency = $detailData['currency'] ?? $currency;

            // If package features are provided, create them
            if (isset($detailData['features']) && is_array($detailData['features'])) {
                $this->addPackageFeatures($orderDetail->id, $detailData['features']);
            }
        }

        // Update order total amount and currency
        $this->orderRepository->update($orderId, [
            'total_amount' => $totalAmount,
            'currency' => $currency
        ]);

        return $createdDetails;
    }

    public function getOrderDetails(string $orderId)
    {
        return $this->orderDetailRepository->getDetailsByOrder($orderId);
    }

    public function updateOrderDetail(string $id, array $detailData)
    {
        unset($detailData['servicePackageOptions']);
        // First, update the order detail itself
        $updated = $this->orderDetailRepository->update($id, $detailData);

        if ($updated) {
            // Recalculate the order total after updating the detail
            $this->recalculateOrderTotal($detailData['order_id']);

            // Check if features are provided and update them
            if (isset($detailData['features']) && is_array($detailData['features'])) {
                // First, delete the existing features
                $this->orderPackageFeatureRepository->deleteByOrderDetail($id);

                // Then, add the new features
                $this->addPackageFeatures($id, $detailData['features']);
            }
        }

        return $updated;
    }

    public function deleteOrderDetails(array $ids)
    {
        $deletedCount = 0;

        foreach ($ids as $id) {
            if ($this->deleteOrderDetail($id)) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
    public function deleteAllOrderDetails(string $orderId)
    {
        // First, get all details for the order
        $details = $this->orderDetailRepository->getDetailsByOrder($orderId);

        // Delete each detail and recalculate order total
        foreach ($details as $detail) {
            $this->deleteOrderDetail($detail->id);
        }

        return true;
    }


    public function deleteOrderDetail(string $id)
    {
        $detail = $this->orderDetailRepository->find($id);
        if (!$detail) {
            return false;
        }

        $orderId = $detail->order_id;
        $deleted = $this->orderDetailRepository->delete($id);

        if ($deleted) {
            $this->recalculateOrderTotal($orderId);
        }

        return $deleted;
    }

    // Package Features operations
    public function addPackageFeatures(string $orderDetailId, array $featuresData)
    {
        $createdFeatures = [];
        foreach ($featuresData as $featureData) {
            $featureData['id'] = Str::uuid()->toString();
            $featureData['order_detail_id'] = $orderDetailId;
            $createdFeatures[] = $this->orderPackageFeatureRepository->create($featureData);
        }

        return $createdFeatures;
    }

    public function getPackageFeatures(string $orderDetailId)
    {
        return $this->orderPackageFeatureRepository->getFeaturesByOrderDetail($orderDetailId);
    }

    public function updatePackageFeature(string $id, array $featureData)
    {
        return $this->orderPackageFeatureRepository->update($id, $featureData);
    }

    public function deletePackageFeature(string $id)
    {
        return $this->orderPackageFeatureRepository->delete($id);
    }

    // Helper methods
    protected function recalculateOrderTotal(string $orderId)
    {
        $orderDetails = $this->orderDetailRepository->getDetailsByOrder($orderId);
        $totalAmount = 0;
        $currency = null;

        foreach ($orderDetails as $detail) {
            $totalAmount += $detail->total_with_tax ?? $detail->total_price;
            $currency = $detail->currency ?? $currency;
        }

        $this->orderRepository->update($orderId, [
            'total_amount' => $totalAmount,
            'currency' => $currency
        ]);
    }

    // Additional business logic methods
    public function changeOrderStatus(string $orderId, array $data)
    {
        DB::beginTransaction();
        try {
            $order = $this->getOrderById($orderId);
            $change = $this->orderRepository->update($orderId, $data);
            
            // map text status to order_status
            $statusMap = [
                'draft' => 'Nháp',
                'pending' => 'Chờ xử lý',
                'approved' => 'Đã phê duyệt',
                'processing' => 'Đang xử lý',
                'completed' => 'Đã hoàn tất',
                'cancelled' => 'Đã hủy'
            ];
            if (isset($data['order_status']) && array_key_exists($data['order_status'], $statusMap)) {
                $statusNew = $statusMap[$data['order_status']];
                $statusOld = $statusMap[$order->order_status] ?? '';
            } else {
                $statusNew = 'draft';
                $statusOld = $statusMap[$order->order_status] ?? '';
            }
            $note = "Trạng thái đơn hàng đã được cập nhật từ '{$statusOld}' sang '{$statusNew}'";
            if (isset($data['reason_cancel'])) {
                $note .= ". Lý do hủy: " . $data['reason_cancel'];
            }
            $logService = app(OrderLogService::class);
            $logService->createLog([
                'order_id'   => $orderId,
                'action'     => "Cập nhật trạng thái",
                'note'       => $note,
                'file_id'    => null, // Không có file đính kèm trong tạo đơn
                'old_status' => $order->order_status ?? 'draft',
                'new_status' => $data['order_status'],
            ]);
            $this->updateStatusCustomer($orderId, $data['order_status'] ?? 'draft');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error changing order status: ' . $e->getMessage());
            throw $e;
        }

        return $change;
    }

    public function getOrdersByCustomer(string $customerId)
    {
        return $this->orderRepository->getOrdersByCustomer($customerId);
    }

    public function getOrdersByTeam(string $teamId)
    {
        return $this->orderRepository->getOrdersByTeam($teamId);
    }
    protected function generateOrderCode(): string
    {
        do {
            $prefix = 'ORD' . now()->format('ymd');
            $random = strtoupper(Str::random(4));
            $code = $prefix . $random;

            // Kiểm tra trùng lặp
            $exists = $this->orderRepository->findByOrderCode($code);
        } while ($exists);

        return $code;
    }
    /**
     * Update status for multiple orders in bulk
     * 
     * @param array $orderIds Array of order IDs to update
     * @param array $data Update data including new status
     * @return int Number of successfully updated orders
     * @throws InvalidArgumentException
     */
    public function bulkStatusUpdate(array $orderIds, array $data): int
    {
        $this->validateStatus($data['status'] ?? '');
        $this->validateOrderIds($orderIds);

        $updatedCount = 0;

        foreach ($orderIds as $orderId) {
            // Kiểm tra xem đơn hàng có tồn tại không
            $order = $this->getOrderById($orderId);
            if ($order) {
                $this->updateOrderStatus($orderId, $data);
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    /**
     * Update status for a single order
     * 
     * @param string $orderId Order ID to update
     * @param array $data Update data including new status
     * @return mixed Result of the status change operation
     * @throws InvalidArgumentException
     */
    public function updateOrderStatus(string $orderId, array $data)
    {
        $this->validateStatus($data['status'] ?? '');

        $updateData = $this->prepareUpdateData($data);
        return $this->changeOrderStatus($orderId, $updateData);
    }

    /**
     * Validate order status
     * 
     * @param string $status Status to validate
     * @throws InvalidArgumentException
     */
    private function validateStatus(string $status): void
    {
        $validStatuses = ['draft', 'pending', 'approved', 'processing', 'completed', 'cancelled'];

        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException('Trạng thái đơn hàng không hợp lệ.');
        }
    }

    /**
     * Validate order IDs array
     * 
     * @param array $orderIds Order IDs to validate
     * @throws InvalidArgumentException
     */
    private function validateOrderIds(array $orderIds): void
    {
        if (empty($orderIds)) {
            throw new \InvalidArgumentException('Danh sách ID đơn hàng không hợp lệ.');
        }
    }

    /**
     * Prepare update data with proper reason_cancel field
     * 
     * @param array $data Original update data
     * @return array Prepared update data
     */
    private function prepareUpdateData(array $data): array
    {
        if ($data['status'] === 'cancelled' && isset($data['reason'])) {
            $updateData['reason_cancel'] = $data['reason'];
        } else {
            $updateData['reason_cancel'] = null;
        }
        $updateData['order_status'] = $data['status'] ?? 'draft';
        return $updateData;
    }
    public function updateStatusCustomer(string $orderId, string $status): bool
    {
        $order = $this->getOrderById($orderId);
        if (!$order) {
            throw new \Exception(__('order::messages.order_not_found'));
        }

        // Update customer status based on order status
        $customer = Customer::find($order->customer_id);
        if ($customer) {
            switch ($status) {
                case 'completed':
                    $customer->status = 'converted';
                    break;
                default:
                    break;
            }
            return $customer->save();
        }

        return false;
    }
}
