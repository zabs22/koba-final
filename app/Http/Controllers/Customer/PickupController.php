<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\CustomerDashboardService;
use App\Services\Demo\DemoDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PickupController extends Controller
{
    public function __construct(private CustomerDashboardService $dashboard, private DemoDataService $data) {}

    public function index(Request $request): View
    {
        return view('customer.pickups', [...$this->dashboard->pickups($request->user()), 'now' => $this->data->now()->timestamp]);
    }
}
