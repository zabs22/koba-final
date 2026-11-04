<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AdminDashboardService;
use App\Services\Dashboard\OrderPresenter;
use App\Services\Demo\DemoDataService;
use App\Support\Dashboard\Csv;
use App\Support\Dashboard\DateRange;
use App\Support\Dashboard\Metrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public const EXPORTS = [
        'orders' => ['label' => 'Orders', 'description' => 'Every order in the period with branch, customer, items, total and status.', 'icon' => 'receipt-text'],
        'daily' => ['label' => 'Daily revenue by branch', 'description' => 'One row per day and branch: orders, revenue, average order value.', 'icon' => 'calendar-days'],
        'branches' => ['label' => 'Branch comparison', 'description' => 'This period against the previous one, per branch.', 'icon' => 'store'],
        'customers' => ['label' => 'Customers', 'description' => 'Lifetime orders, spend and segment per customer.', 'icon' => 'users'],
        'products' => ['label' => 'Product sales', 'description' => 'Units sold and revenue per product.', 'icon' => 'croissant'],
    ];

    public function __construct(private AdminDashboardService $admin, private DemoDataService $data, private OrderPresenter $present) {}

    public function index(Request $request): View|JsonResponse
    {
        $payload = $this->admin->comparison(DateRange::fromRequest($request, '30d'));

        return $request->wantsJson() ? response()->json($payload) : view('admin.reports', ['payload' => $payload, 'exports' => self::EXPORTS]);
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        abort_unless(isset(self::EXPORTS[$type]), 404);
        $range = DateRange::fromRequest($request, '30d');

        return match ($type) {
            'orders' => Csv::download('orders', ['id' => 'Order', 'pickup_date' => 'Date', 'pickup_time' => 'Pickup', 'branch_name' => 'Branch', 'customer' => 'Customer', 'items' => 'Items', 'total' => 'Total (ETB)', 'payment_label' => 'Payment', 'status_label' => 'Status'],
                $this->present->rows($this->data->ordersIn($range))),
            'daily' => Csv::download('daily-revenue', ['date' => 'Date', 'branch' => 'Branch', 'orders' => 'Orders', 'revenue' => 'Revenue (ETB)', 'aov' => 'Avg. order (ETB)'], $this->daily($range)),
            'branches' => Csv::download('branches', ['branch' => 'Branch', 'orders' => 'Orders', 'orders_prev' => 'Orders (prev.)', 'revenue' => 'Revenue (ETB)', 'revenue_prev' => 'Revenue prev. (ETB)', 'change' => 'Change %', 'aov' => 'Avg. order (ETB)', 'prep' => 'Avg. prep (min)', 'cancel' => 'Cancelled %'],
                $this->admin->comparison($range)['rows']),
            'customers' => Csv::download('customers', ['name' => 'Customer', 'email' => 'Email', 'phone' => 'Phone', 'branch_name' => 'Favourite branch', 'orders' => 'Orders', 'spent' => 'Spent (ETB)', 'last' => 'Last order', 'segment' => 'Segment'], $this->admin->customersTable()),
            'products' => Csv::download('products', ['sku' => 'SKU', 'name' => 'Product', 'category_name' => 'Category', 'price' => 'Price (ETB)', 'units' => 'Units sold', 'revenue' => 'Revenue (ETB)'], $this->admin->productsTable($range)),
        };
    }

    private function daily(DateRange $range): \Generator
    {
        $orders = $this->data->ordersIn($range);
        foreach ($this->data->branches() as $branch) {
            $mine = array_values(array_filter($orders, fn ($o) => $o['branch'] === $branch['key']));
            foreach ($range->buckets() as $bucket) {
                $day = array_filter($mine, fn ($o) => $o['pickup_at'] >= $bucket['from'] && $o['pickup_at'] <= $bucket['to']);
                yield ['date' => $this->present->time($bucket['from'], $range->hourly() ? 'Y-m-d H:i' : 'Y-m-d'), 'branch' => $branch['short'], 'orders' => count($day), 'revenue' => Metrics::revenue($day), 'aov' => round(Metrics::aov($day), 2)];
            }
        }
    }
}
 