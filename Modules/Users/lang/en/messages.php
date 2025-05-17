<?php
return [
    'roles' => [
        'retrieved_success' => 'Roles retrieved successfully',
        'failed_to_retrieve' => 'Failed to retrieve roles: :error',
        'failed_to_retrieve_empty' => 'No roles found',
        'created_success' => 'Role created successfully',
        'failed_to_create' => 'Failed to create role: :error',
        'failed_to_create_empty_name' => 'Role name cannot be empty',
        'updated_success' => 'Role updated successfully',
        'failed_to_update' => 'Failed to update role: :error',
        'failed_to_update_empty_name' => 'Role name cannot be empty when updating',
        'deleted_success' => 'Role deleted successfully',
        'failed_to_delete' => 'Failed to delete role with ID :id',
        'assigned_permissions_success' => 'Permissions assigned successfully',
        'failed_to_assign_permissions' => 'Failed to assign permissions to role with ID :id',
        'failed_to_find' => 'Role with ID :id not found',
    ],
    "permissions" => [
        'failed_to_retrieve_empty' => 'No permissions found',
        'retrieved_success' => 'Permissions retrieved successfully',
        'created_success' => 'Permission created successfully',
        'updated_success' => 'Permission updated successfully',
        'deleted_success' => 'Permission deleted successfully',
        'failed_to_retrieve' => 'Failed to retrieve permissions: :error',
        'failed_to_create' => 'Failed to create permission: :error',
        'failed_to_update' => 'Failed to update permission: :error',
        'failed_to_delete' => 'Failed to delete permission: :error',
        'failed_to_find' => 'Permission with ID :id not found',
    ],
    "users" => [
        "delete_failed" => "Failed to delete user",
        "email_not_verified" => "Email not verified",
    ]
];

?>