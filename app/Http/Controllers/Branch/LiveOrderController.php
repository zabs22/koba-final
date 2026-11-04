<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\BranchDashboardService;
use App\Services\Dashboard\OrderPresenter;
use App\Services\Demo\DemoDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveOrderController extends Controller
{
    public function __construct(private BranchDashboardService $dashboard, private DemoDataService $data, private OrderPresenter $present) {}

    public function index(Request $request): View
    {
        return view('branch.live-orders', ['feed' => $this->dashboard->feed($request->user()->branchKey())]);
    }

    /** Polled by the board every few seconds. */
    public function feed(Request $request): JsonResponse
    {
        return response()->json($this->dashboard->feed($request->user()->branchKey()));
    }

    /** Demo: drop a new customer order on this branch's board. */
    public function simulate(Request $request): JsonResponse
    {
        $order = $this->data->placeOrder($request->user()->branchKey());

        return response()->json(['message' => "New order {$order['id']}", 'order' => $this->present->row($order)], 201);
    }
}
