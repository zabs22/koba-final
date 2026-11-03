<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\BranchDashboardService;
use App\Services\Dashboard\OrderPresenter;
use App\Services\Demo\DemoCatalog;
use App\Services\Demo\DemoDataService;
use App\Support\Dashboard\DateRange;
use App\Support\Dashboard\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private BranchDashboardService $dashboard, private DemoDataService $data, private OrderPresenter $present) {}

    public function index(): View
    {
        return view('branch.orders', ['statuses' => DemoCatalog::STATUS_LABELS, 'payments' => DemoCatalog::PAYMENTS]);
    }

    public function data(Request $request): JsonResponse
    {
        $rows = $this->dashboard->ordersTable($request->user()->branchKey(), DateRange::fromRequest($request, 'today'));

        return response()->json(TableQuery::respond($request, $rows, ['id', 'customer', 'items'], ['status', 'payment']));
    }

    public function show(Request $request, string $order): JsonResponse
    {
        return response()->json($this->present->detail($this->find($request, $order)));
    }

    /** Server-side status transition: only the next step, or cancelling an order not yet ready. */
    public function status(Request $request, string $order): JsonResponse
    {
        $found = $this->find($request, $order);
        $allowed = array_filter([
            OrderPresenter::next($found['status']),
            in_array($found['status'], ['pending', 'preparing'], true) ? 'cancelled' : null,
        ]);

        $to = $request->validate([
            'status' => ['required', Rule::in($allowed)],
        ], [
            'status.in' => "Order {$found['id']} is ".DemoCatalog::STATUS_LABELS[$found['status']].' and cannot move to that status.',
        ])['status'];

        $updated = $this->data->setStatus($found, $to);

        return response()->json(['message' => "{$updated['id']} updated.", 'order' => $this->present->detail($updated)]);
    }

    public function receipt(Request $request, string $order): View
    {
        return view('branch.receipt', [
            'order' => $this->present->detail($this->find($request, $order)),
            'branch' => $this->data->branch($request->user()->branchKey()),
        ]);
    }

    /** Scoped to the admin's branch: another branch's order number is simply not found. */
    private function find(Request $request, string $order): array
    {
        return $this->data->findOrder($order, $request->user()->branchKey()) ?? abort(404);
    }
}
