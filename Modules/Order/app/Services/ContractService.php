<?php

namespace Modules\Order\Services;

use App\Mail\SendContractToCustomer;
use Modules\Order\Models\Order;
use Modules\FileManager\Services\FileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\FileManager\Services\FolderService;
use PhpOffice\PhpWord\TemplateProcessor;
use \Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class ContractService
{
    public function exportAndSaveContract(Order $order, $folderId = null)
    {
        DB::beginTransaction();
        // Kiểm tra và lấy folderId, nếu không có thì tạo mới
        $folderId = $this->getFolderService($folderId);
        try {
            // 1. Lấy file mẫu hợp đồng
            $templateName = 'template_econtract.docx'; // hoặc nhận từ ngoài
            $templatePath = storage_path('app/contracts/templates/' . $templateName);

            // 2. Sinh số hợp đồng nếu chưa có
            if (!$order->contract_number) {
                $order->contract_number = $this->generateContractNumber();
            }

            // 3. Merge dữ liệu vào file mẫu Word
            $templateProcessor = new TemplateProcessor($templatePath);

            $orderCode = $order->order_code;
            $link = "https://datmv-solutions-erp.me/payment?order_code=$orderCode";

            $templateProcessor->setValue('url_payment', "$link"); // Link thanh toán
            $templateProcessor->setValue('contract_number', $order->contract_number);
            $templateProcessor->setValue('order_code', $order->order_code);
            $templateProcessor->setValue('to_day', now()->format('d/m/Y'));
            $templateProcessor->setValue('to_day_text', now()->translatedFormat('d \t\há\n\g m \n\ă\m Y'));
            $templateProcessor->setValue('customer_full_name ', $order->customer->full_name ?? ''); // Tên khách hàng
            $templateProcessor->setValue('customer_code', $order->customer->customer_code ?? ''); // Mã khách hàng
            $templateProcessor->setValue('address', $order->customer->address ?? ''); // Địa chỉ khách hàng
            // lấy representative từ customer bảng req nếu có, nếu không thì để trống
            if ($order->customer->customer_type == 'INDIVIDUAL') {
                $templateProcessor->setValue('customer_representative_full_name', $order->customer->full_name ?? ''); // Người đại diện
                $templateProcessor->setValue('customer_representative_position', 'Đại diện'); // chuc vu
            } else {
                $templateProcessor->setValue('customer_representative_full_name', $order->customer->representatives->first()->full_name ?? ''); // Người đại diện
                $templateProcessor->setValue('customer_representative_position', $order->customer->representatives->first()->position ?? ''); // chuc vu
            }
            $templateProcessor->setValue('tax_number', $order->customer->tax_code ?? '');
            $templateProcessor->setValue('total_amount', number_format($order->total_amount, 0, ',', '.')); // Tổng tiền hợp đồng
            $templateProcessor->setValue('currency', $order->currency ?? ''); // Đơn vị tiền tệ

            $templateProcessor->cloneRow('package_name', count($order->details));
            foreach ($order->details as $i => $detail) {
                $templateProcessor->setValue("stt#" . ($i + 1), ($i + 1));
                $templateProcessor->setValue("package_name#" . ($i + 1), $detail->package_name);
                $templateProcessor->setValue("quantity#" . ($i + 1), $detail->quantity);
                $templateProcessor->setValue("base_price#" . ($i + 1), number_format($detail->base_price, 0, ',', '.'));
                $templateProcessor->setValue("tax_rate#" . ($i + 1), number_format($detail->tax_rate));
                $templateProcessor->setValue("total_with_tax#" . ($i + 1), number_format($detail->total_with_tax, 0, ',', '.'));
                // Gộp danh sách tính năng thành chuỗi (1. ...; 2. ...; ...)
                $featureList = '';
                foreach ($detail->packageFeatures as $j => $feature) {
                    $sttFeature = $j + 1;
                    $featureList .= "{$sttFeature}. {$feature->feature_name} ({" . number_format($feature->limit_value) . "} {$feature->unit}); ";
                }
                $templateProcessor->setValue("feature_list#" . ($i + 1), trim($featureList));
            }
            $templateProcessor->setValue('total_amount', number_format($order->total_amount, 0, ',', '.')); // Tổng tiền hợp đồng sau thuế

            // 5. Lưu file Word ra storage (tạm)
            $wordName = 'HopDong_' . $order->order_code . '.docx';
            $wordPath = storage_path('app/contracts/exports/' . $wordName);
            $templateProcessor->saveAs($wordPath);

            // 6. Upload file lên FileService (giống hóa đơn)
            $fileService = app(FileService::class);
            $wordContent = file_get_contents($wordPath);
            $file = $fileService->uploadFromContent($wordContent, $wordName, $folderId, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

            // Nếu cần sinh thêm PDF, convert rồi upload tiếp
            // $pdfContent = ...;
            // $pdfFile = $fileService->uploadFromContent($pdfContent, $pdfName, $folderId, 'application/pdf');

            // 7. Lưu id file vào order (hoặc contract)
            $order->contract_file_id = $file->id;
            $order->contract_date = now();
            $order->contract_status = 'draft';
            $order->save();

            // 8. Xóa file tạm
            unlink($wordPath);

            DB::commit();
            return $file;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
public function exportAndSaveContractPdf(Order $order, $folderId = null)
{
    // Kiểm tra và lấy folderId, nếu không có thì tạo mới
    $folderId = $this->getFolderService($folderId);
    // 1. Merge file Word, lưu ra cloud
    $wordFile = $this->exportAndSaveContract($order, $folderId);

    // 2. Gọi API convert-to-pdf-base64 để lấy base64 PDF
    $convertApi = config("app.url") . '/v1/convert-to-pdf-base64/' . $wordFile->id;
    $response = file_get_contents($convertApi);
    $result = json_decode($response, true);

    if (!$result || empty($result['data']['base64_pdf'])) {
        throw new \Exception("Không convert được file PDF qua API");
    }

    // 3. Giải mã base64 pdf content
    $pdfContent = base64_decode($result['data']['base64_pdf']);

    // 4. Upload PDF lên cloud
    $pdfName = str_replace('.docx', '.pdf', $wordFile->original_name);
    $fileService = app(FileService::class);
    $pdfFile = $fileService->uploadFromContent($pdfContent, $pdfName, $folderId, 'application/pdf');

    $order->contract_file_id = $pdfFile->id;
    $order->contract_status = 'pending';
    $order->save();

    // 5. Trả về giống như cũ
    return [
        'file_pdf' => $pdfFile,
        'file_docx' => $wordFile,
    ];
}




    public function generateContractNumber()
    {
        $prefix = 'CA' . date('Ymd');
        $latest = \Modules\Order\Models\Order::where('contract_number', 'like', $prefix . '%')->max('contract_number');
        $number = $latest ? ((int)substr($latest, -4) + 1) : 1;
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
    public function getFolderService($folderId)
    {
        if (!$folderId) {
            // 1. Tạo folder nếu chưa có (ví dụ: "HOPDONG")
            $folderService = app(FolderService::class);
            $folder = $folderService->getIdByPath('HOPDONG');

            if (!$folder) {
                // Tạo mới nếu chưa có
                $folder = $folderService->create([
                    'id' => Str::uuid(),
                    'name' => 'HOPDONG',
                    'path' => 'HOPDONG',
                    'parent_id' => null, // hoặc ID của folder cha nếu cần
                    'description' => 'Thư mục chứa hóa đơn'
                ]);
                $folderId = $folder->id;
            } else {
                $folderId = $folder;
            }
        }
        return $folderId;
    }
        public function sendContractEmail($order)
    {
        // Ưu tiên lấy email contact chính
        $emailContact = $order->customer->contacts
            ->where('contact_type', 'email')
            ->where('is_primary', 1)
            ->first();
        $email = $emailContact ? $emailContact->value : null;

        // Nếu không có, lấy email của representative đầu tiên (nếu có)
        if (!$email && ($order->customer->representatives->count() > 0)) {
            $rep = $order->customer->representatives->first();
            $email = $rep->email ?? null;
        }
        if (!$email) {
            throw new \Exception('Chưa có email khách hàng');
        }
        if (!$order->contract_file_id) {
            throw new \Exception('Đơn hàng chưa hợp đồng');
        }

        $file = \Modules\FileManager\Models\File::find($order->contract_file_id);
        if (!$file) {
            throw new \Exception('Không tìm thấy file hợp đồng');
        }

        // Tải file tạm về nếu dùng cloud (S3/Spaces), hoặc dùng đường dẫn local
        $tempFilePath = storage_path('app/temp_contracts/' . $file->name);
        if (!file_exists(dirname($tempFilePath))) {
            mkdir(dirname($tempFilePath), 0777, true);
        }

        if (Storage::disk('spaces')->exists($file->path)) {
            // copy file từ cloud về local tạm thời
            file_put_contents($tempFilePath, Storage::disk('spaces')->get($file->path));
        } else {
            throw new \Exception('Không tìm thấy file hợp đồng trên cloud!');
        }

        // Gửi email
        Mail::to($email)
            ->send(new SendContractToCustomer($order, $tempFilePath, $file->name));

        // Xóa file tạm
        @unlink($tempFilePath);

        //log gửi lại hợp đồng
        $logService = app(OrderLogService::class);

        $logService->createLog([
            'order_id'   => $order->id,
            'action'     => "Xuất hợp đồng",
            'note'       => "Xuất hợp đồng cho đơn hàng: " . $order->order_code . ". Số hợp đồng: " . $order->contract_number,
            'file_id'    => $order->contract_file_id,
        ]);
        return [
            'success' => true,
            'email' => $email,
        ];
    }
}
