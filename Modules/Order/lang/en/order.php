<?php

// resources/lang/en/order.php
return [

    'created' => 'Order created successfully.',
    'updated' => 'Order updated successfully.',
    'deleted' => 'Order deleted successfully.',
    'not_found' => 'Order not found.',
    'status_changed' => 'Order status changed successfully.',
    'updated_successfully' => 'Order updated successfully.',
    'deleted_successfully' => 'Order deleted successfully.',
    'bulk_status_updated' => 'Bulk status update successful. :count orders updated.',

    // Validation messages
    'validation' => [
        'customer_id_required' => 'Customer ID is required.',
        'order_status_required' => 'Order status is required.',
        'order_date_required' => 'Order date is required.',
        'order_date_date' => 'Order date must be a valid date.',
        'total_amount_numeric' => 'Total amount must be a number.',
    ],

    // Statuses
    'status' => [
        'draft' => 'Draft',
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    // Fields
    'fields' => [
        'order_code' => 'Order Code',
        'customer_id' => 'Customer',
        'order_status' => 'Status',
        'total_amount' => 'Total Amount',
        'currency' => 'Currency',
        'team_id' => 'Team',
        'created_by' => 'Created By',
    ],


];
