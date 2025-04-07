<?php
return [
    'welcome' => 'Xin chào API User',
    'register' => [
        'success' => 'Người dùng đã đăng ký thành công',
        'failed' => 'Đăng ký thất bại. Vui lòng thử lại.',
    ],
    'login' => [
        'success' => 'Đăng nhập thành công!',
        'failed' => 'Đăng nhập thất bại. Vui lòng kiểm tra thông tin.',
        'credentials_incorrect' => 'Thông tin đăng nhập không chính xác.',
    ],
    'exceptions' => [
        'access_denied' => "Bạn không có quyền truy cập vào tài nguyên này.",
        'invalid_data' => 'Dữ liệu không hợp lệ.',
        'data_not_found' => 'Không tìm thấy dữ liệu.',
    ],
    'success' => 'Thành công',
    
    // Thông báo thành công
    'deleted_one_success' => ':attribute đã được xóa thành công.', // Cho xóa một
    'deleted_many_success' => ':count :attribute đã được xóa thành công.', // Cho xóa
    'deleted_success' => '{1} :attribute đã được xóa thành công.|[2,*] :count :attribute đã được xóa thành công.',
];