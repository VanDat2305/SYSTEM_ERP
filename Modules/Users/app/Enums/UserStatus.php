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
        return [
            self::ACTIVE->value,
            self::INACTIVE->value,
            self::PENDING->value,
            self::SUSPENDED->value,
            self::BANNED->value,
            self::DELETED->value,
        ];
    }

    /**
     * Lấy tên trạng thái
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Hoạt động',
            self::INACTIVE => 'Không hoạt động',
            self::PENDING => 'Chờ kích hoạt',
            self::SUSPENDED => 'Bị tạm khóa',
            self::BANNED => 'Bị cấm',
            self::DELETED => 'Đã xóa',
        };
    }
}