<?php

namespace Modules\Customer\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Customer\Models\Customer;
use Modules\Customer\Interfaces\CustomerRepositoryInterface;

class CustomerRepository implements CustomerRepositoryInterface
{
    protected Customer $model;

    public function __construct(Customer $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->with(['contacts', 'representatives'])->get();
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->with(['contacts', 'representatives']);

        if (!empty($filters['customer_type']) && strtolower($filters['customer_type']) !== 'all') {
            $query->where('customer_type', $filters['customer_type']);
        }

        if (!empty($filters['status']) && strtolower($filters['status']) !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['team_id'])) {
            $query->whereIn('team_id', $filters['team_id']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->whereIn('assigned_to', $filters['assigned_to']);
        }
        if (!empty($filters['query']) && !empty($filters['field'])) {
            $query->where($filters['field'], 'like', "%{$filters['query']}%");
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('short_name', 'like', "%{$search}%")
                  ->orWhere('tax_code', 'like', "%{$search}%")
                  ->orWhere('identity_number', 'like', "%{$search}%");
            });
        }
        if (!empty($filters['created_at'])) {
            $createdAt = $filters['created_at'];

            if (isset($createdAt['from']) && !empty($createdAt['from'])) {
                // Thêm giờ đầu ngày nếu chỉ có yyyy-mm-dd
                $from = strlen($createdAt['from']) <= 10
                    ? $createdAt['from'] . ' 00:00:00'
                    : $createdAt['from'];
                $query->where('created_at', '>=', $from);
            }

            if (isset($createdAt['to']) && !empty($createdAt['to'])) {
                // Thêm giờ cuối ngày nếu chỉ có yyyy-mm-dd
                $to = strlen($createdAt['to']) <= 10
                    ? $createdAt['to'] . ' 23:59:59'
                    : $createdAt['to'];
                $query->where('created_at', '<=', $to);
            }
        }


        if (!empty($filters['sort_by']) && !empty($filters['sort_order'])) {
            $query->orderBy($filters['sort_by'], $filters['sort_order']);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    public function find(string $id): ?Customer
    {
        return $this->model->with(['contacts', 'representatives'])->find($id);
    }

    public function create(array $data): Customer
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): Customer
    {
        $customer = $this->find($id);
        if (!$customer) {
            throw new \Exception(__("customer::messages.customer_not_found"));
        }
        
        $customer->update($data);
        return $customer->fresh(['contacts', 'representatives']);
    }

    public function delete(string $id): bool
    {
        $customer = $this->find($id);
        if (!$customer) {
            throw new \Exception(__('customer::messages.customer_not_found'));
        }
        
        return $customer->delete();
    }

    public function findByType(string $type): Collection
    {
        return $this->model->byType($type)->with(['contacts', 'representatives'])->get();
    }

    public function findByTeam(string $teamId): Collection
    {
        return $this->model->byTeam($teamId)->with(['contacts', 'representatives'])->get();
    }

    public function findActive(): Collection
    {
        return $this->model->active()->with(['contacts', 'representatives'])->get();
    }

    public function search(string $keyword): Collection
    {
        return $this->model->where(function ($query) use ($keyword) {
            $query->where('full_name', 'like', "%{$keyword}%")
                  ->orWhere('short_name', 'like', "%{$keyword}%")
                  ->orWhere('tax_code', 'like', "%{$keyword}%")
                  ->orWhere('identity_number', 'like', "%{$keyword}%");
        })->with(['contacts', 'representatives'])->get();
    }
}
