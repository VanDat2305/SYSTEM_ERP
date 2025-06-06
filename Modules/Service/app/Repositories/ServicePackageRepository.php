<?php

namespace Modules\Service\Repositories;

use Modules\Service\Models\ServicePackage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ServicePackageRepository
{
    public function __construct(private ServicePackage $model)
    {
    }

    public function getAll(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        if (Arr::get($filters, 'type_service') && $filters['type_service'] === 'ALL') {
            unset($filters['type_service']);
        }
        return $this->model
            ->with('features')
            ->when(Arr::get($filters, 'type_service'), fn(Builder $query, string $type) => 
                $query->where('type_service', $type))
            ->when(Arr::get($filters, 'customer_type'), fn(Builder $query, string $type) => 
                $query->where('customer_type', $type))
            ->when(Arr::get($filters, 'billing_cycle'), fn(Builder $query, string $cycle) => 
                $query->where('billing_cycle', $cycle))
            ->when(array_key_exists('is_active', $filters), fn(Builder $q) =>
                $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN))
            )
            ->when(Arr::get($filters, 'search'), fn(Builder $query, string $search) => 
                $query->where(function(Builder $q) use ($search) {
                    $q->where('package_code', 'like', "%{$search}%")
                      ->orWhere('package_name', 'like', "%{$search}%");
                }))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getById(string $id): ?ServicePackage
    {
        return $this->model->with('features')->find($id);
    }

    public function create(array $data): ServicePackage
    {
        if (isset($data['features']) && is_array($data['features'])) {
            $feature = $data['features'];
            unset($data['features']);
        }else {
            $feature = [];
        }
        $package = $this->model->create($data);

        if (isset($feature) && is_array($feature)) {
            $this->syncFeatures($package, $feature);
        }

        return $package->load('features');
    }

    public function update(string $id, array $data): ServicePackage
    {
        $package = $this->getById($id);
        if (!$package) {
            throw new \Exception("Package with ID {$id} not found.");
        }
        if (isset($data['features']) && is_array($data['features'])) {
            $feature = $data['features'];
            unset($data['features']);
        }else {
            $feature = [];
        }
        $package->update($data);
        if (isset($feature) && is_array($feature)) {
            $this->syncFeatures($package, $feature);
        }

        return $package->load('features');
    }

    public function delete($id): bool
    {
        $package = $this->getById($id);
        if (!$package) {
            return false;
        }
        return $package->delete() ? true : false;
    }

    private function syncFeatures(ServicePackage $package, array $features): void
    {
        DB::transaction(function () use ($package, $features) {
            $existingIds = $package->features()->pluck('id')->toArray();
            $newFeatures = [];
            $updatedIds = [];

            foreach ($features as $feature) {
                // Prepare feature data with package_id
                $featureData = [
                    'feature_key' => $feature['feature_key'],
                    'feature_name' => $feature['feature_name'],
                    'feature_type' => $feature['feature_type'],
                    'unit' => $feature['unit'] ?? null,
                    'limit_value' => $feature['limit_value'] ?? null,
                    'is_optional' => $feature['is_optional'] ?? false,
                    'is_customizable' => $feature['is_customizable'] ?? false,
                    'display_order' => $feature['display_order'] ?? 0,
                    'package_id' => $package->id, // Ensure package_id is always set
                ];

                if (isset($feature['id']) && in_array($feature['id'], $existingIds)) {
                    $package->features()->where('id', $feature['id'])->update($featureData);
                    $updatedIds[] = $feature['id'];
                } else {
                    $newFeatures[] = $featureData;
                }
            }

            // Create new features
            if (!empty($newFeatures)) {
                $createdFeatures = $package->features()->createMany($newFeatures);
                $updatedIds = array_merge($updatedIds, $createdFeatures->pluck('id')->toArray());
            }

            // Delete features not present in the request
            $package->features()
                ->whereNotIn('id', $updatedIds)
                ->delete();
        });
    }
}