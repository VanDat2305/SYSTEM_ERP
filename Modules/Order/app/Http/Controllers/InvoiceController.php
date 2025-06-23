<?php

namespace Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Order\Models\Order;
use Modules\Order\Services\InvoiceService;


class InvoiceController extends Controller
{
    public function export(Request $request, $orderId)
    {
        $order = Order::with('details', 'customer.representatives')->findOrFail($orderId);
        // Kiểm tra trạng thái đơn, ví dụ: chỉ cho xuất khi paid
        if ($order->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng chưa được thanh toán. Không thể xuất hóa đơn.'
            ], 400);
        }

        // Nếu đã có hóa đơn (không cho xuất lại, hoặc xử lý nghiệp vụ nếu muốn cho xuất lại)
        if ($order->invoice_status === 'exported' && $order->invoice_file_id) {
            return response()->json([
                'success' => false,
                'message' => 'Hóa đơn đã được xuất trước đó.',
                'invoice_file_id' => $order->invoice_file_id
            ], 409);
        }

        // Thực hiện xuất hóa đơn
        $invoiceService = app(InvoiceService::class);
        $file = $invoiceService->exportAndSaveInvoice($order);

        return response()->json([
            'message' => 'Xuất hóa đơn thành công!',
            'invoice_file_id' => $file->id,
            'file_url' => $file->url,
            'invoice_number' => $order->invoice_number,
            'exported_at' => $order->invoice_exported_at,
        ]);
    }
    public function resendInvoice(Request $request, $orderId)
    {
        $order = Order::with('customer')->findOrFail($orderId);
        if (!$order->invoice_file_id) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng chưa có hóa đơn để gửi lại.'
            ], 400);
        }

        // Gửi email hóa đơn
        $invoiceService = app(InvoiceService::class);
        try {
            $invoiceService->sendInvoiceToCustomer($order);
            return response()->json([
                'success' => true,
                'message' => 'Hóa đơn đã được gửi lại thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gửi hóa đơn thất bại: ' . $e->getMessage()
            ], 500);
        }
    }

}
