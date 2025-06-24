<?php

namespace Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Modules\Customer\Models\Customer;
use Modules\FileManager\Services\FileService;
use Modules\Customer\Models\CustomerLog;
use Modules\Customer\Services\CustomerLogService;

class CustomerLogController extends Controller
{
    public function index(Request $request, $customerId)
    {
        $pageSize = $request->input('page_size', 10);
        $logs = CustomerLog::where('object_type', 'customer')->where('object_id', $customerId)
            ->orderByDesc('created_at')
            ->paginate($pageSize);
        return response()->json($logs);
    }
    public function store(Request $request, $customerId)
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
        
        $customer = Customer::find($customerId); // Kiểm tra khách hàng có tồn tại
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Khách hàng không tồn tại'], 404);
        }
        DB::beginTransaction();
        try {



            $fileId = null;

            // Xử lý upload file nếu có
            if ($request->hasFile('file')) {
                $fileService = app(FileService::class);
                $file = $fileService->upload($request->file('file'), null);
                $fileId = $file->id;
            }

            $logService = app(CustomerLogService::class);

            $log = $logService->createLog([
                'object_id'   => $customerId,
                'action'     => "Ghi chú",
                'note'       => $request->input('note'),
                'file_id'    => $fileId,
            ]);
            DB::commit();
            return response()->json(['success' => true, 'data' => $log]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }
}
