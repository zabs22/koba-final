<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\BranchDashboardService;
use App\Services\Demo\DemoState;
use App\Support\Dashboard\DateRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private BranchDashboardService $dashboard, private DemoState $state) {}

    public function index(Request $request): View
    {
        return view('branch.dashboard', [
            'payload' => $this->dashboard->overview($request->user()->branchKey(), DateRange::fromRequest($request, 'today')),
        ]);
    }

    public function metrics(Request $request): JsonResponse
    {
        return response()->json($this->dashboard->overview($request->user()->branchKey(), DateRange::fromRequest($request, 'today')));
    }

    /** Open / pause / close the branch for new pre-orders (header toggle). */
    public function status(Request $request): JsonResponse
    {
        $status = $request->validate(['status' => ['required', 'in:open,paused,closed']])['status'];
        $this->state->put('branch-status:'.$request->user()->branchKey(), $status);

        return response()->json([
            'status' => $status,
            'message' => match ($status) {
                'open' => 'Open for pre-orders.',
                'paused' => 'Paused: no new pre-orders until you resume.',
                'closed' => 'Closed for the day.',
            },
        ]);
    }
}
