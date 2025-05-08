<?php

namespace Modules\Users\Services;

use Exception;
use Illuminate\Http\Request;
use Modules\Users\Interfaces\PermissionRepositoryInterface;

class PermissionService
{
    protected $permissionRepository;

    public function __construct(PermissionRepositoryInterface $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

    public function getAllPermissions($request)
    {
        $query = $this->permissionRepository->getModel()->newQuery();

        $filterable = ['name', 'guard_name', 'status', 'id'];
        $searchable = ['name', 'guard_name']; // Các cột sẽ tìm kiếm toàn cục
        $allowedOperators = ['=', '!=', '<', '>', '<=', '>=', 'like', 'in'];

        // Xử lý lọc động
        if ($request->has('filters') && is_array($request->filters)) {
            foreach ($request->filters as $filter) {
                $field = $filter['field'] ?? null;
                $operator = strtolower($filter['operator'] ?? '=');
                $value = $filter['value'] ?? null;
                if (in_array($field, $filterable) && in_array($operator, $allowedOperators)) {
                    if ($operator === 'like') {
                        $query->where($field, 'like', '%' . $value . '%');
                    } elseif ($operator === 'in' && is_array($value)) {
                        $query->whereIn($field, $value);
                    } else {
                        $query->where($field, $operator, $value);
                    }
                }
            }
        }

        // Tìm kiếm toàn cục
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm, $searchable) {
                foreach ($searchable as $column) {
                    $q->orWhere($column, 'like', '%' . $searchTerm . '%');
                }
            });
        }

        // Sắp xếp
        $orderBy = $request->input('order_by', 'id');
        $orderDir = $request->input('order_dir', 'desc');
        if (in_array($orderBy, $filterable) && in_array(strtolower($orderDir), ['asc', 'desc'])) {
            $query->orderBy($orderBy, $orderDir);
        }

        // // Phân trang
        // $perPage = $request->input('per_page', 15); // mặc định 15 dòng/trang
        // $page = $request->input('page', 1);
        // $permissions = $query->paginate($perPage, ['*'], 'page', $page);
        $permissions = $query->get();
        if ($permissions->isEmpty()) {
            throw new Exception(trans('users::messages.permissions.failed_to_retrieve_empty'));
        }

        return $permissions;
    }

    public function getPermission($id)
    {
        try {
            return $this->permissionRepository->find($id);
        } catch (Exception $e) {
            throw new Exception(trans('users::messages.permissions.failed_to_find', ['id' => $id]));
        }
    }

    public function createPermission(array $data)
    {
        return $this->permissionRepository->create($data);
    }

    public function updatePermission($id, array $data)
    {
        return $this->permissionRepository->update($id, $data);
    }

    public function deletePermission($id)
    {
        return $this->permissionRepository->delete($id);
    }
}