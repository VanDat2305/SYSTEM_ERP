<p>Xin chào {{ $order->customer->full_name ?? 'Quý khách' }},</p>

<p>Bạn nhận được hợp đồng cho đơn hàng <strong>{{ $order->order_code }}</strong>.</p>

@if(isset($order->contract_number))
<p><b>Số hợp đồng:</b> {{ $order->contract_number }}</p>
@endif

@if(isset($order->contract_date))
<p><b>Ngày hợp đồng:</b> {{ \Carbon\Carbon::parse($order->contract_date)->format('d/m/Y') }}</p>
@endif

<p>Vui lòng kiểm tra file hợp đồng đính kèm trong email này.</p>

<p>Xin cảm ơn,<br>
{{ config('app.name') }}</p>
