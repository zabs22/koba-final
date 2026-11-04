<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\BranchDashboardService;
use App\Support\Dashboard\DateRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private BranchDashboardService $dashboard) {}

    public function index(Request $request): View|JsonResponse
    {
        $payload = $this->dashboard->payments($request->user()->branchKey(), DateRange::fromRequest($request, '7d'));

        return $request->wantsJson() ? response()->json($payload) : view('branch.payments', ['payload' => $payload]);
    }
}
