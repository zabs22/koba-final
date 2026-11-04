<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\PickupVerificationService;
use App\Services\Demo\DemoDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pickup verification at the counter: scan the customer's QR or type the KB
 * code, check the order, then confirm the hand-over. Always scoped to the
 * signed-in admin's branch.
 */
class PickupVerificationController extends Controller
{
    public function __construct(private PickupVerificationService $pickups, private DemoDataService $data) {}

    public function index(Request $request): View
    {
        return view('branch.pickup-verification', [
            'feed' => $this->pickups->feed($request->user()->branchKey()),
            'code' => (string) $request->query('code', ''),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        return response()->json($this->pickups->feed($request->user()->branchKey()));
    }

    /** Finds the order only; it stays Ready until confirm(). */
    public function lookup(Request $request): JsonResponse
    {
        $code = $request->validate(['code' => ['required', 'string', 'max:80']])['code'];

        return response()->json($this->pickups->lookup($request->user()->branchKey(), $code));
    }

    public function confirm(Request $request, string $order): JsonResponse
    {
        $via = $request->validate(['via' => ['nullable', Rule::in(['qr', 'code', 'list'])]])['via'] ?? 'code';
        $result = $this->pickups->confirm($request->user(), $this->find($request, $order), $via);

        return response()->json(['message' => "{$order} picked up.", 'order' => $result]);
    }

    public function undo(Request $request, string $order): JsonResponse
    {
        $result = $this->pickups->undo($request->user(), $this->find($request, $order));

        return response()->json(['message' => "{$order} is back to Ready.", 'order' => $result]);
    }

    /** Demo panel: undo every pickup confirmed at this counter today. */
    public function reset(Request $request): JsonResponse
    {
        $count = $this->pickups->reset($request->user()->branchKey());

        return response()->json(['message' => $count ? "Demo reset: {$count} ".str('order')->plural($count).' back to Ready.' : 'Demo reset: nothing to undo.']);
    }

    private function find(Request $request, string $order): array
    {
        return $this->data->findOrder($order, $request->user()->branchKey()) ?? abort(404);
    }
}
