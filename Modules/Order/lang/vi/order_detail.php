<?php

// resources/lang/en/order.php
return [
    'created' => 'Chi tiết đơn hàng đã được thêm.',
    'updated' => 'Chi tiết đơn hàng đã được cập nhật.',
    'deleted' => 'Chi tiết đơn hàng đã bị xóa.',
    'not_found' => 'Không tìm thấy chi tiết đơn hàng.',

    // Validation messages
    'validation' => [
        'service_package_id_required' => 'Gói dịch vụ là bắt buộc.',
        'quantity_required' => 'Số lượng là bắt buộc.',
        'quantity_min' => 'Số lượng phải ít nhất là 1.',
        'base_price_required' => 'Giá cơ bản là bắt buộc.',
        'start_date_required' => 'Ngày bắt đầu là bắt buộc.',
        'end_date_after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
    ],

    // Fields
    'fields' => [
        'package_name' => 'Tên gói',
        'package_code' => 'Mã gói',
        'base_price' => 'Giá cơ bản',
        'quantity' => 'Số lượng',
        'total_price' => 'Tổng tiền',
        'start_date' => 'Ngày bắt đầu',
        'end_date' => 'Ngày kết thúc',
        'is_active' => 'Kích hoạt',
    ],
];
