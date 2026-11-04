<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\BranchDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Only customers who have ordered from this branch. */
class CustomerController extends Controller
{
    public function __construct(private BranchDashboardService $dashboard) {}

    public function index(Request $request): View
    {
        return view('branch.customers', ['rows' => $this->dashboard->customersTable($request->user()->branchKey())]);
    }
}
