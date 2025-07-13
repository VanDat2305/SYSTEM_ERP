<?php

namespace Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Order\Models\OrderDetail;
use Modules\Order\Models\OrderPackageFeature;

class QuotaController extends Controller
{
    /**
     * API kiểm tra quota còn lại
     * GET /api/quota/check?order_detail_id=xxx&feature_key=xxx
     */
    public function check(Request $request)
    {
        $request->validate([
            'order_detail_id' => 'required|uuid',
            'feature_key' => 'required|string'
        ]);

        $feature = OrderPackageFeature::where('order_detail_id', $request->order_detail_id)
            ->where('feature_key', $request->feature_key)
            ->firstOrFail();

        $quantity = $feature->orderDetail->quantity ?? 1;
        $totalQuota = $feature->limit_value * $quantity;
        $remain = $totalQuota - $feature->used_count;

        return response()->json([
            'total_quota' => $totalQuota,
            'used_count'  => $feature->used_count,
            'remain'      => $remain,
            'is_active'   => $feature->is_active,
            'end_date'    => $feature->orderDetail->end_date
        ]);
    }

    /**
     * API tiêu hao quota (hệ thống con gọi sau khi phát sinh nghiệp vụ thành công)
     * POST /api/quota/use
     * Body: { "order_detail_id": "...", "feature_key": "...", "amount": 1 }
     */
    public function use(Request $request)
    {
        $data = $request->validate([
            'order_detail_id' => 'required|uuid',
            'feature_key'     => 'required|string',
            'amount'          => 'required|numeric|min:1'
        ]);

        DB::transaction(function() use ($data) {
            $feature = OrderPackageFeature::lockForUpdate()
                ->where('order_detail_id', $data['order_detail_id'])
                ->where('feature_key', $data['feature_key'])
                ->firstOrFail();

            // Trường hợp feature là thời hạn sử dụng (duration)
            if ($feature->feature_key === 'duration') {
                // Kiểm tra hết hạn hay chưa
                $now = Carbon::now();
                if ($feature->detail->end_date && $now > $feature->detail->end_date) {
                    abort(400, 'Gói đã hết hạn, không thể sử dụng.');
                }
                // Không tăng used_count và không trừ quota
                return;
            }

            // Các feature còn lại kiểu 'quantity'
            $quantity = $feature->detail->quantity ?? 1;
            $totalQuota = $feature->limit_value * $quantity;

            if ($feature->used_count + $data['amount'] > $totalQuota) {
                abort(400, 'Số lượng sử dụng vượt quá cho phép.');
            }
            $feature->used_count += $data['amount'];
            $feature->save();
        });

        return response()->json(['status' => 'ok']);
    }


    /**
     * API lấy danh sách gói sắp hết hạn/quota cho cảnh báo
     * GET /api/quota/warning
     */
    public function warning()
    {
        $now = Carbon::now();

        $expiring = OrderDetail::where('is_active', true)
            ->whereDate('end_date', '<=', $now->copy()->addDays(30))
            ->whereDate('end_date', '>=', $now)
            ->get();

        $lowQuota = OrderPackageFeature::where('is_active', true)
            ->get()
            ->filter(function($feature) {
                $quantity = $feature->orderDetail->quantity ?? 1;
                $totalQuota = $feature->limit_value * $quantity;
                return $totalQuota > 0 && ($totalQuota - $feature->used_count) / $totalQuota < 0.1;
            });

        return response()->json([
            'expiring' => $expiring,
            'low_quota'=> $lowQuota->values(),
        ]);
    }
}
