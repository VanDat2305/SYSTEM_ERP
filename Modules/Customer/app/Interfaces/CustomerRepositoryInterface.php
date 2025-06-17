<?php

namespace Modules\Customer\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Customer\Models\Customer;

interface CustomerRepositoryInterface
{
    public function all(): Collection;
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function find(string $id): ?Customer;
    public function create(array $data): Customer;
    public function update(string $id, array $data): Customer;
    public function delete(string $id): bool;
    public function findByType(string $type): Collection;
    public function findByTeam(string $teamId): Collection;
    public function findActive(): Collection;
    public function search(string $keyword): Collection;
    public function findByCustomerCode(string $customerCode): ?Customer;
}