<?php

return [
    'already_enabled' => 'Xác thực hai yếu tố đã được bật',
    'not_enabled' => 'Xác thực hai yếu tố chưa được bật',
    'disabled' => 'Xác thực hai yếu tố đã được tắt',
    'enabled_successfully' => 'Bật xác thực hai yếu tố thành công',
    'invalid_code' => 'Mã xác thực hai yếu tố không hợp lệ',
    "secret_key_missing" => 'Thiếu khóa bí mật xác thực hai yếu tố',
    "secret_key_missing_or_expired" => 'Khóa bí mật xác thực hai yếu tố bị thiếu hoặc đã hết hạn',
    "code_required_without" => 'Trường mã xác thực là bắt buộc khi không có mã khôi phục',
    "code_string" => 'Mã xác thực phải là một chuỗi',
    "recovery_code_required_without" => 'Trường mã khôi phục là bắt buộc khi không có mã xác thực',
    "recovery_code_string" => 'Mã khôi phục phải là một chuỗi',
    "too_many_attempts" => 'Quá nhiều lần thử. Vui lòng thử lại sau :seconds giây.',
    "not_set_up" => 'Xác thực hai yếu tố chưa được thiết lập cho người dùng này',
    "invalid_reconvery_code" => 'Mã khôi phục không hợp lệ',
    "recovery_accepted" => 'Mã khôi phục đã được chấp nhận',
    "recovery_required"  => "Cần có mã xác thực hoặc mã khôi phục",
    "status_changed" => [
        "subject" => "Trạng thái xác thực hai yếu tố đã thay đổi",
        "line1" => "Xác thực hai yếu tố của bạn đã được :status",   // 'Xác thực hai yếu tố của bạn đã được :status'
        "line2" => "vào lúc :time",   // 'vào lúc :time'
        "line3" => "từ địa chỉ IP :ip",   // 'từ địa chỉ IP :ip'
        "line4" => "Nếu bạn không thực hiện thay đổi này, vui lòng liên hệ với bộ phận hỗ trợ ngay lập tức.",   // 'Nếu bạn không thực hiện thay đổi này, vui lòng liên hệ với bộ phận hỗ trợ ngay lập tức.'
        "ENABLED" => "ĐÃ BẬT",
        "DISABLED" => "ĐÃ TẮT",
    ]
];
