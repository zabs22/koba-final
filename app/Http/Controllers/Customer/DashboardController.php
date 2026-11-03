<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\CustomerDashboardService;
use App\Services\Demo\DemoDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private CustomerDashboardService $dashboard, private DemoDataService $data) {}

    public function index(Request $request): View
    {
        $this->dashboard->ensureDemoOrder($request->user());

        return view('customer.dashboard', [
            'payload' => $this->dashboard->overview($request->user()),
            'now' => $this->data->now()->timestamp,
        ]);
    }
}
