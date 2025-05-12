<?php

return [
    'already_enabled' => '2FA is already enabled',
    'not_enabled' => '2FA is not enabled',
    'disabled' => '2FA has been disabled',
    'enabled_successfully' => '2FA has been enabled successfully',
    'invalid_code' => 'Invalid 2FA code',
    "secret_key_missing" => '2FA secret key is missing',
    "secret_key_missing_or_expired" => '2FA secret key is missing or expired',
    "code_required_without" => 'The code field is required when recovery code is not present',
    "code_string" => 'The code must be a string',
    "recovery_code_required_without" => 'The recovery code field is required when code is not present',
    "recovery_code_string" => 'The recovery code must be a string',
    "too_many_attempts" => 'Too many attempts. Please try again in :seconds seconds.',
    "not_set_up" => '2FA is not set up for this user',
    "invalid_reconvery_code" => 'Invalid recovery code',
    "recovery_accepted" => 'Recovery code accepted',
    "recovery_required"  => "Either code or recovery code is required",
    "status_changed" => [
        "subject" => "2FA Status Changed",
        "line1" => "Your two-factor authentication has been :status",   // 'Your two-factor authentication has been :status'
        "line2" => "at :time",   // 'at :time'
        "line3" => "from IP address :ip",   // 'from IP address :ip'
        "line4" => "If you did not make this change, please contact support immediately.",   // 'If you did not make this change, please contact support immediately.'
        "ENABLED" => "ENABLED",
        "DISABLED" => "DISABLED",
    ]
];
