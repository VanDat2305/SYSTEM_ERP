<?php

// app/Console/Commands/NotifyPackageExpiry.php
namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\PackageNotifyLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Modules\Customer\Models\CustomerContact;
use Modules\Order\Models\OrderDetail;

class NotifyPackageExpiry extends Command
{
    protected $signature = 'notify:package-expiry';
    protected $description = 'Gửi cảnh báo gói sắp hết hạn/hết hạn cho khách hàng';

    public function handle()
    {
        // Bước 1: Gom các gói cảnh báo theo khách hàng
        $orderDetails = OrderDetail::with(['features', 'order.customer'])
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('is_active', true)
            ->whereNull('renewed_from_detail_id')
            ->get();

        $warnings = []; // [customer_id => [gói1, gói2,...]]

        foreach ($orderDetails as $detail) {
            $milestone = $this->getMilestone($detail);
            if (!$milestone) continue;

            $customer = $detail->order->customer;
            $email = $this->getCustomerEmail($customer->id);
            if (!$email) continue;

            // Kiểm tra đã gửi chưa (1 gói chỉ gửi 1 lần)
            if (!$this->shouldSend($detail->id, $customer->id, $milestone['type'], $milestone['milestone'])) continue;

            $warnings[$customer->id]['customer'] = $customer;
            $warnings[$customer->id]['email'] = $email;
            $warnings[$customer->id]['details'][] = [
                'package_name' => $detail->package_name,
                'package_code' => $detail->package_code,
                'end_date'     => $detail->end_date,
                'status'       => $milestone['type'],
                'milestone'    => $milestone['milestone'],
            ];

            // Đánh dấu đã gửi (để không gửi trùng nếu nhiều gói cùng loại/mốc)
            \App\Models\PackageNotifyLog::create([
                'order_detail_id' => $detail->id,
                'customer_id'     => $customer->id,
                'type'            => $milestone['type'],
                'milestone'       => $milestone['milestone'],
                'sent_at'         => now(),
            ]);
        }

        // Bước 2: Gửi 1 email tổng hợp cho mỗi khách hàng
        foreach ($warnings as $item) {
            $data = [
                'customer_name' => $item['customer']->full_name ?: $item['customer']->short_name,
                'packages' => $item['details'],
            ];
            try {
                Mail::send('emails.package_expiry', $data, function ($message) use ($item) {
                    $message->to($item['email'])->subject('[Cảnh báo] Các gói dịch vụ sắp hết hạn/hết quota');
                });
                $this->info("Đã gửi email tổng hợp cho {$item['email']}");
            } catch (\Exception $e) {
                $this->error("Lỗi gửi mail {$item['email']}: " . $e->getMessage());
            }
        }

        $this->info('Kết thúc gửi cảnh báo.');
    }


    // Tìm milestone hiện tại cần cảnh báo
    protected function getMilestone($orderDetail)
    {
        $feature = $orderDetail->features->first();
        $now = Carbon::now();
        $end = $orderDetail->end_date ? Carbon::parse($orderDetail->end_date) : null;
        $type = null;
        $milestone = null;
        $subject = null;

        // Gói theo thời gian (duration)
        if ($feature && $feature->feature_key == 'duration') {
            if ($end && $end->isPast()) {
                return [
                    'type' => 'expired',
                    'milestone' => 'expired',
                    'subject' => '[Thông báo] Gói dịch vụ đã hết hạn'
                ];
            }
            $diff = $now->diffInDays($end, false);
            if ($diff == 30) {
                return [
                    'type' => 'warning',
                    'milestone' => '30days',
                    'subject' => '[Cảnh báo] Gói dịch vụ còn 30 ngày nữa sẽ hết hạn'
                ];
            }
            if ($diff == 7) {
                return [
                    'type' => 'warning',
                    'milestone' => '7days',
                    'subject' => '[Cảnh báo] Gói dịch vụ còn 7 ngày nữa sẽ hết hạn'
                ];
            }
            if ($diff == 1) {
                return [
                    'type' => 'warning',
                    'milestone' => '1day',
                    'subject' => '[Cảnh báo] Gói dịch vụ còn 1 ngày nữa sẽ hết hạn'
                ];
            }
            return null;
        }

        // Gói có quota
        $totalQuota = $feature ? ($feature->limit_value * $orderDetail->quantity) : null;
        $used = $feature ? $feature->used_count : null;
        $remain = $feature ? ($totalQuota - $used) : null;

        if (($remain !== null && $remain <= 0) || ($end && $end->isPast())) {
            return [
                'type' => 'expired',
                'milestone' => 'quota_0',
                'subject' => '[Thông báo] Gói dịch vụ đã hết chỉ tiêu/quá hạn'
            ];
        }
        // Mốc cảnh báo quota
        if ($totalQuota && $remain !== null && $totalQuota > 0) {
            if (($remain / $totalQuota) <= 0.1 && ($remain / $totalQuota) > 0) {
                return [
                    'type' => 'warning',
                    'milestone' => 'sắp hết số lượng',
                    'subject' => '[Cảnh báo] Gói dịch vụ sắp hết chỉ tiêu sử dụng'
                ];
            }
            if (($remain / $totalQuota) == 0) {
                return [
                    'type' => 'expired',
                    'milestone' => 'quota_0',
                    'subject' => '[Thông báo] Gói dịch vụ đã sử dụng hết chỉ tiêu'
                ];
            }
        }
        // Mốc thời gian như gói duration
        $diff = $end ? $now->diffInDays($end, false) : null;
        if ($diff === 30) {
            return [
                'type' => 'warning',
                'milestone' => '30days',
                'subject' => '[Cảnh báo] Gói dịch vụ còn 30 ngày nữa sẽ hết hạn'
            ];
        }
        if ($diff === 7) {
            return [
                'type' => 'warning',
                'milestone' => '7days',
                'subject' => '[Cảnh báo] Gói dịch vụ còn 7 ngày nữa sẽ hết hạn'
            ];
        }
        if ($diff === 1) {
            return [
                'type' => 'warning',
                'milestone' => '1day',
                'subject' => '[Cảnh báo] Gói dịch vụ còn 1 ngày nữa sẽ hết hạn'
            ];
        }
        return null;
    }

    // Kiểm tra đã gửi chưa
    protected function shouldSend($orderDetailId, $customerId, $type, $milestone)
    {
        return !PackageNotifyLog::where([
            'order_detail_id' => $orderDetailId,
            'customer_id'     => $customerId,
            'type'            => $type,
            'milestone'       => $milestone,
        ])->exists();
    }

    protected function getCustomerEmail($customerId)
    {
        $contact = CustomerContact::where('customer_id', $customerId)
            ->where('contact_type', 'email')
            ->where('is_primary', true)
            ->first();
        return $contact ? $contact->value : null;
    }
}
