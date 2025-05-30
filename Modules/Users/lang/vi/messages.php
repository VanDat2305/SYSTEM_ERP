<?php
return [
    'roles' => [
        'retrieved_success' => 'Danh sách vai trò được lấy thành công',
        'failed_to_retrieve' => 'Không thể lấy danh sách vai trò: :error',
        'failed_to_retrieve_empty' => 'Không tìm thấy vai trò',
        'created_success' => 'Vai trò được tạo thành công',
        'failed_to_create' => 'Không thể tạo vai trò: :error',
        'failed_to_create_empty_name' => 'Tên vai trò không được để trống',
        'updated_success' => 'Vai trò được cập nhật thành công',
        'failed_to_update' => 'Không thể cập nhật vai trò: :error',
        'failed_to_update_empty_name' => 'Tên vai trò không được để trống khi cập nhật',
        'deleted_success' => 'Vai trò được xóa thành công',
        'failed_to_delete' => 'Không thể xóa vai trò với ID :id',
        'assigned_permissions_success' => 'Quyền được gán thành công',
        'failed_to_assign_permissions' => 'Không thể gán quyền cho vai trò với ID :id',
        'failed_to_find' => 'Không tìm thấy vai trò với ID :id',
    ],
    "permissions" => [
        'failed_to_retrieve_empty' => 'Không tìm thấy quyền',
        'retrieved_success' => 'Quyền đã được lấy thành công',
        'created_success' => 'Quyền đã được tạo thành công',
        'updated_success' => 'Quyền đã được cập nhật thành công',
        'deleted_success' => 'Quyền đã được xóa thành công',
        'failed_to_retrieve' => 'Không thể lấy quyền: :error',
        'failed_to_create' => 'Không thể tạo quyền: :error',
        'failed_to_update' => 'Không thể cập nhật quyền: :error',
        'failed_to_delete' => 'Không thể xóa quyền: :error',
        'failed_to_find' => 'Không tìm thấy quyền với ID :id',
    ], 
    "users" => [
        "delete_failed" => "Không thể xóa người dùng",
        "email_not_verified" => "Email chưa được xác nhận",
        "change_password_success" => "Đổi mật khẩu thành công",
    ]
];
?>