<?php

namespace Modules\Customer\Services;
use Illuminate\Support\Str;
use Modules\Customer\Models\CustomerLog;

class CustomerLogService
{
    public function createLog(array $data): CustomerLog
    {
        return CustomerLog::create([
            'id'         => Str::uuid()->toString(),
            'object_type'   => $data['order_id'] ?? 'customer',
            'object_id'   => $data['object_id'],
            'action'     => $data['action'] ?? 'update',
            'old_value' => $data['old_status'] ?? null,
            'new_value' => $data['new_status'] ?? null,
            'note'       => $data['note'] ?? null,
            'file_id'    => $data['file_id'] ?? null,
            'user_id'    => $data['user_id'] ?? auth()->id(),
            'user_name'  => $data['user_name'] ?? (auth()->user()?->name ?? null),
            'created_at' => now()
        ]);
    }
}