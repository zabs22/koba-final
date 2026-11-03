<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\Dashboard\CustomerDashboardService;
use App\Services\Dashboard\OrderPresenter;
use App\Services\Demo\DemoDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Command palette search. Results are scoped by role on the server:
 * super admin → all orders, customers and branches; branch admin → its own
 * orders; customer → their own orders.
 */
class SearchController extends Controller
{
    public function __construct(private DemoDataService $data, private OrderPresenter $present, private CustomerDashboardService $customer) {}

    public function __invoke(Request $request): JsonResponse
    {
        $q = Str::lower(trim((string) $request->query('q', '')));
        $user = $request->user();
        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $area = $user->role()->area();
        $since = $this->data->now()->subDays(13)->startOfDay();
        $orders = match ($user->role()) {
            UserRole::SuperAdmin => $this->data->orders($since, $this->data->now()),
            UserRole::BranchAdmin => $this->data->orders($since, $this->data->now(), $user->branchKey()),
            UserRole::Customer => $this->data->customerOrders($this->customer->profile($user)),
        };

        $results = collect($this->present->rows(array_reverse($orders)))
            ->filter(fn ($o) => str_contains(Str::lower($o['id'].' '.$o['customer'].' '.$o['items']), $q))
            ->take(6)
            ->map(fn ($o) => ['label' => $o['id'].' · '.$o['customer'], 'sub' => $o['pickup_date'].' · '.$o['status_label'].' · '.OrderPresenter::etb($o['total']), 'icon' => 'receipt-text', 'group' => 'Orders', 'order' => $o['id'], 'href' => route("$area.orders", ['order' => $o['id']])])
            ->values();

        if ($user->hasRole(UserRole::SuperAdmin)) {
            $results = $results
                ->concat(collect($this->data->customers())->filter(fn ($c) => str_contains(Str::lower($c['name'].' '.$c['email']), $q))->take(4)
                    ->map(fn ($c) => ['label' => $c['name'], 'sub' => $c['email'], 'icon' => 'user-round', 'group' => 'Customers', 'href' => route('admin.customers', ['q' => $c['name']])]))
                ->concat(collect($this->data->branches())->filter(fn ($b) => str_contains(Str::lower($b['name'].' '.$b['area']), $q))
                    ->map(fn ($b) => ['label' => $b['name'], 'sub' => $b['area'], 'icon' => 'store', 'group' => 'Branches', 'href' => route('admin.branches.show', $b['key'])]))
                ->values();
        }

        return response()->json(['results' => $results]);
    }
}
