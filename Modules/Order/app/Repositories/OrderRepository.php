<?php

namespace Modules\Order\Repositories;

use Modules\Order\Interfaces\OrderRepositoryInterface;
use Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(protected Order $model)
    {
        // Constructor có thể để trống hoặc dùng để inject các phụ thuộc khác nếu cần
    }
    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find(string $id): ?Order
    {
        return $this->model->find($id)->load([
            // Load các quan hệ liên quan nếu cần
            'customer', 'details.features', 'team'
        ]);
    }

    public function create(array $data): Order
    {
        return $this->model->create($data);
    }
    public function paginate(int $perPage = 15, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->model->with([
            // Load quan hệ liên quan nếu cần
            'customer.contacts', 'details.features', 'team', 'creator:id,name',
        ]);

        // Tìm kiếm theo order_code, customer_id, hoặc lọc theo trường cụ thể
        if (!empty($filters['order_code'])) {
            $query->where('order_code', 'like', "%{$filters['order_code']}%");
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['team_id'])) {
            // team_id có thể là array hoặc string
            $teamIds = is_array($filters['team_id']) ? $filters['team_id'] : [$filters['team_id']];
            $query->whereIn('team_id', $teamIds);
        }

        if (!empty($filters['order_status']) && strtolower($filters['order_status']) !== 'all') {
            $query->where('order_status', $filters['order_status']);
        }

        if (!empty($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        if (!empty($filters['opportunity_id'])) {
            $query->where('opportunity_id', $filters['opportunity_id']);
        }

        if (!empty($filters['contract_id'])) {
            $query->where('contract_id', $filters['contract_id']);
        }

        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (!empty($filters['query']) && !empty($filters['field'])) {
            $allowedFields = ['order_code', 'customer.customer_code', 'customer.full_name']; // Các field cho phép
            
            if (!in_array($filters['field'], $allowedFields)) {
                throw new \InvalidArgumentException("Trường {$filters['field']} không hợp lệ cho tìm kiếm.");
            }

            if (str_contains($filters['field'], '.')) {
                [$relation, $relationField] = explode('.', $filters['field'], 2);
                
                $query->whereHas($relation, function($q) use ($relationField, $filters) {
                    $q->where($relationField, 'like', "%{$filters['query']}%");
                });
            } else {
                $query->where($filters['field'], 'like', "%{$filters['query']}%");
            }
        }

        // Lọc theo tổng tiền (total_amount) khoảng giá
        if (!empty($filters['total_amount'])) {
            $amount = $filters['total_amount'];
            if (isset($amount['from']) && is_numeric($amount['from'])) {
                $query->where('total_amount', '>=', $amount['from']);
            }
            if (isset($amount['to']) && is_numeric($amount['to'])) {
                $query->where('total_amount', '<=', $amount['to']);
            }
        }

        // Lọc theo ngày đơn hàng
        if (!empty($filters['created_at'])) {
            $orderDate = $filters['created_at'];
            if (isset($orderDate['from']) && !empty($orderDate['from'])) {
                $query->where('created_at', '>=', $orderDate['from']);
            }
            if (isset($orderDate['to']) && !empty($orderDate['to'])) {
                $query->where('created_at', '<=', $orderDate['to']);
            }
        }

        // Lọc theo created_at (tạo đơn)
        if (!empty($filters['created_at'])) {
            $createdAt = $filters['created_at'];
            if (isset($createdAt['from']) && !empty($createdAt['from'])) {
                $from = strlen($createdAt['from']) <= 10
                    ? $createdAt['from'] . ' 00:00:00'
                    : $createdAt['from'];
                $query->where('created_at', '>=', $from);
            }
            if (isset($createdAt['to']) && !empty($createdAt['to'])) {
                $to = strlen($createdAt['to']) <= 10
                    ? $createdAt['to'] . ' 23:59:59'
                    : $createdAt['to'];
                $query->where('created_at', '<=', $to);
            }
        }

        // Fulltext search nâng cao (nhiều trường)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                ->orWhere('order_status', 'like', "%{$search}%")
                ->orWhere('currency', 'like', "%{$search}%");
                // Nếu muốn tìm theo liên kết customer, load quan hệ và whereHas
                $q->orWhereHas('customer', function ($qq) use ($search) {
                    $qq->where('full_name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%");
                });
            });
        }

        // Sort
        if (!empty($filters['sort_by']) && !empty($filters['sort_order'])) {
            $query->orderBy($filters['sort_by'], $filters['sort_order']);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }


    public function update(string $id, array $data): bool
    {
        $order = $this->find($id);
        if ($order) {
            return $order->update($data);
        }
        return false;
    }

    public function delete(string $id): bool
    {
        $order = $this->find($id);
        return $order ? $order->delete() : false;
    }

    public function findByOrderCode(string $orderCode): ?Order
    {
        return $this->model->where('order_code', $orderCode)->first();
    }

    public function getOrdersByCustomer(string $customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)->get();
    }

    public function getOrdersByStatus(string $status): Collection
    {
        return $this->model->where('order_status', $status)->get();
    }

    public function getOrdersByTeam(string $teamId): Collection
    {
        return $this->model->where('team_id', $teamId)->get();
    }
}