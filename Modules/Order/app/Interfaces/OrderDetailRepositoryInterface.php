<?php

namespace Modules\Order\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Modules\Order\Models\OrderDetail;

interface OrderDetailRepositoryInterface
{
    public function find(string $id): ?OrderDetail;
    public function create(array $data): OrderDetail;
    public function update(string $id, array $data): bool;
    public function delete(string $id): bool;
    public function getDetailsByOrder(string $orderId): Collection;
    public function deleteByOrder(string $orderId): bool;
}