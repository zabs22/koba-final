<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AdminDashboardService;
use App\Services\Demo\DemoCatalog;
use App\Services\Demo\DemoDataService;
use App\Services\Demo\DemoState;
use App\Support\Dashboard\DateRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private AdminDashboardService $admin, private DemoDataService $data, private DemoState $state) {}

    public function index(Request $request): View
    {
        return view('admin.products', [
            'rows' => $this->admin->productsTable(DateRange::fromRequest($request, '30d')),
            'categories' => DemoCatalog::CATEGORIES,
        ]);
    }

    /** Hide / show a product on every branch's menu. */
    public function update(Request $request, int $product): JsonResponse
    {
        abort_unless(isset($this->data->products()[$product]), 404);
        $active = $request->validate(['active' => ['required', 'boolean']])['active'];

        $this->state->update('products-hidden', fn ($hidden) => $active
            ? array_values(array_diff($hidden, [$product]))
            : array_values(array_unique([...$hidden, $product])));

        return response()->json(['message' => $this->data->products()[$product]['name'].($active ? ' is back on the menu.' : ' is hidden from every menu.')]);
    }

    public function categories(Request $request): View
    {
        $range = DateRange::fromRequest($request, '30d');

        return view('admin.categories', ['rows' => $this->admin->categoriesTable($range), 'range' => $range]);
    }
}
