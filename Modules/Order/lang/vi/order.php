<?php
// resources/lang/vi/order.php
return [

        'created' => 'Đơn hàng đã được tạo thành công.',
        'updated' => 'Đơn hàng đã được cập nhật.',
        'deleted' => 'Đơn hàng đã bị xóa.',
        'not_found' => 'Không tìm thấy đơn hàng.',
        'status_changed' => 'Trạng thái đơn hàng đã được thay đổi.',
        'updated_successfully' => 'Cập nhật thành công.',
        'deleted_successfully' => 'Xóa thành công.',

        // Validation messages
        'validation' => [
            'customer_id_required' => 'ID khách hàng là bắt buộc.',
            'order_status_required' => 'Trạng thái đơn hàng là bắt buộc.',
            'order_date_required' => 'Ngày đơn hàng là bắt buộc.',
            'order_date_date' => 'Ngày đơn hàng phải hợp lệ.',
            'total_amount_numeric' => 'Tổng tiền phải là số.',
        ],

        // Statuses
        'status' => [
            'draft' => 'Bản nháp',
            'pending' => 'Chờ xử lý',
            'confirmed' => 'Đã xác nhận',
            'processing' => 'Đang xử lý',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
        ],

        // Fields
        'fields' => [
            'order_code' => 'Mã đơn hàng',
            'customer_id' => 'Khách hàng',
            'order_status' => 'Trạng thái',
            'total_amount' => 'Tổng tiền',
            'currency' => 'Tiền tệ',
            'team_id' => 'Nhóm',
            'created_by' => 'Người tạo',
        ],

];
