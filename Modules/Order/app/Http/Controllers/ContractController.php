<?php

namespace Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Order\Models\Order;

use Modules\Order\Services\ContractService;

class ContractController extends Controller
{
    /**
     * Xuất hợp đồng (Word), lưu qua FileService và trả về thông tin file.
     * POST /api/orders/{order}/export-contract
     */
    public function exportContract(Request $request, $orderId)
    {
        $order = Order::with([
            'customer.contacts',
            'customer.representatives',
            'details.features'
        ])->findOrFail($orderId);

        $folderId = $request->input('folder_id', null);
        $fileType = $request->input('file_type', 'docx'); // 'docx' (default) hoặc 'pdf'
        $contractService = app(ContractService::class);

        try {
            if ($fileType === 'pdf') {
                $result = $contractService->exportAndSaveContractPdf($order, $folderId);
                $file = $result['file_pdf'];
                $fileDocx = $result['file_docx'];
                // Có thể trả luôn cả link Word lẫn PDF nếu muốn!
                return response()->json([
                    'success' => true,
                    'file_id' => $file->id,
                    'file_name' => $file->original_name,
                    'url' => $file->url,
                    'file_id_docx' => $fileDocx->id,
                    // 'url_docx' => $fileDocx->url,
                    'order_id' => $order->id,
                    'contract_number' => $order->contract_number,
                    'file_type' => $fileType,
                ]);
            } else {
                $file = $contractService->exportAndSaveContract($order, $folderId);
                return response()->json([
                    'success' => true,
                    'file_id' => $file->id,
                    'file_name' => $file->original_name,
                    'url' => $file->url, // link tải file docx
                    'order_id' => $order->id,
                    'contract_number' => $order->contract_number,
                    'file_type' => $fileType,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xuất hợp đồng: ' . $e->getMessage(),
            ], 500);
        }
    }
}
