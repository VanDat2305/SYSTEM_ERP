<?php

namespace Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\FileManager\Services\FileService;
use Modules\Order\Models\Order;

use Modules\Order\Services\ContractService;
use Modules\Order\Services\OrderLogService;

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
        $logService = app(OrderLogService::class);
        try {
            
            if ($fileType === 'pdf') {
                $result = $contractService->exportAndSaveContractPdf($order, $folderId);
                $file = $result['file_pdf'];
                $fileDocx = $result['file_docx'];
                $note = "Xuất hợp đồng PDF: " . $file->original_name . ". Số hợp đồng: " . $order->contract_number;
                $logService->createLog([
                    'order_id'   => $orderId,
                    'action'     => "Xuất hợp đồng",
                    'note'       => $note,
                    'file_id'    => $file->id,
                ]);
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
                $note = "Xuất hợp đồng Word: " . $file->original_name. ". Số hợp đồng: " . $order->contract_number;
                $logService->createLog([
                    'order_id'   => $orderId,
                    'action'     => "Xuất hợp đồng",
                    'note'       => $note,
                    'file_id'    => $file->id,
                ]);
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
    public function uploadFileSigned(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);
        $file = $request->file('file');
        
        if (!$file) {
            return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
        }

        // Validate file type and size if needed
        // $this->validate($request, [
        //     'file_signed' => 'required|mimes:pdf|max:2048', // Example validation
        // ]);

        try {
            $contractService = app(ContractService::class);
            $folderId = $contractService->getFolderService($request->input('folder_id', null));
            $fileService = app(FileService::class);
            $uploadedFile = $fileService->upload($file,  $folderId);

            // Lưu thông tin file vào order
            $order->contract_file_id = $uploadedFile->id;
            $order->contract_status = 'signed';
            $order->save();

            // Ghi log
            $logService = app(OrderLogService::class);
            $logService->createLog([
                'order_id'   => $orderId,
                'action'     => 'Upload file ký',
                'note'       => 'Cập nhật hợp đồng đã ký: ' . $uploadedFile->original_name,
                'file_id'    => $uploadedFile->id,
                'user_id'    => auth()->id(),
                'user_name'  => auth()->user()?->name ?? null,
            ]);
            return response()->json([
                'success' => true,
                'file_id' => $uploadedFile->id,
                'url' => $uploadedFile->url,
                'message' => 'File signed uploaded successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading file: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function deleteFileContract(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);
        
        if (!$order->contract_file_id) {
            return response()->json(['success' => false, 'message' => 'No contract file found'], 404);
        }

        try {
            // $fileService = app(FileService::class);
            // $fileService->delete($order->contract_file_id);

            // Xóa thông tin file khỏi order
            $order->contract_file_id = null;
            $order->contract_status = 'draft'; // Hoặc trạng thái khác nếu cần
            $order->save();

            // Ghi log
            $logService = app(OrderLogService::class);
            $logService->createLog([
                'order_id'   => $orderId,
                'action'     => 'Xóa file hợp đồng',
                'note'       => 'Đã xóa hợp đồng',
                'file_id'    => null,
                'user_id'    => auth()->id(),
                'user_name'  => auth()->user()?->name ?? null,
            ]);

            return response()->json(['success' => true, 'message' => 'Contract file deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting file: ' . $e->getMessage()], 500);
        }
    }
}
