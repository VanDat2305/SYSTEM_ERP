<?php
// Modules/Service/Resources/lang/vn/service.php

return [
    'validation' => [
        'type_service_required' => 'Loại dịch vụ là bắt buộc',
        'customer_type_required' => 'Loại khách hàng là bắt buộc',
        'package_code_unique' => 'Mã gói dịch vụ đã tồn tại',
        'feature_key_required' => 'Key tính năng là bắt buộc',
    ],
    'attributes' => [
        'type_service' => 'Loại dịch vụ',
        'customer_type' => 'Loại khách hàng',
        'package_code' => 'Mã gói',
        'package_name' => 'Tên gói',
        'base_price' => 'Giá cơ bản',
        'billing_cycle' => 'Chu kỳ thanh toán',
        'is_active' => 'Trạng thái',
        'features' => 'Tính năng',
        'feature_key' => 'Key tính năng',
        'feature_name' => 'Tên tính năng',
        'feature_type' => 'Loại tính năng',
        'unit' => 'Đơn vị',
        'limit_value' => 'Giới hạn',
        'is_optional' => 'Tùy chọn',
        'is_customizable' => 'Có thể tùy chỉnh',
        'display_order' => 'Thứ tự hiển thị',
        'description' => 'Mô tả',
        'currency' => 'Tiền tệ',
    ],
    'enums' => [
        'type_service' => [
            'SER_IHD' => 'Dịch vụ IHD',
            'SER_CA' => 'Dịch vụ CA',
            'SER_EC' => 'Dịch vụ EC',
        ],
        'customer_type' => [
            'INDIVIDUAL' => 'Cá nhân',
            'ORGANIZATION' => 'Tổ chức',
        ],
        'billing_cycle' => [
            'monthly' => 'Hàng tháng',
            'yearly' => 'Hàng năm',
            'one-time' => 'Một lần',
        ],
        'feature_type' => [
            'quantity' => 'Số lượng',
            'boolean' => 'Boolean',
        ],
    ],
];

?>