<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ObjectTypeSeeder extends Seeder
{
 public function run(): void
    {
        $now = Carbon::now();

        $objectTypes = [
            [
                'code' => 'billing_cycle',
                'name' => 'Chu kỳ thanh toán',
                'description' => 'Các lựa chọn chu kỳ thanh toán áp dụng cho gói dịch vụ.',
                'values' => [
                    ['code' => 'monthly', 'name' => 'Hàng tháng', 'description' => 'Thanh toán mỗi tháng'],
                    ['code' => 'yearly', 'name' => 'Hàng năm', 'description' => 'Thanh toán mỗi năm'],
                    ['code' => 'one-time', 'name' => 'Một lần', 'description' => 'Thanh toán một lần duy nhất'],
                ]
            ],
            [
                'code' => 'feature_type',
                'name' => 'Loại tính năng',
                'description' => 'Kiểu tính năng áp dụng trong gói dịch vụ.',
                'values' => [
                    ['code' => 'quantity', 'name' => 'Số lượng', 'description' => 'Tính năng giới hạn số lượng sử dụng'],
                    ['code' => 'boolean', 'name' => 'Có/Không', 'description' => 'Tính năng chỉ có bật/tắt'],
                    ['code' => 'tiered', 'name' => 'Phân cấp', 'description' => 'Tính năng có nhiều cấp sử dụng'],
                ]
            ],
            [
                'code' => 'pricing_type',
                'name' => 'Loại giá',
                'description' => 'Phương thức áp dụng giá cho mỗi tính năng.',
                'values' => [
                    ['code' => 'free', 'name' => 'Miễn phí', 'description' => 'Không tính thêm chi phí'],
                    ['code' => 'included', 'name' => 'Đã bao gồm', 'description' => 'Đã nằm trong giá gói chính'],
                    ['code' => 'per_unit', 'name' => 'Theo đơn vị', 'description' => 'Tính phí theo từng đơn vị sử dụng'],
                    ['code' => 'tiered', 'name' => 'Phân cấp giá', 'description' => 'Mức giá thay đổi theo ngưỡng sử dụng'],
                ]
            ],
            [
                'code' => 'order_status',
                'name' => 'Trạng thái đơn hàng',
                'description' => 'Tình trạng xử lý của đơn hàng trong hệ thống.',
                'values' => [
                    ['code' => 'draft', 'name' => 'Nháp', 'description' => 'Đơn hàng chưa hoàn tất, đang soạn'],
                    ['code' => 'confirmed', 'name' => 'Đã xác nhận', 'description' => 'Đơn hàng đã được xác nhận bởi khách hàng'],
                    ['code' => 'paid', 'name' => 'Đã thanh toán', 'description' => 'Đơn hàng đã được thanh toán đầy đủ'],
                    ['code' => 'cancelled', 'name' => 'Đã hủy', 'description' => 'Đơn hàng đã bị hủy và không được xử lý'],
                ]
            ],
            [
                'code' => 'discount_type',
                'name' => 'Loại chiết khấu',
                'description' => 'Cách áp dụng chiết khấu cho giá sản phẩm/dịch vụ.',
                'values' => [
                    ['code' => 'percentage', 'name' => 'Phần trăm', 'description' => 'Giảm theo tỷ lệ phần trăm'],
                    ['code' => 'fixed', 'name' => 'Cố định', 'description' => 'Giảm theo một số tiền nhất định'],
                ]
            ],
            [
                'code' => 'service_type',
                'name' => 'Loại dịch vụ',
                'description' => 'Phân loại các gói dịch vụ theo danh mục chính',
                'values' => [
                    ['code' => 'SER_IHD', 'name' => 'Hóa đơn điện tử', 'description' => 'Hóa đơn điện tử'],
                    ['code' => 'SER_CA', 'name' => 'Chữ ký số', 'description' => 'Chữ ký số'],
                    ['code' => 'SER_COMBO', 'name' => 'Combo ', 'description' => 'Gồm nhiều dịch vụ'],
                ]
            ],
               [
                'code' => 'unit_type',
                'name' => 'Đơn vị tính',
                'description' => 'Đơn vị chuẩn đo lường số lượng dịch vụ (ví dụ: chứng thư, hóa đơn, thiết bị)',
                'values' => [
                    ['code' => 'DS', 'name' => 'Chữ ký số', 'description' => 'Digital Signature'],
                    ['code' => 'INV', 'name' => 'Hóa đơn điện tử', 'description' => 'Invoice'],
                    ['code' => 'DOC', 'name' => 'Tài liệu', 'description' => 'Document'],
                    ['code' => 'SMS', 'name' => 'Tin nhắn', 'description' => 'Short Message Service'],
                    ['code' => 'SP', 'name' => 'Gói dịch vụ', 'description' => 'Service Package'],
                    ['code' => 'DEV', 'name' => 'Thiết bị', 'description' => 'Device'], 
                    ['code' => 'GB', 'name' => 'Gigabyte', 'description' => 'Gigabyte'],
                ]
            ],
        ];

        foreach ($objectTypes as $type) {
            $typeId = (string) Str::uuid();
            DB::table('object_types')->insert([
                'id' => $typeId,
                'code' => $type['code'],
                'name' => $type['name'],
                'order' => null,
                'status' => 'active',
                'created_by' => null,
                'tenant_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($type['values'] as $index => $item) {
                DB::table('objects')->insert([
                    'id' => (string) Str::uuid(),
                    'object_type_id' => $typeId,
                    'code' => $item['code'],
                    'name' => $item['name'],
                    'parent_id' => null,
                    'order' => $index + 1,
                    'status' => 'active',
                    'created_by' => null,
                    'tenant_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
