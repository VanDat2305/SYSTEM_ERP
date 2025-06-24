<?php

namespace Modules\Order\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Order\Models\Order;
use Modules\FileManager\Services\FileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendInvoiceToCustomer;
use Illuminate\Support\Facades\Storage;
use Modules\FileManager\Services\FolderService;
use \Illuminate\Support\Str;

class InvoiceService
{
    public function exportAndSaveInvoice(Order $order, $folderId = null)
    {
        DB::beginTransaction();
        if (!$folderId) {
            // 1. Tạo folder nếu chưa có (ví dụ: "HOADON")
            $folderService = app(FolderService::class);
            $folder = $folderService->getIdByPath('HOADON');
            
            if (!$folder) {
                // Tạo mới nếu chưa có
                $folder = $folderService->create([
                    'id' => Str::uuid(),
                    'name' => 'HOADON',
                    'path' => 'HOADON',
                    'parent_id' => null, // hoặc ID của folder cha nếu cần
                    'description' => 'Thư mục chứa hóa đơn'
                ]);
                $folderId = $folder->id;   
            } else {
                $folderId = $folder;
            }
        }
        try {
            // 3. Sinh số hóa đơn nếu chưa có
            if (!$order->invoice_number) {
                $order->invoice_number = $this->generateInvoiceNumber();
            }

            // 1. Sinh PDF từ blade view
            $pdf = Pdf::loadView('invoices.pdf', ['order' => $order]);
            $pdfContent = $pdf->output();
            $originalName = 'HoaDon_' . $order->order_code . '.pdf';

            // 2. Lưu file bằng FileService (file PDF hóa đơn)
            $fileService = app(FileService::class);
            $file = $fileService->uploadFromContent($pdfContent, $originalName, $folderId, 'application/pdf');


            // 4. Update các trường hóa đơn cho order
            $order->invoice_file_id = $file->id;
            $order->invoice_exported_at = now();
            $order->invoice_status = 'exported';
            $order->save();

            DB::commit();
            return $file;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // Hàm tự sinh số hóa đơn (tuỳ bạn điều chỉnh quy tắc)
    public function generateInvoiceNumber()
    {
        $prefix = 'HD' . date('Ymd');
        $latest = \Modules\Order\Models\Order::where('invoice_number', 'like', $prefix . '%')->max('invoice_number');
        $number = $latest ? ((int)substr($latest, -4) + 1) : 1;
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
    public function sendInvoiceToCustomer($order)
    {
        // Ưu tiên lấy email contact chính
        $emailContact = $order->customer->contacts
            ->where('contact_type', 'email')
            ->where('is_primary', 1)
            ->first();

        $email = $emailContact ? $emailContact->contact : null;

        // Nếu không có, lấy email của representative đầu tiên (nếu có)
        if (!$email && ($order->customer->representatives->count() > 0)) {
            $rep = $order->customer->representatives->first();
            $email = $rep->email ?? null;
        }
        $email = 'datmv.dev@gmail.com';
        if (!$email) {
            throw new \Exception('Chưa có email khách hàng');
        }
        if (!$order->invoice_file_id) {
            throw new \Exception('Đơn hàng chưa có hóa đơn để gửi');
        }

        $file = \Modules\FileManager\Models\File::find($order->invoice_file_id);
        if (!$file) {
            throw new \Exception('Không tìm thấy file hóa đơn');
        }

        // Tải file tạm về nếu dùng cloud (S3/Spaces), hoặc dùng đường dẫn local
        $tempFilePath = storage_path('app/temp_invoices/' . $file->name);
        if (!file_exists(dirname($tempFilePath))) {
            mkdir(dirname($tempFilePath), 0777, true);
        }

        if (Storage::disk('spaces')->exists($file->path)) {
            // copy file từ cloud về local tạm thời
            file_put_contents($tempFilePath, Storage::disk('spaces')->get($file->path));
        } else {
            throw new \Exception('Không tìm thấy file hóa đơn trên cloud!');
        }

        // Gửi email
        Mail::to($email)
            ->send(new SendInvoiceToCustomer($order, $tempFilePath, $file->name));

        // Xóa file tạm
        @unlink($tempFilePath);

        //log gửi lại hóa đơn
        $logService = app(OrderLogService::class);

        $logService->createLog([
            'order_id'   => $order->id,
            'action'     => "Xuất hóa đơn",
            'note'       => "Xuất hóa đơn cho đơn hàng: " . $order->order_code . ". Số hóa đơn: " . $order->invoice_number,
            'file_id'    => $order->invoice_file_id,
        ]);
        return;
    }
}
