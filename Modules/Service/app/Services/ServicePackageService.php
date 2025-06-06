<?php

namespace Modules\Service\Services;

use Modules\Service\Models\ServicePackage;
use Modules\Service\Repositories\ServicePackageRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ServicePackageService
{
    public function __construct(
        private ServicePackageRepository $repository
    ) {
    }

    public function getAllPackages(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->getAll($filters, $perPage);
    }

    public function getPackageById(string $id): ?ServicePackage
    {
        return $this->repository->getById($id);
    }

    public function createPackage(array $data): ServicePackage
    {
        return $this->repository->create($data);
    }

    public function updatePackage(string $id, array $data): ServicePackage
    {
        return $this->repository->update($id, $data);
    }

    public function deletePackage(string $id): bool
    {
        return $this->repository->delete($id);
    }
}