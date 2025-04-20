<?php

namespace Modules\Users\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';         // Hoạt động bình thường
    case INACTIVE = 'inactive';     // Không hoạt động (vô hiệu hóa)
    case PENDING = 'pending';       // Chờ kích hoạt
    case SUSPENDED = 'suspended';   // Bị khóa tạm thời
    case BANNED = 'banned';         // Bị cấm vĩnh viễn
    case DELETED = 'deleted';       // Đã xóa (soft delete)

    /**
     * Lấy danh sách trạng thái dưới dạng mảng
     */
    public static function getValues(): array
    {
        return array_map(fn(self $status) => $status->value, self::cases());
    }

    /**
     * Lấy tên trạng thái (đa ngôn ngữ)
     */
    public function getLabel(): string
    {
        return __("status.users.{$this->value}");
    }
}