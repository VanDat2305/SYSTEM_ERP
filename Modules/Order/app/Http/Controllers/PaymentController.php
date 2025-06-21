<?php


namespace Modules\Order\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Order\Models\App\Models\Payment;
use Modules\Order\Models\Order;

class PaymentController extends Controller
{
    /**
     * Xử lý khởi tạo thanh toán theo phương thức được chọn
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'order_code' => 'required|string',
            'method' => 'required|in:cash,vnpay,bank_transfer'
        ]);

        $order = Order::where('order_code', $request->order_code)->first();

        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Đơn hàng đã được thanh toán'], 400);
        }

        switch ($request->method) {
            case 'vnpay':
                return redirect()->to("/vnpay/create/{$order->id}");

            case 'cash':
                return $this->processManualPayment($order, 'cash');

            case 'bank_transfer':
                return $this->processManualPayment($order, 'bank_transfer');
        }

        return response()->json(['message' => 'Phương thức không hỗ trợ'], 400);
    }
    public function methods()
    {
        return response()->json([
            'methods' => [
                'vnpay' => 'Thanh toán qua VNPay',
                'cash' => 'Thanh toán tiền mặt',
                'bank_transfer' => 'Chuyển khoản ngân hàng'
            ]
        ]);
    }
    public function findByCode(string $order_code)
    {
        $order = Order::with([])->where('order_code', $order_code)->select(
            'id', 'order_code', 'customer_id', 'total_amount', 'payment_status', 'created_at'
        )->first();

        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }

        return response()->json($order->load(['customer', 'details.features', 'team']));
    }


    /**
     * Ghi nhận thanh toán thủ công: tiền mặt, chuyển khoản
     */
    private function processManualPayment(Order $order, string $method)
    {
        $order->update([
            'payment_status' => 'paid',
            'payment_method' => $method,
            'paid_at' => now(),
        ]);

        Payment::create([
            'id' => Str::uuid(),
            'order_id' => $order->id,
            'amount_paid' => $order->total_amount,
            'payment_method' => $method,
            'status' => 'successful',
            'payment_date' => now(),
        ]);

        return response()->json(['message' => 'Đã ghi nhận thanh toán thành công']);
    }
}
