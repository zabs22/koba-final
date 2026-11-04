<?php

namespace Tests\Feature\Dashboard;

use PHPUnit\Framework\Attributes\DataProvider;

/** Every dashboard page renders for its own role. */
class PagesRenderTest extends DashboardTestCase
{
    public static function adminPages(): array
    {
        return array_map(fn ($r) => [$r], [
            'admin.dashboard', 'admin.branches', 'admin.admins', 'admin.customers', 'admin.orders', 'admin.products',
            'admin.categories', 'admin.payments', 'admin.revenue', 'admin.reports', 'admin.settings', 'admin.profile',
        ]);
    }

    public static function branchPages(): array
    {
        return array_map(fn ($r) => [$r], [
            'branch.dashboard', 'branch.live-orders', 'branch.verify', 'branch.orders', 'branch.products', 'branch.categories', 'branch.pickups',
            'branch.customers', 'branch.payments', 'branch.reports', 'branch.settings', 'branch.profile',
        ]);
    }

    public static function customerPages(): array
    {
        return array_map(fn ($r) => [$r], [
            'customer.dashboard', 'customer.orders', 'customer.pickups', 'customer.favorites', 'customer.notifications', 'customer.profile',
        ]);
    }

    #[DataProvider('adminPages')]
    public function test_admin_pages_render(string $route): void
    {
        $this->actingAs($this->superAdmin())->get(route($route))->assertOk()->assertSee('Demo data');
    }

    #[DataProvider('branchPages')]
    public function test_branch_pages_render(string $route): void
    {
        $this->actingAs($this->branchAdmin())->get(route($route))->assertOk()->assertSee('KOBA Bole Atlas');
    }

    #[DataProvider('customerPages')]
    public function test_customer_pages_render(string $route): void
    {
        $this->actingAs($this->customer())->get(route($route))->assertOk()->assertSee('Selam');
    }

    public function test_admin_branch_detail_and_json_endpoints(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get(route('admin.branches.show', 'sanford'))->assertOk()->assertSee('KOBA Sanford');
        $this->actingAs($admin)->get(route('admin.branches.show', 'nowhere'))->assertNotFound();
        $this->actingAs($admin)->getJson(route('admin.dashboard.metrics', ['range' => '30d', 'branch' => 'bole-atlas']))
            ->assertOk()->assertJsonStructure(['range', 'kpis' => ['revenue' => ['value', 'change', 'spark']], 'charts' => ['revenue', 'byBranch', 'status', 'heatmap', 'payments'], 'leaderboard']);
        $this->actingAs($admin)->getJson(route('admin.orders.data', ['range' => 'today']))->assertOk()->assertJsonStructure(['rows', 'total', 'pages']);
        $this->actingAs($admin)->get(route('admin.reports.export', 'orders'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
