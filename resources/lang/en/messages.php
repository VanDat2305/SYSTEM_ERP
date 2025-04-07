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
];