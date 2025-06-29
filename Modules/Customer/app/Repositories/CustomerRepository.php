<?php

namespace Modules\Customer\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Customer\Models\Customer;
use Modules\Customer\Interfaces\CustomerRepositoryInterface;
use Illuminate\Support\Facades\Auth;

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
        $user = auth()->user();
        // Kiểm tra quyền của người dùng
        if ($user->can('customers.view')) {
            // Admin có thể xem tất cả khách hàng
        } elseif ($user->can('customers.view.team')) {
            // Teamlead hoặc member có thể xem khách hàng trong nhóm của họ
            $teamIds = $user->teams()->pluck('id'); // Lấy tất cả các nhóm mà người dùng tham gia
            $query->whereIn('team_id', $teamIds);
        } elseif ($user->can('customers.view.own')) {
            // Người dùng chỉ có thể xem khách hàng mà họ đã tạo
            $query->where('created_by', $user->id);
        } else {
            // Nếu người dùng không có quyền, trả về lỗi
            throw new \Exception(__('customer::messages.forbidden_access'));
        }


        if (!empty($filters['customer_type']) && strtolower($filters['customer_type']) !== 'all') {
            $query->where('customer_type', $filters['customer_type']);
        }
        if (!empty($filters['status'])) {
            $now = now();

            if ($filters['status'] === 'expiring_soon') {
                $query->whereHas('orderDetails', function ($q) use ($now) {
                    $q->where('start_date', '<=', $now) // Chỉ xét gói đã bắt đầu
                        ->where(function ($subQ) use ($now) {
                            // 1. Sắp hết hạn thời gian (còn < 60 ngày)
                            $subQ->where('end_date', '>', $now)
                                ->where('end_date', '<', $now->copy()->addDays(60));

                            // 2. HOẶC quota còn < 10% (và đã bắt đầu)
                            $subQ->orWhereHas('features', function ($qq) {
                                $qq->where('limit_value', '>', 0)
                                    ->where('quantity', '>', 0)
                                    ->whereRaw('(used_count / (limit_value * quantity)) >= 0.9'); // Đã dùng >= 90%
                            });
                        });
                });
            } elseif ($filters['status'] === 'expired') {
                $query->whereHas('orderDetails', function ($q) use ($now) {
                    $q->where('start_date', '<=', $now) // Chỉ xét gói đã bắt đầu
                        ->where(function ($subQ) use ($now) {
                            // 1. Hết hạn thời gian
                            $subQ->where('end_date', '<', $now);

                            // 2. HOẶC đã dùng hết quota
                            $subQ->orWhereHas('features', function ($qq) {
                                $qq->where('limit_value', '>', 0)
                                    ->where('quantity', '>', 0)
                                    ->whereRaw('used_count >= (limit_value * quantity)');
                            });
                        });
                });
            }

            unset($filters['status']); // Xóa filter để không xử lý lại
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
        if (!empty($filters['customer_code'])) {
            $query->where('customer_code', 'like', "%{$filters['customer_code']}%");
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
    public function findByCustomerCode(string $customerCode): ?Customer
    {
        return $this->model->where('customer_code', $customerCode)->with(['contacts', 'representatives'])->first();
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
