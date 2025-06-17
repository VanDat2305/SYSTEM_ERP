<?php

namespace Modules\Order\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Order\Interfaces\OrderRepositoryInterface;
use Modules\Order\Interfaces\OrderDetailRepositoryInterface;
use Modules\Order\Interfaces\OrderPackageFeatureRepositoryInterface;
use Illuminate\Support\Str;

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
        return DB::transaction(function () use ($orderData, $orderDetailsData) {
            // Generate order code if not provided
            if (!isset($orderData['order_code'])) {
                $orderData['order_code'] = $this->generateOrderCode();
            }

            $order = $this->orderRepository->create($orderData);

            if (!empty($orderDetailsData)) {
                $this->addOrderDetails($order->id, $orderDetailsData);
            }

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
    public function changeOrderStatus(string $orderId, string $status)
    {
        return $this->orderRepository->update($orderId, ['order_status' => $status]);
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
}
