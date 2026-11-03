<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\CustomerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(private CustomerDashboardService $dashboard) {}

    public function index(Request $request): View
    {
        return view('customer.notifications', ['items' => $this->dashboard->notifications($request->user())]);
    }

    public function read(Request $request): JsonResponse
    {
        $this->dashboard->markNotificationsRead($request->user());

        return response()->json(['message' => 'All caught up.']);
    }
}
