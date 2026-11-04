<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\BranchDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PickupController extends Controller
{
    public function __construct(private BranchDashboardService $dashboard) {}

    public function index(Request $request): View|JsonResponse
    {
        $branch = $request->user()->branchKey();
        $slots = $this->dashboard->pickupSlots($branch);

        return $request->wantsJson()
            ? response()->json(['slots' => $slots])
            : view('branch.pickups', ['slots' => $slots, 'settings' => $this->dashboard->settings($branch)]);
    }
}
