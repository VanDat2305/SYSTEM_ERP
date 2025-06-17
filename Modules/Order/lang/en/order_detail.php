<?php

// resources/lang/en/order.php
return [
    'created' => 'Order detail added successfully.',
    'updated' => 'Order detail updated successfully.',
    'deleted' => 'Order detail removed successfully.',
    'not_found' => 'Order detail not found.',

    // Validation messages
    'validation' => [
        'service_package_id_required' => 'Service package is required.',
        'quantity_required' => 'Quantity is required.',
        'quantity_min' => 'Quantity must be at least 1.',
        'base_price_required' => 'Base price is required.',
        'start_date_required' => 'Start date is required.',
        'end_date_after' => 'End date must be after start date.',
    ],

    // Fields
    'fields' => [
        'package_name' => 'Package Name',
        'package_code' => 'Package Code',
        'base_price' => 'Base Price',
        'quantity' => 'Quantity',
        'total_price' => 'Total Price',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'is_active' => 'Is Active',
    ],
];
