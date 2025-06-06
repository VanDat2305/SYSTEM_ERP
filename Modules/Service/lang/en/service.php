<?php
// Modules/Service/Resources/lang/en/service.php

return [
    'validation' => [
        'type_service_required' => 'Service type is required',
        'customer_type_required' => 'Customer type is required',
        'package_code_unique' => 'Package code already exists',
        'feature_key_required' => 'Feature key is required',
    ],
    'attributes' => [
        'type_service' => 'Service Type',
        'customer_type' => 'Customer Type',
        'package_code' => 'Package Code',
        'package_name' => 'Package Name',
        'base_price' => 'Base Price',
        'billing_cycle' => 'Billing Cycle',
        'is_active' => 'Status',
        'features' => 'Features',
        'feature_key' => 'Feature Key',
        'feature_name' => 'Feature Name',
        'feature_type' => 'Feature Type',
        'unit' => 'Unit',
        'limit_value' => 'Limit Value',
        'is_optional' => 'Optional',
        'is_customizable' => 'Customizable',
        'display_order' => 'Display Order',
        'description' => 'Description',
        "currency" => 'Currency',
    ],
    'enums' => [
        'type_service' => [
            'SER_IHD' => 'IHD Service',
            'SER_CA' => 'CA Service',
            'SER_EC' => 'EC Service',
        ],
        'customer_type' => [
            'INDIVIDUAL' => 'Individual',
            'ORGANIZATION' => 'Organization',
        ],
        'billing_cycle' => [
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
            'one-time' => 'One-time',
        ],
        'feature_type' => [
            'quantity' => 'Quantity',
            'boolean' => 'Boolean',
        ],
    ],
];

?>