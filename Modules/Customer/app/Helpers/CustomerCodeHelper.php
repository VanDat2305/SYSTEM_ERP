<?php

namespace Modules\Customer\Helpers;

use Illuminate\Support\Facades\DB;

class CustomerCodeHelper
{
     /**
     * Sinh customer_code: KHYYMMxxxxx (VD: KH24060001)
     * - KH: prefix
     * - YYMM: năm, tháng (ví dụ 2406 là tháng 6 năm 2024)
     * - xxxxx: số tự tăng theo tháng, luôn đủ số 0
     */
    public static function generate($prefix = 'KH')
    {
        $ym = date('ym'); // '2406' cho tháng 6 năm 2024
        $base = $prefix . $ym;

        // Lấy customer_code lớn nhất trong tháng hiện tại
        $lastCode = DB::table('customers')
            ->where('customer_code', 'like', $base . '%')
            ->orderByDesc('customer_code')
            ->value('customer_code');

        if ($lastCode) {
            $number = (int) substr($lastCode, strlen($base));
            $newNumber = $number + 1;
        } else {
            $newNumber = 1;
        }

        // Format lại mã, ví dụ KH24060001
        return $base . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }
}
