<p>Chào {{ $customer_name }},</p>
<p>Các gói dịch vụ dưới đây của bạn đang sắp hết hạn/hết quota:</p>
<table border="1" cellpadding="6" cellspacing="0">
    <tr>
        <th>Tên gói</th>
        <th>Mã gói</th>
        <th>Ngày hết hạn</th>
        <th>Loại cảnh báo</th>
        <th>Mốc cảnh báo</th>
    </tr>
    @foreach($packages as $pkg)
    <tr>
        <td>{{ $pkg['package_name'] }}</td>
        <td>{{ $pkg['package_code'] }}</td>
        <td>{{ \Carbon\Carbon::parse($pkg['end_date'])->format('d/m/Y') }}</td>
        <td>
            @if($pkg['status'] == 'expired')
            Đã hết hạn/hết số lượng
            @elseif($pkg['status'] == 'warning')
            Sắp hết hạn hoặc sắp hết số lượng
            @else
            --
            @endif
        </td>
        <!-- Có thể không hiển thị milestone hoặc dùng mô tả tiếng Việt cho từng mốc -->
        <td>
            @switch($pkg['milestone'])
            @case('low_quota')
            Sắp hết số lượng
            @break
            @case('quota_0')
            @case('expired')
            Đã hết số lượng
            @break
            @case('30days')
            Còn 30 ngày hết hạn
            @break
            @case('7days')
            Còn 7 ngày hết hạn
            @break
            @case('1day')
            Còn 1 ngày hết hạn
            @break
            @default
            --
            @endswitch
        </td>
    </tr>
    @endforeach

</table>
<p>Vui lòng kiểm tra và gia hạn kịp thời để tránh gián đoạn dịch vụ.</p>
<p>Trân trọng,<br>Đội ngũ CSKH</p>