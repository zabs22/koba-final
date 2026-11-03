<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\CustomerDashboardService;
use App\Services\Demo\DemoDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function __construct(private CustomerDashboardService $dashboard, private DemoDataService $data) {}

    public function index(Request $request): View
    {
        return view('customer.favorites', ['favorites' => $this->dashboard->favorites($request->user())]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:branch,product'],
            'value' => ['required', $request->input('type') === 'branch'
                ? Rule::in(array_keys($this->data->branches()))
                : Rule::in(array_map('strval', array_keys($this->data->products())))],
        ]);

        return response()->json(['favorites' => $this->dashboard->toggleFavorite($request->user(), $data['type'], (string) $data['value'])]);
    }
}
