<?php

namespace Modules\Order\Repositories;

use Modules\Order\Interfaces\OrderDetailRepositoryInterface;
use Modules\Order\Models\OrderDetail;
use Illuminate\Database\Eloquent\Collection;
use Modules\Order\Models\Order;

class OrderDetailRepository implements OrderDetailRepositoryInterface
{
    public function __construct(protected OrderDetail $model)
    {
        
    }
    public function find(string $id): ?OrderDetail
    {
        return $this->model->find($id);
    }

    public function create(array $data): OrderDetail
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): bool
    {
        $detail = $this->find($id);
        return $detail ? $detail->update($data) : false;
    }

    public function delete(string $id): bool
    {
        $detail = $this->find($id);
        return $detail ? $detail->delete() : false;
    }

    public function getDetailsByOrder(string $orderId): Collection
    {
        return $this->model->where('order_id', $orderId)->get();
    }

    public function deleteByOrder(string $orderId): bool
    {
        return $this->model->where('order_id', $orderId)->delete();
    }
}