<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AdminDashboardService;
use App\Services\Dashboard\OrderPresenter;
use App\Services\Demo\DemoDataService;
use App\Support\Dashboard\DateRange;
use App\Support\Dashboard\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __construct(private AdminDashboardService $admin, private DemoDataService $data, private OrderPresenter $present) {}

    public function payments(Request $request): View|JsonResponse
    {
        $payload = $this->admin->finance(DateRange::fromRequest($request, '30d'));

        return $request->wantsJson() ? response()->json($payload) : view('admin.payments', ['payload' => $payload, 'branches' => $this->data->branches()]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $now = $this->data->now()->timestamp;
        $orders = array_reverse(array_filter($this->data->ordersIn(DateRange::fromRequest($request, '30d')), fn ($o) => $o['placed_at'] <= $now));
        $rows = array_map(fn ($o) => [...$this->present->transaction($o), 'total' => $o['total']], $orders);

        return response()->json(TableQuery::respond($request, $rows, ['id', 'order', 'customer'], ['method', 'status', 'branch']));
    }

    public function revenue(Request $request): View|JsonResponse
    {
        $range = DateRange::fromRequest($request, '30d');
        $payload = [...$this->admin->finance($range), 'overview' => $this->admin->overview($range)['charts']];

        return $request->wantsJson() ? response()->json($payload) : view('admin.revenue', ['payload' => $payload]);
    }
}
