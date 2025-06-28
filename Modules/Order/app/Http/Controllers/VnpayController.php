<?php

namespace Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Order\Models\Payment;
use Modules\Order\Models\Order;
use Modules\Order\Services\InvoiceService;
use Modules\Order\Services\OrderLogService;
use Illuminate\Support\Facades\DB;
use Modules\Order\Services\OrderService;

class VnpayController extends Controller
{
    // Dùng config/services.php để cấu hình VNPAY
    protected $vnpUrl;
    protected $vnpTmnCode;
    protected $vnpHashSecret;
    protected $vnpReturnUrl;
    protected $vnpIpnUrl;

    public function __construct()
    {
        $this->vnpUrl = config('services.vnpay.url');
        $this->vnpTmnCode = config('services.vnpay.tmncode');
        $this->vnpHashSecret = config('services.vnpay.hash_secret');
        $this->vnpReturnUrl = config('services.vnpay.return_url');
        $this->vnpIpnUrl = config('services.vnpay.ipn_url');
    }

    public function initiateVnpay(Request $request)
    {
        $request->validate([
            'order_code' => 'required|string',
        ]);
        $order = Order::where('order_code', $request->order_code)
            ->where('payment_status', '!=', 'paid')
            ->whereNotIN('order_status', ['cancelled', 'draft', 'pending'])
            ->first();
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }

        $txnRef = $order->order_code;
        $amount = (int)$order->total_amount * 100;
        $ip = $request->header('x-forwarded-for') ?? $request->ip();
        if ($ip === '127.0.0.1' || $ip === '::1') {
            $ip = '171.241.108.110'; // Đổi thành IP public thật khi test
        }

        $inputData = [
            'vnp_Version'    => '2.1.0',
            'vnp_Command'    => 'pay',
            'vnp_TmnCode'    => $this->vnpTmnCode,
            'vnp_Amount'     => $amount,
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_CurrCode'   => 'VND',
            'vnp_IpAddr'     => $ip,
            'vnp_Locale'     => 'vn',
            'vnp_OrderInfo'  => 'Thanh toán đơn hàng ' . $txnRef,
            'vnp_OrderType'  => 'billpayment',
            'vnp_ReturnUrl'  => $this->vnpReturnUrl,
            'vnp_TxnRef'     => $txnRef,
        ];

        if ($request->filled('bank_code')) {
            $inputData['vnp_BankCode'] = $request->bank_code;
        }


        ksort($inputData);

        // Build hashData (urlencode từng key và value)
        $hashData = '';
        $query = '';
        $i = 0;
        foreach ($inputData as $key => $value) {
            $item = urlencode($key) . "=" . urlencode($value);
            $hashData .= ($i == 0) ? $item : '&' . $item;
            $query .= $item . '&';
            $i++;
        }

        // Sinh chữ ký
        $vnpSecureHash = hash_hmac('sha512', $hashData, $this->vnpHashSecret);

        // Build URL
        $paymentUrl = $this->vnpUrl . '?' . $query . 'vnp_SecureHash=' . $vnpSecureHash;

        Log::info('VNPAY_DEBUG', [
            'inputData' => $inputData,
            'hashData'  => $hashData,
            'query'     => $query,
            'paymentUrl' => $paymentUrl
        ]);

        return response()->json([
            'payment_url' => $paymentUrl
        ]);
    }

    public function handleReturn(Request $request)
    {
        DB::beginTransaction();
        try {


            $isValid = $this->validateVnpSignature($request);
            $order = Order::where('order_code', $request->vnp_TxnRef)->first();
            $frontendUrl = config("app.frontend_url") . '/payment/vnpay-result';
            $query = http_build_query([
                'order_code' => $request->vnp_TxnRef,
                'status' => ($isValid && $request->vnp_ResponseCode == '00' && $order) ? 'success' : 'fail',
                'amount' => isset($request->vnp_Amount) ? ($request->vnp_Amount / 100) : null
            ]);

            // update order status if valid
            if ($isValid && $request->vnp_ResponseCode == '00' && $order) {
                if ($order->payment_status !== 'paid') {
                    $order->update([
                        'payment_status' => 'paid',
                        'payment_method' => 'vnpay',
                        'paid_at' => now()
                    ]);
                    Payment::create([
                        'id' => Str::uuid(),
                        'order_id' => $order->id,
                        'amount_paid' => $request->vnp_Amount / 100,
                        'payment_method' => 'vnpay',
                        'status' => 'successful',
                        'payment_reference' => $request->vnp_TxnRef,
                        'payment_date' => now(),
                        'raw_response' => json_encode($request->all())
                    ]);
                    // tu dong kich hoat cac dich vu
                    $logService = app(OrderLogService::class);
                    $logService->createLog([
                        'order_id'   => $order->id,
                        'action'     => "Thanh toán vnpay",
                        'note'       => "Đã thanh toán đơn hàng qua VNPAY: " . $order->order_code . "Số tiền: " . ($request->vnp_Amount / 100),
                        'file_id'    => null,
                        'user_name' => 'Khách hàng'
                    ]);
                    app(OrderService::class)->activateOrderWithDynamicServices($order->id);
                }
            }

            // Log để debug với thông tin chi tiết
            Log::info('VNPAY_RETURN_DEBUG', [
                'isValid' => $isValid,
                'responseCode' => $request->vnp_ResponseCode,
                'order_exists' => !!$order,
                'receivedHash' => $request->vnp_SecureHash,
                'allParams' => $request->all()
            ]);
            DB::commit();

            return redirect()->to($frontendUrl . '?' . $query);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('VNPAY_RETURN_ERROR', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return redirect()->to(config("app.frontend_url") . '/payment/vnpay-result?status=fail&message=' . urlencode($e->getMessage()));
        }
    }

    public function handleIpn(Request $request)
    {
        $isValid = $this->validateVnpSignature($request);
        $order = Order::where('order_code', $request->vnp_TxnRef)->first();

        if ($isValid && $request->vnp_ResponseCode == '00' && $order) {
            if ($order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_method' => 'vnpay',
                    'paid_at' => now()
                ]);
                Payment::create([
                    'id' => Str::uuid(),
                    'order_id' => $order->id,
                    'amount_paid' => $request->vnp_Amount / 100,
                    'payment_method' => 'vnpay',
                    'status' => 'successful',
                    'payment_reference' => $request->vnp_TxnRef,
                    'payment_date' => now(),
                    'raw_response' => json_encode($request->all())
                ]);
                //gui mail hoa don 
                $invoiceService = app(InvoiceService::class);
                $invoiceService->sendInvoiceToCustomer($order);
            }
            return response('{"RspCode":"00","Message":"Success"}');
        }
        return response('{"RspCode":"97","Message":"Invalid signature"}');
    }

    private function validateVnpSignature(Request $request)
    {
        // Loại bỏ vnp_SecureHash và vnp_SecureHashType
        $inputData = $request->except(['vnp_SecureHash', 'vnp_SecureHashType']);

        // Loại bỏ các tham số rỗng
        $inputData = array_filter($inputData, function ($value) {
            return $value !== null && $value !== '';
        });

        // Sắp xếp theo key tăng dần
        ksort($inputData);

        // Tạo hash data theo chuẩn VNPAY - URL encode như khi gửi request
        $hashData = '';
        $i = 0;
        foreach ($inputData as $key => $value) {
            // VNPAY yêu cầu URL encode cả key và value khi validate
            $item = urlencode($key) . "=" . urlencode($value);
            $hashData .= ($i == 0) ? $item : '&' . $item;
            $i++;
        }

        // Tạo secure hash
        $secureHash = hash_hmac('sha512', $hashData, $this->vnpHashSecret);

        // Log để debug chi tiết
        Log::info('VNPAY_VALIDATE_DEBUG', [
            'inputData' => $inputData,
            'hashData' => $hashData,
            'calculatedHash' => $secureHash,
            'receivedHash' => $request->vnp_SecureHash,
            'isValid' => $secureHash === $request->vnp_SecureHash,
            'hashSecret' => substr($this->vnpHashSecret, 0, 5) . '***' // Chỉ log 5 ký tự đầu để bảo mật
        ]);

        return $secureHash === $request->vnp_SecureHash;
    }
}
