<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderLogService;

class AutoCompleteOrder extends Command
{
    protected $signature = 'orders:auto-complete';
    protected $description = 'Tự động chuyển trạng thái đơn hàng từ processing sang completed khi đủ điều kiện';

    public function handle()
    {
        // Tìm các đơn hàng ở trạng thái processing và đã thanh toán, hợp đồng đã ký, dịch vụ đã kích hoạt
        $orders = Order::where('order_status', 'processing')
            ->where('payment_status', 'paid')
            ->where('contract_status', 'signed') // hoặc contract_status = exported/hoặc check file hợp đồng tùy hệ thống
            ->whereHas('details', function($q) {
                $q->where('is_active', true); // hoặc check từng detail tùy logic bạn
            })
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Không có đơn hàng nào đủ điều kiện chuyển trạng thái.');
            return;
        }

        foreach ($orders as $order) {
            // Nếu cần kiểm tra nhiều điều kiện chi tiết hơn ở order_details, kiểm tra tại đây
            $oldStatus = $order->order_status;
            $order->order_status = 'completed';
            $order->save();
            $logService = app(OrderLogService::class);
                $logService->createLog([
                    'order_id'   => $order->id,
                    'action'     => "Tự động chuyển trạng thái",
                    'note'       => "Hệ thống tự động chuyển đơn hàng từ [$oldStatus] sang [completed] do đã thanh toán, hợp đồng đã ký và dịch vụ đã kích hoạt.",
                    'file_id'    => null,
                    'old_status' => $oldStatus,
                    'new_status' => 'completed',
                ]);

                $this->info("Đã chuyển đơn hàng {$order->order_code} sang completed và ghi log.");
        }

        $this->info('Hoàn tất chuyển trạng thái các đơn hàng đủ điều kiện.');
    }
}
