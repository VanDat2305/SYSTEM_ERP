<?php

namespace Modules\Order\Interfaces;

use Modules\Order\Models\OrderPackageFeature;
use Illuminate\Database\Eloquent\Collection;

interface OrderPackageFeatureRepositoryInterface
{
    public function find(string $id): ?OrderPackageFeature;
    public function create(array $data): OrderPackageFeature;
    public function update(string $id, array $data): bool;
    public function delete(string $id): bool;
    public function getFeaturesByOrderDetail(string $orderDetailId): Collection;
    public function deleteByOrderDetail(string $orderDetailId): bool;
}