<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\BranchDashboardService;
use App\Services\Demo\DemoCatalog;
use App\Services\Demo\DemoDataService;
use App\Services\Demo\DemoState;
use App\Support\Dashboard\DateRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function __construct(private BranchDashboardService $dashboard, private DemoDataService $data, private DemoState $state) {}

    public function products(Request $request): View
    {
        return view('branch.products', [
            'rows' => $this->dashboard->productsTable($request->user()->branchKey(), DateRange::make('7d')),
            'categories' => DemoCatalog::CATEGORIES,
        ]);
    }

    /** Availability switch: this branch only. */
    public function availability(Request $request, int $product): JsonResponse
    {
        $branch = $request->user()->branchKey();
        abort_unless(isset($this->data->products()[$product]), 404);
        $available = $request->validate(['available' => ['required', 'boolean']])['available'];

        $this->state->update("unavailable:$branch", fn ($off) => $available
            ? array_values(array_diff($off, [$product]))
            : array_values(array_unique([...$off, $product])));

        $name = $this->data->products()[$product]['name'];

        return response()->json(['message' => $available ? "$name is available again." : "$name marked unavailable."]);
    }

    public function categories(Request $request): View
    {
        return view('branch.categories', ['rows' => $this->dashboard->categories($request->user()->branchKey(), DateRange::make('30d'))]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $order = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => [Rule::in(array_keys(DemoCatalog::CATEGORIES))],
        ])['order'];

        $this->state->put('category-order:'.$request->user()->branchKey(), array_values(array_unique($order)));

        return response()->json(['message' => 'Menu order saved.']);
    }
}
