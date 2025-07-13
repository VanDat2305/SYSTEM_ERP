<?php

namespace Modules\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountServiceController extends Controller
{
    /**
     * Lấy danh sách dịch vụ/gói mà account (khách hàng) đã mua/kích hoạt
     * Route: GET /api/account/services
     * Middleware: auth:account_api (đảm bảo đã login bằng account và có token Sanctum)
     */
    public function services(Request $request)
    {
        // 1. Lấy account hiện tại
        $account = $request->user();
        $erpCustomerId = $account->erp_customer_id;

        if (!$erpCustomerId) {
            return response()->json(['error' => 'Account chưa gắn erp_customer_id'], 400);
        }

        // 2. Lấy các order còn hiệu lực (không cancelled, không xóa mềm)
        $orders = \Modules\Order\Models\Order::query()
            ->where('customer_id', $erpCustomerId)
            ->whereNotIn('order_status', ['cancelled', 'draft'])
            ->whereNull('deleted_at')
            ->pluck('id');

        // 3. Lấy order_details còn hiệu lực, không xóa mềm
        $orderDetails = \Modules\Order\Models\OrderDetail::query()
            ->whereIn('order_id', $orders)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderByDesc('start_date')
            ->get();

        // 4. Gom các dịch vụ theo service_type
        $services = [];
        foreach ($orderDetails as $detail) {
            // Lấy các features (không xóa mềm)
            $features = \Modules\Order\Models\OrderPackageFeature::query()
                ->where('order_detail_id', $detail->id)
                ->whereNull('deleted_at')
                ->get();

            $packageFeatures = [];
            foreach ($features as $f) {
                $quantity = $detail->quantity ?? 1;
                $totalQuota = $f->limit_value * $quantity;
                $remain = $totalQuota - $f->used_count;

                $packageFeatures[] = [
                    'feature_key'   => $f->feature_key,
                    'feature_name'  => $f->feature_name,
                    'feature_type'  => $f->feature_type,
                    'unit'          => $f->unit,
                    'total_quota'   => $totalQuota,
                    'used_count'    => $f->used_count,
                    'remain'        => $remain,
                    'is_active'     => $f->is_active,
                ];
            }

            $type = $detail->service_type;
            if (!isset($services[$type])) {
                $services[$type] = [
                    'service_type' => $type,
                    'service_name' => $type, // Nếu có bảng dịch vụ thì JOIN để lấy tên đầy đủ
                    'packages' => []
                ];
            }

            $services[$type]['packages'][] = [
                'order_detail_id' => $detail->id,
                'package_code'    => $detail->package_code,
                'package_name'    => $detail->package_name,
                'start_date'      => $detail->start_date,
                'end_date'        => $detail->end_date,
                'is_active'       => $detail->is_active,
                'features'        => $packageFeatures,
            ];
        }

        $services = array_values($services);

        return response()->json([
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'erp_customer_id' => $erpCustomerId,
            ],
            'services' => $services,
        ]);
    }

}