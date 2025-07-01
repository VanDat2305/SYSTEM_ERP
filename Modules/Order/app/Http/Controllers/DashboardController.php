<?php

namespace Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Customer\Models\Customer;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderDetail;
use Modules\Order\Models\Payment;

class DashboardController extends Controller
{
    // Status constants for consistency
    const ORDER_STATUS = [
        'DRAFT' => 'draft',
        'PENDING' => 'pending',
        'APPROVED' => 'approved',
        'PROCESSING' => 'processing',
        'COMPLETED' => 'completed',
        'CANCELLED' => 'cancelled'
    ];

    const PAYMENT_STATUS = [
        'UNPAID' => 'unpaid',
        'PAID' => 'paid',
        'PARTIALLY_PAID' => 'partially_paid',
        'FAILED' => 'failed'
    ];

    const INVOICE_STATUS = [
        'PENDING' => 'pending',
        'EXPORTED' => 'exported',
        'CANCELLED' => 'cancelled'
    ];

    const CONTRACT_STATUS = [
        'DRAFT' => 'draft',
        'PENDING' => 'pending',
        'SIGNED' => 'signed'
    ];

    public function __construct()
    {
        // Middleware để check quyền truy cập dashboard
        // $this->middleware('auth');
        // $this->middleware('permission:dashboard.view')->only(['kpi', 'revenue', 'orders', 'customers', 'analytics']);
        // $this->middleware('permission:dashboard.export')->only(['export']);
    }

    /**
     * Get user's accessible team IDs based on role
     */
    private function getAccessibleTeamIds()
    {
        $user = Auth::user();
        // Admin có thể xem tất cả
        if ($user->hasAnyRole(['admin', 'superadmin', 'accountant'])) {
            return null; // null = không filter theo team
        }
        // Quản lý có thể xem tất cả team
        if ($user->hasRole('manager')) {
            return $user->teams()->pluck('teams.id')->toArray();
        }
        // Nhân viên chỉ xem team mình thuộc về
        if ($user->team_id) {
            return [$user->team_id]; // Chỉ có 1 team
        }
        return []; // Không có quyền truy cập team nào
    }

    /**
     * Apply team filter to query builder
     */
    private function applyTeamFilter($query, $teamColumn = 'team_id')
    {
        $teamIds = $this->getAccessibleTeamIds();

        if ($teamIds !== null) {
            $query->whereIn($teamColumn, $teamIds);
        }

        return $query;
    }

    // 1. API KPI Tổng Quan
    public function kpi(Request $request)
    {
        $period = $request->input('period', 30);
        $startDate = Carbon::now()->subDays($period);
        $teamIds = $this->getAccessibleTeamIds();

        // Tổng đơn hàng với filter team (loại trừ draft)
        $totalOrdersQuery = Order::where('created_at', '>=', $startDate)
            ->where('order_status', '!=', self::ORDER_STATUS['DRAFT']);
        $this->applyTeamFilter($totalOrdersQuery);
        $totalOrders = $totalOrdersQuery->count();

        $prevTotalOrdersQuery = Order::whereBetween('created_at', [
            $startDate->copy()->subDays($period),
            $startDate
        ])->where('order_status', '!=', self::ORDER_STATUS['DRAFT']);
        $this->applyTeamFilter($prevTotalOrdersQuery);
        $prevTotalOrders = $prevTotalOrdersQuery->count();

        $orderChange = $prevTotalOrders > 0 ?
            round(($totalOrders - $prevTotalOrders) / $prevTotalOrders * 100, 1) : 0;

        // Doanh thu với filter team (chỉ tính payment đã paid)
        // Sử dụng trực tiếp từ bảng orders để tối ưu
        $revenueQuery = Order::where('payment_status', 'paid')
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $startDate)
            ->whereNotIn('order_status', ['draft', 'cancelled']); // Loại trừ đơn nháp và đã hủy

        $this->applyTeamFilter($revenueQuery);
        $revenue = $revenueQuery->sum('total_amount'); // Giả sử có trường total_amount lưu tổng tiền

        // Truy vấn doanh thu kỳ trước
        $prevRevenueQuery = Order::where('payment_status', 'paid')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [
                $startDate->copy()->subDays($period),
                $startDate
            ])
            ->whereNotIn('order_status', ['draft', 'cancelled']);

        $this->applyTeamFilter($prevRevenueQuery);
        $prevRevenue = $prevRevenueQuery->sum('total_amount');

        $revenueChange = ($prevRevenue > 0 && $revenue > 0)
            ? round(($revenue - $prevRevenue) / $prevRevenue * 100, 1)
            : ($revenue > 0 ? 100 : 0);
        // Khách hàng mới với filter team
        $newCustomersQuery = Customer::where('created_at', '>=', $startDate);
        $this->applyTeamFilter($newCustomersQuery);
        $newCustomers = $newCustomersQuery->count();

        $prevNewCustomersQuery = Customer::whereBetween('created_at', [
            $startDate->copy()->subDays($period),
            $startDate
        ]);
        $this->applyTeamFilter($prevNewCustomersQuery);
        $prevNewCustomers = $prevNewCustomersQuery->count();

        $customerChange = $prevNewCustomers > 0 ?
            round(($newCustomers - $prevNewCustomers) / $prevNewCustomers * 100, 1) : 0;

        // Đơn chưa thanh toán với filter team
        $unpaidOrdersQuery = Order::where('payment_status', self::PAYMENT_STATUS['UNPAID'])
            ->where('created_at', '>=', $startDate);
        $this->applyTeamFilter($unpaidOrdersQuery);
        $unpaidOrders = $unpaidOrdersQuery->count();

        $prevUnpaidOrdersQuery = Order::where('payment_status', self::PAYMENT_STATUS['UNPAID'])
            ->whereBetween('created_at', [
                $startDate->copy()->subDays($period),
                $startDate
            ]);
        $this->applyTeamFilter($prevUnpaidOrdersQuery);
        $prevUnpaidOrders = $prevUnpaidOrdersQuery->count();

        $unpaidChange = $prevUnpaidOrders > 0 ?
            round(($unpaidOrders - $prevUnpaidOrders) / $prevUnpaidOrders * 100, 1) : 0;

        // // Dịch vụ sắp hết hạn (trong 30 ngày tới) với filter team
        // $expiringServicesQuery = OrderDetail::select('order_details.*')
        //     ->join('orders', 'order_details.order_id', '=', 'orders.id')
        //     ->whereBetween('order_details.end_date', [
        //         Carbon::now(),
        //         Carbon::now()->addDays(30)
        //     ])
        //     ->where('order_details.is_active', true);
        // $this->applyTeamFilter($expiringServicesQuery, 'orders.team_id');
        // $expiringServices = $expiringServicesQuery->count();

        // $prevExpiringServicesQuery = OrderDetail::select('order_details.*')
        //     ->join('orders', 'order_details.order_id', '=', 'orders.id')
        //     ->whereBetween('order_details.end_date', [
        //         Carbon::now()->subDays(30),
        //         Carbon::now()
        //     ])
        //     ->where('order_details.is_active', true);
        // $this->applyTeamFilter($prevExpiringServicesQuery, 'orders.team_id');
        // $prevExpiringServices = $prevExpiringServicesQuery->count();

        // $expiringChange = $prevExpiringServices > 0 ?
        //     round(($expiringServices - $prevExpiringServices) / $prevExpiringServices * 100, 1) : 0;

        return response()->json([
            'kpis' => [
                ['value' => $totalOrders, 'change' => $orderChange],
                ['value' => number_format($revenue, 0, '', '.') . "đ", 'change' => $revenueChange],
                ['value' => $newCustomers, 'change' => $customerChange],
                ['value' => $unpaidOrders, 'change' => $unpaidChange],
                // ['value' => $expiringServices, 'change' => $expiringChange],
            ]
        ]);
    }

    // 2. API Biểu đồ doanh thu
    public function revenue(Request $request)
    {
        $periodType = $request->input('period_type', 'ngày');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Xác định khoảng thời gian dựa trên period type
        if (!$startDate || !$endDate) {
            $dateRange = $this->getDateRangeByPeriodType($periodType);
            $startDate = $dateRange['start_date'];
            $endDate = $dateRange['end_date'];
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Sử dụng trực tiếp bảng orders để tối ưu
        $query = Order::select(
            $this->getDateSelectByPeriodType($periodType),
            DB::raw('SUM(total_amount) as total')
        )
            ->where('payment_status', 'paid')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$start, $end])
            ->whereNotIn('order_status', ['draft', 'cancelled']);

        $this->applyTeamFilter($query);

        $groupBy = $this->getGroupByPeriodType($periodType);
        $revenueData = $query->groupBy($groupBy)
            ->orderBy('date_group')
            ->get();

        // Điền đầy đủ các mốc thời gian
        $filledData = $this->fillDateGaps($revenueData, $start, $end, $periodType);

        return response()->json([
            'categories' => $filledData['categories'],
            'series' => [
                [
                    'name' => 'Doanh thu',
                    'data' => $filledData['data']
                ]
            ]
        ]);
    }
    private function getDateRangeByPeriodType($periodType)
    {
        $now = Carbon::now();
        switch ($periodType) {
            case 'ngày':
                return [
                    'start_date' => $now->copy()->subDays(7),
                    'end_date' => $now
                ];
            case 'tuần':
                return [
                    'start_date' => $now->copy()->subWeeks(4),
                    'end_date' => $now
                ];
            case 'tháng':
                return [
                    'start_date' => $now->copy()->subMonths(12),
                    'end_date' => $now
                ];
            default:
                return [
                    'start_date' => $now->copy()->subDays(30),
                    'end_date' => $now
                ];
        }
    }
    private function getDateSelectByPeriodType($periodType)
    {
        switch ($periodType) {
            case 'ngày':
                return DB::raw('DATE(paid_at) as date_group');
            case 'tuần':
                return DB::raw('CONCAT(YEAR(paid_at), "-", LPAD(WEEK(paid_at), 2, "0")) as date_group');
            case 'tháng':
                return DB::raw('DATE_FORMAT(paid_at, "%Y-%m") as date_group');
            default:
                return DB::raw('DATE(paid_at) as date_group');
        }
    }

    private function getGroupByPeriodType($periodType)
    {
        switch ($periodType) {
            case 'tuần':
                return DB::raw('CONCAT(YEAR(paid_at), "-", LPAD(WEEK(paid_at), 2, "0"))');
            case 'tháng':
                return DB::raw('DATE_FORMAT(paid_at, "%Y-%m")');
            default:
                return DB::raw('DATE(paid_at)');
        }
    }

    private function fillDateGaps($data, $start, $end, $periodType)
    {
        $result = [];
        $current = $start->copy();

        while ($current <= $end) {
            $key = $this->getDateKey($current, $periodType);
            $found = $data->firstWhere('date_group', $key);

            $result['categories'][] = $this->formatDateLabel($current, $periodType);
            $result['data'][] = $found ? (float)$found->total : 0;

            $this->incrementDate($current, $periodType);
        }

        return $result;
    }

    private function getDateKey($date, $periodType)
    {
        switch ($periodType) {
            case 'ngày':
                return $date->format('Y-m-d');
            case 'tuần':
                return $date->format('Y-') . str_pad($date->weekOfYear, 2, '0', STR_PAD_LEFT);
            case 'tháng':
                return $date->format('Y-m');
            default:
                return $date->format('Y-m-d');
        }
    }


    private function formatDateLabel($date, $periodType)
    {
        switch ($periodType) {
            case 'ngày':
                return $date->format('d/m');
            case 'tuần':
                return 'Tuần ' . $date->weekOfYear . ' ' . $date->format('Y');
            case 'tháng':
                return $date->format('m/Y');
            default:
                return $date->format('d/m');
        }
    }

    private function incrementDate(&$date, $periodType)
    {
        switch ($periodType) {
            case 'ngày':
                $date->addDay();
                break;
            case 'tuần':
                $date->addWeek();
                break;
            case 'tháng':
                $date->addMonth();
                break;
            default:
                $date->addDay();
        }
    }


    // 3. API Đơn hàng mới nhất
    public function orders(Request $request)
    {
        $limit = $request->input('limit', 10);

        $ordersQuery = Order::with('customer')
            ->orderByDesc('created_at')
            ->limit($limit);

        $this->applyTeamFilter($ordersQuery);

        $orders = $ordersQuery->get()
            ->map(function ($order) {
                return [
                    'order_code' => $order->order_code,
                    'customer' => $order->customer->full_name ?? '',
                    'amount' => $order->total_amount,
                    'status' => $order->order_status,
                    'payment_status' => $order->payment_status,
                    'date' => $order->created_at->format('Y-m-d')
                ];
            });

        return response()->json([
            'orders' => $orders
        ]);
    }

    // 4. API Khách hàng mới nhất
    public function customers(Request $request)
    {
        $limit = $request->input('limit', 10);

        $customersQuery = Customer::with(['contacts' => function ($query) {
            $query->where('is_primary', true)
                ->where(function ($q) {
                    $q->where('contact_type', 'email')
                        ->orWhere('contact_type', 'phone');
                });
        }])
            ->orderByDesc('created_at')
            ->limit($limit);

        $this->applyTeamFilter($customersQuery);

        $customers = $customersQuery->get()
            ->map(function ($customer) {
                $primaryEmail = $customer->contacts->firstWhere('contact_type', 'email');
                $primaryPhone = $customer->contacts->firstWhere('contact_type', 'phone');

                return [
                    'id' => $customer->id,
                    'customer_code' => $customer->customer_code,
                    'name' => $customer->full_name,
                    'email' => $primaryEmail->value ?? '',
                    'phone' => $primaryPhone->value ?? '',
                    'registeredDate' => $customer->created_at->format('Y-m-d')
                ];
            });

        return response()->json([
            'customers' => $customers
        ]);
    }

    // 5. API Dữ liệu phân tích
    public function analytics(Request $request)
    {
        $period = $request->input('period', 30);
        $startDate = Carbon::now()->subDays($period);

        // Order Status với filter team (sử dụng constants)
        $orderStatusQueries = [
            'completed' => Order::where('order_status', self::ORDER_STATUS['COMPLETED']),
            'processing' => Order::where('order_status', self::ORDER_STATUS['PROCESSING']),
            'approved' => Order::where('order_status', self::ORDER_STATUS['APPROVED']),
            'pending' => Order::where('order_status', self::ORDER_STATUS['PENDING']),
            'cancelled' => Order::where('order_status', self::ORDER_STATUS['CANCELLED'])
        ];

        $orderStatus = [];
        foreach ($orderStatusQueries as $status => $query) {
            $this->applyTeamFilter($query);
            $orderStatus[] = $query->count();
        }

        // Payment Status Analysis
        $paymentStatusQueries = [
            'paid' => Order::where('payment_status', self::PAYMENT_STATUS['PAID']),
            'unpaid' => Order::where('payment_status', self::PAYMENT_STATUS['UNPAID']),
            'partially_paid' => Order::where('payment_status', self::PAYMENT_STATUS['PARTIALLY_PAID']),
            'failed' => Order::where('payment_status', self::PAYMENT_STATUS['FAILED'])
        ];

        $paymentStatus = [];
        foreach ($paymentStatusQueries as $status => $query) {
            $this->applyTeamFilter($query);
            $paymentStatus[] = $query->count();
        }

        // Top Products với filter team
        $topProductsQuery = DB::table('order_details')
            ->join('service_packages', 'order_details.package_code', '=', 'service_packages.package_code')
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->select(
                'service_packages.package_name',
                DB::raw('SUM(order_details.quantity) as total_quantity')
            )
            ->where('order_details.created_at', '>=', $startDate);

        $this->applyTeamFilter($topProductsQuery, 'orders.team_id');

        $topProducts = $topProductsQuery
            ->groupBy('service_packages.package_name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        $topProductsNames = $topProducts->pluck('package_name')->toArray();
        $topProductsData = $topProducts->pluck('total_quantity')->toArray();

        // // Customer Growth (last 6 months) với filter team
        // $customerGrowth = [];
        // for ($i = 5; $i >= 0; $i--) {
        //     $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
        //     $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();

        //     $customerGrowthQuery = Customer::whereBetween('created_at', [$monthStart, $monthEnd]);
        //     $this->applyTeamFilter($customerGrowthQuery);
        //     $customerGrowth[] = $customerGrowthQuery->count();
        // }

        // // Sales Performance (last 6 months) với filter team
        // $salesPerformance = [];
        // for ($i = 5; $i >= 0; $i--) {
        //     $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
        //     $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();

        //     $target = 100000000; // Example target - có thể lấy từ config hoặc database

        //     $actualQuery = Payment::select('payments.*')
        //         ->join('orders', 'payments.order_id', '=', 'orders.id')
        //         ->where('payments.status', self::PAYMENT_STATUS['PAID'])
        //         ->whereBetween('payments.created_at', [$monthStart, $monthEnd]);

        //     $this->applyTeamFilter($actualQuery, 'orders.team_id');
        //     $actual = $actualQuery->sum('payments.amount_paid');

        //     $performance = $target > 0 ? round(($actual / $target) * 100, 0) : 0;
        //     $salesPerformance[] = min($performance, 100); // Cap at 100%
        // }

        // Conversion Funnel với filter team
        $conversionFunnelQueries = [
            Order::query(), // All orders
            Order::where('order_status', '!=', self::ORDER_STATUS['CANCELLED']), // Not cancelled
            Order::where('payment_status', self::PAYMENT_STATUS['PAID']), // Paid
            Order::where('order_status', self::ORDER_STATUS['COMPLETED']), // Completed
            Order::where('created_at', '>=', Carbon::now()->subMonth()) // Recent
        ];

        $conversionFunnel = [];
        foreach ($conversionFunnelQueries as $query) {
            $this->applyTeamFilter($query);
            $conversionFunnel[] = $query->count();
        }

        return response()->json([
            "orderStatus" => $orderStatus,
            "paymentStatus" => $paymentStatus,
            "topProductsSeries" => [
                ["name" => "Số lượng bán", "data" => $topProductsData]
            ],
            "topProductsCategories" => $topProductsNames,
            // "customerGrowth" => [
            //     ["name" => "Khách hàng mới", "data" => $customerGrowth]
            // ],
            // "salesPerformance" => [
            //     ["name" => "Hiệu suất", "data" => $salesPerformance]
            // ],
            "conversionFunnel" => [
                ["name" => "Số lượng", "data" => $conversionFunnel]
            ]
        ]);
    }
    public function customerGrowth(Request $request)
    {
        // Lấy start_date, end_date động (có thể truyền hoặc lấy mặc định 6 tháng gần nhất)
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfMonth() : Carbon::now()->endOfMonth();
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfMonth() : $endDate->copy()->subMonths(5)->startOfMonth();

        // Sinh mảng mốc thời gian và dữ liệu theo tháng
        $categories = [];
        $data = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $query = Customer::whereBetween('created_at', [$monthStart, $monthEnd]);
            $this->applyTeamFilter($query);
            $data[] = $query->count();
            $categories[] = $current->format('m/Y');

            $current->addMonth();
        }

        return response()->json([
            'categories' => $categories,  // ["03/2024", "04/2024", ...]
            'series' => [
                [
                    'name' => 'Khách hàng mới',
                    'data' => $data
                ]
            ]
        ]);
    }


    // 6. API Export Dashboard (PDF)
    public function export(Request $request)
    {
        $format = $request->input('format', 'pdf');
        $period = $request->input('period', 30);

        // Kiểm tra quyền export
        // if (!Auth::user()->can('dashboard.export')) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        // Log export activity
        activity()
            ->causedBy(Auth::user())
            ->withProperties([
                'format' => $format,
                'period' => $period,
                'exported_at' => now()
            ])
            ->log('Dashboard exported');

        // Generate file name with user info for audit
        $fileName = "dashboard-report-" . Auth::user()->id . "-" . date('Y-m-d-H-i-s') . ".pdf";
        $filePath = storage_path('app/public/' . $fileName);

        // In a real implementation, you would:
        // 1. Generate PDF with filtered data based on user's team access
        // 2. Include user info and timestamp
        // 3. Store export log for audit

        if (!file_exists($filePath)) {
            // Tạo nội dung PDF với data đã filter theo team
            $user = Auth::user();
            $content = "Dashboard Export\n";
            $content .= "Generated by: " . $user->name . "\n";
            $content .= "Date: " . date('Y-m-d H:i:s') . "\n";
            $content .= "Period: " . $period . " days\n";
            $content .= "User Role: " . $user->role . "\n";

            file_put_contents($filePath, $content);
        }

        return response()->download($filePath, $fileName);
    }

    /**
     * Helper method to get status labels in Vietnamese
     */
    public function getStatusLabels()
    {
        return [
            'order_status' => [
                self::ORDER_STATUS['DRAFT'] => 'Nháp',
                self::ORDER_STATUS['PENDING'] => 'Chờ xử lý',
                self::ORDER_STATUS['APPROVED'] => 'Đã phê duyệt',
                self::ORDER_STATUS['PROCESSING'] => 'Đang xử lý',
                self::ORDER_STATUS['COMPLETED'] => 'Đã hoàn tất',
                self::ORDER_STATUS['CANCELLED'] => 'Đã hủy'
            ],
            'payment_status' => [
                self::PAYMENT_STATUS['UNPAID'] => 'Chưa thanh toán',
                self::PAYMENT_STATUS['PAID'] => 'Đã thanh toán đầy đủ',
                self::PAYMENT_STATUS['PARTIALLY_PAID'] => 'Thanh toán 1 phần',
                self::PAYMENT_STATUS['FAILED'] => 'Giao dịch thất bại'
            ],
            'invoice_status' => [
                self::INVOICE_STATUS['PENDING'] => 'Chưa xuất',
                self::INVOICE_STATUS['EXPORTED'] => 'Đã xuất',
                self::INVOICE_STATUS['CANCELLED'] => 'Hủy'
            ],
            'contract_status' => [
                self::CONTRACT_STATUS['DRAFT'] => 'Chưa ký',
                self::CONTRACT_STATUS['PENDING'] => 'Chờ ký',
                self::CONTRACT_STATUS['SIGNED'] => 'Đã ký'
            ]
        ];
    }
}
