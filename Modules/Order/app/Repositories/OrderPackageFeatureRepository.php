<?php

namespace Modules\Order\Repositories;

use Modules\Order\Interfaces\OrderPackageFeatureRepositoryInterface;
use Modules\Order\Models\OrderPackageFeature;
use Illuminate\Database\Eloquent\Collection;

class OrderPackageFeatureRepository implements OrderPackageFeatureRepositoryInterface
{
    public function __construct(protected OrderPackageFeature $model)
    {
        // Constructor can be used for dependency injection if needed
    }
    public function find(string $id): ?OrderPackageFeature
    {
        return $this->model->find($id);
    }

    public function create(array $data): OrderPackageFeature
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): bool
    {
        $feature = $this->find($id);
        return $feature ? $feature->update($data) : false;
    }

    public function delete(string $id): bool
    {
        $feature = $this->find($id);
        return $feature ? $feature->delete() : false;
    }

    public function getFeaturesByOrderDetail(string $orderDetailId): Collection
    {
        return $this->model->where('order_detail_id', $orderDetailId)->get();
    }

    public function deleteByOrderDetail(string $orderDetailId): bool
    {
        return $this->model->where('order_detail_id', $orderDetailId)->delete();
    }
}