<?php

namespace Modules\Order\Services;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Order\Interfaces\OrderRepositoryInterface;
use Modules\Order\Interfaces\OrderDetailRepositoryInterface;
use Modules\Order\Interfaces\OrderPackageFeatureRepositoryInterface;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderDetail;

class OrderDetailService
{
    /**
     * CÔNG THỨC TÍNH GÓI SẮP HẾT HẠN
     * 
     * Điều kiện: OR logic (chỉ cần thỏa mãn 1 trong 2 điều kiện)
     */

    // 1. SẮP HẾT QUOTA - Dựa vào order_package_features
    function isQuotaNearExpiry($orderDetail)
    {
        foreach ($orderDetail->features as $feature) {
            // Chỉ check những feature có giới hạn số lượng
            if (
                $feature->feature_type == 'quantity' &&
                $feature->limit_value > 0 &&
                $feature->used_count !== null
            ) {

                $totalQuota = $feature->limit_value * $orderDetail->quantity;
                $usedQuota = $feature->used_count;
                $remainQuota = $totalQuota - $usedQuota;

                // Nếu quota còn lại < 10% tổng quota
                if (($remainQuota / $totalQuota) < 0.1) {
                    return true;
                }
            }
        }
        return false;
    }

    // 2. SẮP HẾT THỜI GIAN - Dựa vào order_details
    function isTimeNearExpiry($orderDetail)
    {
        if (!$orderDetail->end_date) {
            return false;
        }

        $endDate = Carbon::parse($orderDetail->end_date);
        $now = now();

        // Nếu chưa hết hạn và còn < 60 ngày
        return $endDate->isFuture() && $endDate->diffInDays($now) < 60;
    }

    // CÔNG THỨC TỔNG HỢP
    function getPackageStatus($orderDetail)
    {
        // Thứ tự ưu tiên kiểm tra

        // 1. Đã được gia hạn
        $renewedDetailIds = OrderDetail::whereNotNull('renewed_from_detail_id')
            ->pluck('renewed_from_detail_id')->toArray();

        if (in_array($orderDetail->id, $renewedDetailIds)) {
            return 'renewed';
        }

        // 2. Đã hết hạn (quota hoặc thời gian)
        if ($this->isQuotaExpired($orderDetail) || $this->isTimeExpired($orderDetail)) {
            return 'expired';
        }

        // 3. Sắp hết hạn (WARNING)
        if ($this->isQuotaNearExpiry($orderDetail) || $this->isTimeNearExpiry($orderDetail)) {
            return 'warning';
        }

        // 4. Hoạt động bình thường
        return 'active';
    }

    // Hàm hỗ trợ kiểm tra hết hạn
    function isQuotaExpired($orderDetail)
    {
        foreach ($orderDetail->features as $feature) {
            if (
                $feature->feature_type == 'quantity' &&
                $feature->limit_value > 0 &&
                $feature->used_count !== null
            ) {

                $totalQuota = $feature->limit_value * $orderDetail->quantity;
                $usedQuota = $feature->used_count;

                if ($usedQuota >= $totalQuota) {
                    return true;
                }
            }
        }
        return false;
    }

    function isTimeExpired($orderDetail)
    {
        if (!$orderDetail->end_date) {
            return false;
        }

        return Carbon::parse($orderDetail->end_date)->isPast();
    }

    /**
     * ĐIỀU KIỆN CHI TIẾT GÓI SẮP HẾT HẠN:
     * 
     * 1. SẮP HẾT QUOTA (< 10%):
     *    - feature_type = 'quantity'
     *    - limit_value > 0
     *    - used_count NOT NULL
     *    - (limit_value * quantity - used_count) / (limit_value * quantity) < 0.1
     * 
     * 2. SẮP HẾT THỜI GIAN (< 60 ngày):
     *    - end_date NOT NULL
     *    - end_date > NOW() (chưa hết hạn)
     *    - DATEDIFF(end_date, NOW()) < 60
     * 
     * CÁC TRƯỜNG HỢP ĐẶC BIỆT:
     * - Nếu feature_type = 'boolean': không tính quota
     * - Nếu limit_value = NULL hoặc 0: không giới hạn quota
     * - Nếu used_count = NULL: chưa sử dụng (= 0)
     * - Nếu start_date > NOW(): gói chưa bắt đầu
     */
}
