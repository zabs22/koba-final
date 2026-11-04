<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\CakeOrderRequest;
use App\Http\Requests\Site\ContactRequest;
use App\Services\Demo\WebOrders;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives the public cake-order and contact forms.
 *
 * Validation is complete. Cake orders become demo orders (WebOrders) so the
 * chosen branch sees them live and the customer gets a pickup code and QR;
 * a real Order model plus a mail or SMS to the branch replaces that later.
 */
class EnquiryController extends Controller
{
    public function cakeOrder(CakeOrderRequest $request, WebOrders $orders): JsonResponse|RedirectResponse
    {
        Log::info('KOBA cake order request', $request->validated());

        $order = $orders->place($request->validated(), $request->user());
        $url = $orders->confirmationUrl($order);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => __('koba.order.success_body'), 'order' => $order['id'], 'redirect' => $url]);
        }

        return redirect($url)->with('status', __('koba.order.success_body'));
    }

    public function contact(ContactRequest $request): JsonResponse|RedirectResponse
    {
        Log::info('KOBA contact message', $request->validated());

        return $this->respond($request, __('koba.contact.success_body'));
    }

    private function respond(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return back()->with('status', $message);
    }
}
