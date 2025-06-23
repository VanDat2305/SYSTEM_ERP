<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>HÓA ĐƠN GIÁ TRỊ GIA TĂNG</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 13px;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
        }

        .vat-invoice {
            font-size: 14px;
        }

        .info-table,
        .goods-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 2px 4px;
        }

        .goods-table th,
        .goods-table td {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
        }

        .goods-table th {
            background: #f2f2f2;
        }

        .summary-table td {
            padding: 2px 4px;
            border: 1px solid #000;
        }

        .signature {
            text-align: center;
            padding-top: 20px;
        }

        .qr {
            position: absolute;
            top: 22px;
            right: 26px;
        }

        .logo {
            float: left;
        }

        .clearfix {
            clear: both;
        }

        .red {
            color: red;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">
            {{-- <img src="{{ public_path('images/logo_efy.png') }}" style="height:60px;"> --}}
        </div>
        <div>
            <div class="title">HÓA ĐƠN GIÁ TRỊ GIA TĂNG</div>
            <div class="vat-invoice">(VAT INVOICE)</div>
            <div><i>(Bản thể hiện của hóa đơn điện tử)</i></div>
            <div>
                <span>Ký hiệu (Serial No): <b>{{ $order->invoice_serial ?? '1K25TEY' }}</b></span> &nbsp;&nbsp;
                <span>Số (No): <span class="red">{{ $order->invoice_number ?? '10523' }}</span></span>
            </div>
            <div>
                Ngày (Date): <b>{{ \Carbon\Carbon::parse($order->invoice_exported_at ?? now())->format('d') }}</b>
                tháng <b>{{ \Carbon\Carbon::parse($order->invoice_exported_at ?? now())->format('m') }}</b>
                năm <b>{{ \Carbon\Carbon::parse($order->invoice_exported_at ?? now())->format('Y') }}</b>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>

    <table class="info-table">
        <tr>
            <td colspan="6"><b>CÔNG TY CỔ PHẦN CÔNG NGHỆ TIN HỌC EFY VIỆT NAM</b></td>
        </tr>
        <tr>
            <td>Mã số thuế:</td>
            <td colspan="2">0102519041</td>
            <td>Điện thoại:</td>
            <td colspan="2">(+84) 2462872290</td>
        </tr>
        <tr>
            <td>Địa chỉ:</td>
            <td colspan="5">Tầng 9, tòa nhà Sannam, số 78 phố Duy Tân, Dịch Vọng Hậu, Cầu Giấy, TP. Hà Nội</td>
        </tr>
        <tr>
            <td>Email:</td>
            <td colspan="2">Contact@efy.com.vn</td>
            <td>Website:</td>
            <td colspan="2">efy.com.vn</td>
        </tr>

    </table>

    <hr>
    <table class="info-table">
        <tr>
            <td><b>Họ tên người mua :</b></td>
            <td colspan="5">{{ $order->customer->customer_type == 'INDIVIDUAL' ? $order->customer->full_name :
                $order->customer->representatives[0]->full_name }}</td>
        </tr>
        <tr>
            <td>Tên đơn vị :</td>
            <td colspan="5">{{ $order->customer->customer_type == 'ORGANIZATION' ? $order->customer->full_name : '' }}
            </td>
        </tr>
        <tr>
            <td>Mã số thuế :</td>
            <td colspan="2">{{ $order->customer->tax_code }}</td>
        <tr>
            <td>Địa chỉ :</td>
            <td colspan="5">{{ $order->customer->address }}</td>
        </tr>
        <tr>
            <td>Hình thức thanh toán:</td>
            <td colspan="2">{{ $order->payment_method }}</td>
        </tr>
    </table>

    <br>
    <table class="goods-table">
        <thead>
            <tr>
                <th>STT</th>
                <th>Tên hàng hóa, dịch vụ</th>
                <th>Số lượng</th>
                <th>Đơn giá</th>
                <th>Thuế suất</th>
                <th>Tiền thuế</th>
                <th>Thành tiền chưa thuế</th>
                <th>Thành tiền có thuế</th>
            </tr>
        </thead>
        <tbody>
            @php
            $sum_price = 0;
            $sum_tax = 0;
            $sum_with_tax = 0;
            @endphp
            @foreach($order->details as $i => $item)
            @php
             
            // Trường hợp giá đã gồm thuế thì cần xử lý ngược lại nếu muốn hiển thị đúng
            $tax_rate = floatval($item->tax_rate ?? 0);
            $tax_included = $item->tax_included ?? false;
            // Tổng trước thuế đã có
            $total_price = $item->total_price;
            if ($tax_included && $tax_rate > 0) {
                $price_excl_tax = round($total_price / (1 + $tax_rate/100), 2);
                $tax_amount = $total_price - $price_excl_tax;
                $total_with_tax = $total_price;
            } else {
                $price_excl_tax = $total_price;
                $tax_amount = $tax_rate > 0 ? round($total_price * $tax_rate / 100, 2) : 0;
                $total_with_tax = $total_price + $tax_amount;
            }

            $sum_price += $total_price;
            $sum_tax += $tax_amount;
            $sum_with_tax += $total_with_tax;
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td style="text-align:left;">{{ $item->package_name }}</td>
                <td>{{ $item->quantity }}</td>
                <td style="text-align:right;">{{ number_format($item->base_price) }}</td>
                <td>{{ $tax_rate > 0 ? $tax_rate . '%' : 'KCT' }}</td>
                <td style="text-align:right;">{{ number_format($tax_amount) }}</td>
                <td style="text-align:right;">{{ number_format($total_price) }}</td>
                <td style="text-align:right;">{{ number_format($total_with_tax) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right;"><b>Tổng cộng:</b></td>
                <td style="text-align:right;"><b>{{ number_format($sum_tax) }}</b></td>
                <td style="text-align:right;"><b>{{ number_format($sum_price) }}</b></td>
                <td style="text-align:right;"><b>{{ number_format($sum_with_tax) }}</b></td>
            </tr>
        </tfoot>
    </table>

    <div class="signature" style="margin-top: 25px;">
        <table style="width: 100%; text-align:center;">
            <tr>
                <td colspan="1">
                    Người mua hàng<br>
                    <span style="font-size:12px; font-style:italic;">(Ký, ghi rõ họ, tên)</span>
                </td>
                <td colspan="1">
                    Người bán hàng<br>
                    <span style="font-size:12px; font-style:italic;">(Ký, ghi rõ họ, tên)</span>
                </td>
            </tr>
            <tr>
                <td style="height: 60px;" colspan="1"></td>
                <td colspan="1">
                    <i>Ký bởi: CÔNG TY CỔ PHẦN CÔNG NGHỆ TIN HỌC<br> EFY VIỆT NAM</i><br>
                    <span style="color:green;">Ký ngày: {{ \Carbon\Carbon::parse($order->invoice_exported_at ??
                        now())->format('d/m/Y H:i:s') }}</span>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>