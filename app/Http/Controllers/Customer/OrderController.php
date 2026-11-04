<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\CustomerDashboardService;
use App\Services\Dashboard\OrderPresenter;
use App\Services\Demo\DemoDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private CustomerDashboardService $dashboard, private DemoDataService $data, private OrderPresenter $present) {}

    public function index(Request $request): View
    {
        return view('customer.orders', ['rows' => $this->dashboard->ordersTable($request->user()), 'branches' => $this->data->branches()]);
    }

    /** Polled by the live tracker. */
    public function active(Request $request): JsonResponse
    {
        $this->dashboard->ensureDemoOrder($request->user());

        return response()->json(['order' => $this->dashboard->active($request->user()), 'now' => $this->data->now()->timestamp]);
    }

    /** Only the customer's own orders. */
    public function show(Request $request, string $order): JsonResponse
    {
        return response()->json($this->dashboard->order($request->user(), $order) ?? abort(404));
    }

    /**
     * Demo: place an order (from favourites) at the customer's favourite café,
     * so the tracker and the branch's Live Orders board can be tried end to end.
     */
    public function demo(Request $request): JsonResponse
    {
        $user = $request->user();
        $active = $this->dashboard->active($user);
        abort_if($active && $active['status'] !== 'completed', 422, 'You already have an order in progress.');

        $favorites = $this->dashboard->favorites($user);
        $branch = collect($favorites['branch_keys'])->first(fn ($key) => ($this->data->branch($key)['status'] ?? null) === 'open');
        abort_unless($branch, 422, 'Your favourite cafés are not taking pre-orders right now.');

        $items = collect($favorites['product_ids'] ?: [1, 14])->shuffle()->take(2)->map(fn ($id) => [(int) $id, 1])->values()->all();
        $order = $this->data->placeOrder($branch, $this->dashboard->profile($user), $items);

        return response()->json(['message' => 'Order placed.', 'order' => $this->present->detail($order)], 201);
    }
}
