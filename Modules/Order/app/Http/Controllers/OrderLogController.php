<?php

namespace Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Modules\FileManager\Services\FileService;
use Modules\Order\Models\OrderLog;
use Modules\Order\Services\OrderLogService;

class OrderLogController extends Controller
{
    public function index(Request $request, $orderId)
    {
        $pageSize = $request->input('page_size', 10);
        $logs = OrderLog::where('order_id', $orderId)
            ->orderByDesc('created_at')
            ->paginate($pageSize);
        return response()->json($logs);
    }
    public function store(Request $request, $orderId)
    {
        $request->validate([
            'note'    => 'required|string',
            'action'  => 'nullable|string|max:50',
            'file'    => 'nullable|file|max:10120', // 10MB
        ], [], 
        [
            'note'    => 'Ghi chú',
            'action'  => 'Hành động',
            'file'    => 'Tệp đính kèm',
        ]);
        DB::beginTransaction();
        try {



            $fileId = null;

            // Xử lý upload file nếu có
            if ($request->hasFile('file')) {
                $fileService = app(FileService::class);
                $file = $fileService->upload($request->file('file'), null);
                $fileId = $file->id;
            }

            $logService = app(OrderLogService::class);

            $log = $logService->createLog([
                'order_id'   => $orderId,
                'action'     => "Ghi chú",
                'note'       => $request->input('note'),
                'file_id'    => $fileId,
                // old_status, new_status có thể truyền từ client nếu muốn lưu
                'old_status' => $request->input('old_status'),
                'new_status' => $request->input('new_status'),
            ]);
            DB::commit();
            return response()->json(['success' => true, 'data' => $log]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }
}
