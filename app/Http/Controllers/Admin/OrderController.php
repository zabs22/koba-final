<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\OrderPresenter;
use App\Services\Demo\DemoDataService;
use App\Support\Dashboard\DateRange;
use App\Support\Dashboard\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private DemoDataService $data, private OrderPresenter $present) {}

    public function index(): View
    {
        return view('admin.orders', ['branches' => $this->data->branches()]);
    }

    /** Remote rows for the orders table: ?range=&branch=&status=&payment=&q=&sort=&page= */
    public function data(Request $request): JsonResponse
    {
        $now = $this->data->now()->timestamp;
        $orders = array_reverse(array_filter($this->data->ordersIn(DateRange::fromRequest($request, '7d')), fn ($o) => $o['placed_at'] <= $now));

        return response()->json(TableQuery::respond($request, $this->present->rows($orders), ['id', 'customer', 'items', 'branch_name'], ['branch', 'status', 'payment']));
    }

    public function show(string $order): JsonResponse
    {
        $found = $this->data->findOrder($order) ?? abort(404);

        return response()->json($this->present->detail($found));
    }
}
