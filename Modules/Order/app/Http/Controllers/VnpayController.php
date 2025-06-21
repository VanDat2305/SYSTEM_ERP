<?php


namespace Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Order\Models\App\Models\Payment;
use Modules\Order\Models\Order;

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

    // 1️⃣ API tạo link thanh toán VNPAY (trả về JSON)
    public function initiateVnpay(Request $request)
    {
        $request->validate([
            'order_code' => 'required|string',
        ]);
        $order = Order::where('order_code', $request->order_code)->first();
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }

        $txnRef = $order->order_code;
        $amount = (int)$order->total_amount * 100;
        $ip = $request->header('x-forwarded-for') ?? $request->ip();
        if ($ip === '127.0.0.1' || $ip === '::1') {
            // Lấy IP public mạng thật của bạn, hoặc hardcode để test:
            $ip = '171.241.108.110'; // Đổi thành IP thật hoặc 1 IP bất kỳ
        }

        $inputData = [
            'vnp_Version'    => '2.1.0',
            'vnp_Command'    => 'pay',
            'vnp_TmnCode'    => config('services.vnpay.tmncode'),   // dùng config, tránh hardcode!
            'vnp_Amount'     => $amount,
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_CurrCode'   => 'VND',
            'vnp_IpAddr'     => $ip,
            'vnp_Locale'     => 'vn',
            'vnp_OrderInfo'  => 'Thanh toan don hang ' . $txnRef,
            'vnp_OrderType'  => 'billpayment',
            'vnp_ReturnUrl'  => config('services.vnpay.return_url'), // config chuẩn
            'vnp_TxnRef'     => $txnRef,
            'vnp_IpnUrl'     => config('services.vnpay.ipn_url'),
        ];

        ksort($inputData);

        // Build hash string KHÔNG encode
        $hashDataArr = [];
        foreach ($inputData as $key => $value) {
            $hashDataArr[] = $key . '=' . $value;
        }
        $hashData = implode('&', $hashDataArr);

        // Build query string (để redirect), ĐƯỢC phép urlencode
        $queryArr = [];
        foreach ($inputData as $key => $value) {
            $queryArr[] = urlencode($key) . '=' . urlencode($value);
        }
        $query = implode('&', $queryArr);

        $vnpSecureHash = hash_hmac('sha512', $hashData, config('services.vnpay.hash_secret'));
        $paymentUrl = config('services.vnpay.url') . '?' . $query . '&vnp_SecureHash=' . $vnpSecureHash;
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


    // 2️⃣ Xử lý khi frontend SPA nhận return, gửi các query params lên để xác thực lại (nếu muốn)
    public function handleReturn(Request $request)
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
            }
            return response()->json(['success' => true, 'message' => 'Thanh toán thành công!']);
        }
        return response()->json(['success' => false, 'message' => 'Thanh toán thất bại hoặc sai chữ ký'], 400);
    }

    // 3️⃣ Nhận IPN từ VNPAY (server backend)
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
            }
            return response('{"RspCode":"00","Message":"Success"}');
        }
        return response('{"RspCode":"97","Message":"Invalid signature"}');
    }

    private function validateVnpSignature(Request $request)
    {
        $inputData = $request->except('vnp_SecureHash', 'vnp_SecureHashType');
        ksort($inputData);
        $hashData = '';
        foreach ($inputData as $key => $value) {
            $hashData .= $key . '=' . $value . '&';
        }
        $hashData = rtrim($hashData, '&');
        $secureHash = hash_hmac('sha512', $hashData, $this->vnpHashSecret);

        return $secureHash === $request->vnp_SecureHash;
    }
}
