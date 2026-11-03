<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\BranchDashboardService;
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
        'orders' => ['label' => 'Orders', 'description' => 'Every order in the period with customer, items, total, payment and status.', 'icon' => 'receipt-text'],
        'daily' => ['label' => 'Daily sales', 'description' => 'Orders, revenue, average order and prep time per day.', 'icon' => 'calendar-days'],
        'products' => ['label' => 'Product sales', 'description' => 'Units sold per product, with availability today.', 'icon' => 'croissant'],
        'customers' => ['label' => 'Customers', 'description' => 'Everyone who ordered from this branch, with totals.', 'icon' => 'users'],
    ];

    public function __construct(private BranchDashboardService $dashboard, private DemoDataService $data, private OrderPresenter $present) {}

    public function index(Request $request): View|JsonResponse
    {
        $payload = $this->dashboard->overview($request->user()->branchKey(), DateRange::fromRequest($request, '30d'));

        return $request->wantsJson() ? response()->json($payload) : view('branch.reports', ['payload' => $payload, 'exports' => self::EXPORTS]);
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        abort_unless(isset(self::EXPORTS[$type]), 404);
        $branch = $request->user()->branchKey();
        $range = DateRange::fromRequest($request, '30d');

        return match ($type) {
            'orders' => Csv::download("$branch-orders", ['id' => 'Order', 'pickup_date' => 'Date', 'pickup_time' => 'Pickup', 'customer' => 'Customer', 'items' => 'Items', 'total' => 'Total (ETB)', 'payment_label' => 'Payment', 'status_label' => 'Status', 'prep' => 'Prep (min)'],
                $this->dashboard->ordersTable($branch, $range)),
            'daily' => Csv::download("$branch-daily", ['date' => 'Date', 'orders' => 'Orders', 'revenue' => 'Revenue (ETB)', 'aov' => 'Avg. order (ETB)', 'prep' => 'Avg. prep (min)'], $this->daily($branch, $range)),
            'products' => Csv::download("$branch-products", ['sku' => 'SKU', 'name' => 'Product', 'category_name' => 'Category', 'price' => 'Price (ETB)', 'units' => 'Units sold', 'available' => 'Available today'], $this->dashboard->productsTable($branch, $range)),
            'customers' => Csv::download("$branch-customers", ['name' => 'Customer', 'phone' => 'Phone', 'orders' => 'Orders', 'spent' => 'Spent (ETB)', 'last' => 'Last order', 'segment' => 'Segment'], $this->dashboard->customersTable($branch)),
        };
    }

    private function daily(string $branch, DateRange $range): \Generator
    {
        $orders = $this->data->ordersIn($range, $branch);
        foreach ($range->buckets() as $bucket) {
            $day = array_filter($orders, fn ($o) => $o['pickup_at'] >= $bucket['from'] && $o['pickup_at'] <= $bucket['to']);
            yield ['date' => $this->present->time($bucket['from'], $range->hourly() ? 'Y-m-d H:i' : 'Y-m-d'), 'orders' => count($day), 'revenue' => Metrics::revenue($day), 'aov' => round(Metrics::aov($day), 2), 'prep' => Metrics::avgPrep($day)];
        }
    }
}
