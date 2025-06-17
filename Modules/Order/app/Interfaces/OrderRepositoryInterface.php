<?php

namespace Modules\Order\Interfaces;

use Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function all(): Collection;
    public function find(string $id): ?Order;
    public function paginate(int $perPage = 15, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    public function create(array $data): Order;
    public function update(string $id, array $data): bool;
    public function delete(string $id): bool;
    public function findByOrderCode(string $orderCode): ?Order;
    public function getOrdersByCustomer(string $customerId): Collection;
    public function getOrdersByStatus(string $status): Collection;
    public function getOrdersByTeam(string $teamId): Collection;
}