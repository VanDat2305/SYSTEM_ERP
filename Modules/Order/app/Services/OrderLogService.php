<?php

namespace Modules\Order\Services;
use Modules\FileManager\Services\FileService;
use Modules\Order\Models\OrderLog;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class OrderLogService
{
    public function createLog(array $data): OrderLog
    {
        return OrderLog::create([
            'id'         => Str::uuid()->toString(),
            'order_id'   => $data['order_id'],
            'action'     => $data['action'] ?? 'update',
            'old_status' => $data['old_status'] ?? null,
            'new_status' => $data['new_status'] ?? null,
            'note'       => $data['note'] ?? null,
            'file_id'    => $data['file_id'] ?? null,
            'user_id'    => $data['user_id'] ?? auth()->id() ?? null,
            'user_name'  => $data['user_name'] ?? (auth()->user()?->name ?? null),
            'created_at' => now()
        ]);
    }
}