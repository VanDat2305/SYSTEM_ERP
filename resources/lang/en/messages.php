<?php
return [
    'welcome' => 'Welcome to User API',
    'register' => [
        'success' => 'User registered successfully',
        'failed' => 'Registration failed. Please try again.',
    ],
    'login'  => [
        'success' => 'Login successful!',
        'failed' => 'Login failed. Please check your credentials.',
        'credentials_incorrect' => 'The provided credentials are incorrect.',
    ],
    'exceptions' => [
        'access_denied' => "You do not have permission to access this resource.",
        'invalid_data' => 'Invalid data.',
        'data_not_found' => 'Data not found.',
    ],
    'success' => 'Success',
    'deleted_one_success' => ':attribute deleted successfully.', // Cho xóa một
    'deleted_many_success' => ':count :attribute deleted successfully.', // Cho xóa nhiều
    
    'deleted_success' => '{1} :attribute deleted successfully.|[2,*] :count :attribute deleted successfully.',
    'auth' => [
        'unauthenticated' => 'You are not authenticated.',
    ],

    'crud' => [
        'created' => ':model has been created successfully.',
        'updated' => ':model has been updated.',
        'deleted' => ':model has been deleted.',
        'not_found' => ':model not found.',
        'action' => 'Action :action has been performed on :model.',
        'failure' => 'Action :action on :model failed. Please try again.',  // Thêm thông báo thất bại
    ],
    'action' => [
        'created' => 'created',
        'updated' => 'updated',
        'deleted' => 'deleted',
        'retrieved' => 'retrieved',
    ],
    'logout' => [
        'success' => 'Logout successful!',
        'failed' => 'Logout failed. Please try again.',
    ],
];