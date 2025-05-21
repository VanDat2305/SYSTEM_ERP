<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Core\Interfaces\ObjectItemRepositoryInterface;
use Modules\Core\Models\ObjectItem;
use Modules\Core\Models\ObjectType;

class ObjectItemService
{
    public function __construct(protected ObjectItemRepositoryInterface $repo) {}
    public function list($request = null)
    {
        return $this->repo->allWithMeta($request);
    }
    public function filterByStatus($status = 'active')
    {
        return $this->repo->filterByStatus($status);
    }

    public function get($id)
    {
        return $this->repo->find($id);
    }
    public function getById($id, $request)
    {

        $status = $request->get('status', 'active');
        
        return $this->repo->findById($id, $status);
    }
    public function store(array $data)
    {
        $meta = $data['meta'] ?? [];
        unset($data['meta']);
        $object = $this->repo->create($data);

        foreach ($meta as $key => $value) {
            $object->meta()->create(['key' => $key, 'value' => $value]);
        }
        $typeCode = ObjectType::find($data['object_type_id'])->code;
        $this->refreshObjectCache($typeCode);
        return $object;
    }
    public function update($id, array $data)
    {
        $meta = $data['meta'] ?? [];
        unset($data['meta']);
        // Cập nhật dữ liệu chính
        $object = $this->repo->update($id, $data);
        // Đồng bộ dữ liệu meta
        if ($object->meta) {
            $existingMeta = $object->meta->pluck('value', 'key')->toArray();
            foreach ($meta as $key => $value) {
                if (array_key_exists($key, $existingMeta)) {
                    // Cập nhật meta đã tồn tại
                    $object->meta()->where('key', $key)->update(['value' => $value]);
                } else {
                    // Thêm meta mới
                    $object->meta()->create(['key' => $key, 'value' => $value]);
                }
            }

            // Xóa các meta không còn tồn tại trong dữ liệu mới
            $metaToDelete = array_diff_key($existingMeta, $meta);
            if (!empty($metaToDelete)) {
                $object->meta()->whereIn('key', array_keys($metaToDelete))->delete();
            }
        } else {
            // Nếu không có meta nào, tạo mới tất cả
            foreach ($meta as $key => $value) {
                $object->meta()->create(['key' => $key, 'value' => $value]);
            }
        }
        $typeCode = ObjectType::find($data['object_type_id'])->code;
        $this->refreshObjectCache($typeCode);
        return $object;
    }
    public function destroy($id)
    {
        return $this->repo->delete($id);
    }

    public function getActiveObjectsByTypeCode(string $typeCode)
    {
        $cacheKey = "core:objects:type:{$typeCode}";

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($typeCode) {
            $type = ObjectType::where('code', $typeCode)->firstOrFail();

            return ObjectItem::where('object_type_id', $type->id)
                ->where('status', 'active')
                ->orderBy('order')
                ->get();
        });
    }
    public function refreshObjectCache(string $typeCode)
    {
        $cacheKey = "core:objects:type:{$typeCode}";
        Cache::forget($cacheKey);

        // Gọi lại để đẩy dữ liệu vào cache
        return $this->getActiveObjectsByTypeCode($typeCode);
    }

}
