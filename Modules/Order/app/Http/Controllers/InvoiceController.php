<?php

namespace Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Modules\Order\Models\Order;
use Modules\Order\Services\InvoiceService;
use Modules\Order\Services\OrderLogService;

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

        //log xuất hóa đơn
        $logService = app(OrderLogService::class);

        $logService->createLog([
            'order_id'   => $order->id,
            'action'     => "Xuất hóa đơn",
            'note'       => "Xuất hóa đơn cho đơn hàng: " . $order->order_code . ". Số hóa đơn: " . $order->invoice_number,
            'file_id'    => $file->id,
        ]);

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
    public function addPayment(Request $request, $orderId)
    {
        DB::beginTransaction();
        try {
            $order = Order::findOrFail($orderId);
            $amount_paid = $request->input('amount_paid');
            $payment_date = $request->input('payment_date');
            $paymentMethod = $request->input('payment_method', 'bank_transfer'); // Mặc định là bank_transfer

            // Kiểm tra số tiền thanh toán
            if ($amount_paid <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Số tiền thanh toán phải lớn hơn 0.'
                ], 400);
            }

            // Thêm thông tin thanh toán
            $order->payments()->create([
                'amount_paid' => $amount_paid,
                'payment_method' => $paymentMethod,
                'payment_date' => $payment_date,
                'status' => 'successful'
            ]);

            // Cập nhật trạng thái đơn hàng nếu cần
            if ($order->payments()->sum('amount_paid') >= $order->total_amount) {
                $order->paid_at = now();
                $order->payment_status = 'paid';
                $order->save();
            } else {
                $order->payment_status = 'partially_paid';
                $order->paid_at = now();
                $order->save();
            }
            //log thanh toán
            $logService = app(OrderLogService::class);
            $logService->createLog([
                'order_id'   => $order->id,
                'action'     => "Thêm thanh toán",
                'note'       => "Đã thêm thanh toán cho đơn hàng: " . $order->order_code . ". Số tiền: " . $amount_paid,
                'file_id'    => null,
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Thanh toán đã được ghi nhận thành công.',
                'order_id' => $order->id,
                'new_payment_status' => $order->payment_status
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi thêm thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }
}
