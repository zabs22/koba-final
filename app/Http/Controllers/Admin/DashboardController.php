<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AdminDashboardService;
use App\Services\Demo\DemoDataService;
use App\Support\Dashboard\DateRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private AdminDashboardService $dashboard, private DemoDataService $data) {}

    public function index(Request $request): View
    {
        return view('admin.dashboard', [
            'payload' => $this->payload($request),
            'branches' => $this->data->branches(),
        ]);
    }

    public function metrics(Request $request): JsonResponse
    {
        return response()->json($this->payload($request));
    }

    private function payload(Request $request): array
    {
        $branch = $this->data->branch($request->query('branch')) ? $request->query('branch') : null;

        return $this->dashboard->overview(DateRange::fromRequest($request, '7d'), $branch);
    }
}
