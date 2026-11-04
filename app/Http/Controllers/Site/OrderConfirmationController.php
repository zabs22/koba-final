<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\OrderPresenter;
use App\Services\Demo\DemoDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * "Order confirmed" page with the pickup code and QR. Reached by a signed
 * link (route middleware), so a guest who ordered can open it again without
 * an account, and nobody can browse other orders by changing the number.
 */
class OrderConfirmationController extends Controller
{
    public function __construct(private DemoDataService $data, private OrderPresenter $present) {}

    public function show(string $order): View
    {
        $found = $this->data->findOrder($order) ?? abort(404);

        return view('pickup.confirmed', [
            'order' => $this->present->pass($found),
            'branch' => $this->data->branch($found['branch']),
            'statusUrl' => URL::signedRoute('order.confirmed.status', ['order' => $found['id']]),
            'now' => $this->data->now()->timestamp,
        ]);
    }

    /** Polled by the page so the timeline follows the kitchen. */
    public function status(string $order): JsonResponse
    {
        $found = $this->data->findOrder($order) ?? abort(404);

        return response()->json(['order' => $this->present->detail($found), 'now' => $this->data->now()->timestamp]);
    }
}
