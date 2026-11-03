<?php

namespace Tests\Feature\Dashboard;

use App\Services\Demo\DemoDataService;

/**
 * A branch admin (Bole Atlas) only ever sees and changes Bole Atlas orders,
 * whatever order number is put in the URL.
 */
class BranchIsolationTest extends DashboardTestCase
{
    private function orderAt(string $branch): array
    {
        $data = app(DemoDataService::class);
        $orders = $data->orders($data->now()->subDays(2)->startOfDay(), $data->now()->subDay()->endOfDay(), $branch);
        $this->assertNotEmpty($orders, "Demo data should have recent orders at $branch");

        return $orders[0];
    }

    public function test_branch_admin_can_open_its_own_orders(): void
    {
        $order = $this->orderAt('bole-atlas');

        $this->actingAs($this->branchAdmin())->getJson(route('branch.orders.show', $order['id']))
            ->assertOk()->assertJsonPath('id', $order['id'])->assertJsonPath('branch', 'bole-atlas');
    }

    public function test_branch_admin_cannot_view_another_branchs_order_by_changing_the_id(): void
    {
        $other = $this->orderAt('sanford');
        $admin = $this->branchAdmin();

        $this->actingAs($admin)->getJson(route('branch.orders.show', $other['id']))->assertNotFound();
        $this->actingAs($admin)->get(route('branch.orders.receipt', $other['id']))->assertNotFound();
    }

    public function test_branch_admin_cannot_update_another_branchs_order(): void
    {
        $data = app(DemoDataService::class);
        $other = $data->placeOrder('sanford');

        $this->actingAs($this->branchAdmin())
            ->patchJson(route('branch.orders.status', $other['id']), ['status' => 'preparing'])
            ->assertNotFound();

        $this->assertSame('pending', app(DemoDataService::class)->findOrder($other['id'])['status']);
    }

    public function test_status_moves_one_step_at_a_time_and_is_seen_by_the_super_admin(): void
    {
        $order = app(DemoDataService::class)->placeOrder('bole-atlas');
        $admin = $this->branchAdmin();

        $this->actingAs($admin)->patchJson(route('branch.orders.status', $order['id']), ['status' => 'completed'])->assertUnprocessable();
        $this->actingAs($admin)->patchJson(route('branch.orders.status', $order['id']), ['status' => 'preparing'])
            ->assertOk()->assertJsonPath('order.status', 'preparing');

        $this->actingAs($this->superAdmin())->getJson(route('admin.orders.show', $order['id']))
            ->assertOk()->assertJsonPath('status', 'preparing');
    }

    public function test_branch_feeds_only_contain_the_admins_branch(): void
    {
        app(DemoDataService::class)->placeOrder('sanford');
        $admin = $this->branchAdmin();

        $feed = $this->actingAs($admin)->getJson(route('branch.live-orders.feed'))->assertOk()->json('columns');
        foreach (array_merge(...array_values($feed)) as $order) {
            $this->assertSame('bole-atlas', $order['branch']);
        }

        $rows = $this->actingAs($admin)->getJson(route('branch.orders.data', ['range' => '7d', 'per_page' => 100]))->json('rows');
        $this->assertNotEmpty($rows);
        $this->assertSame(['bole-atlas'], array_values(array_unique(array_column($rows, 'branch'))));
    }

    public function test_customers_only_see_their_own_orders(): void
    {
        $someoneElses = $this->orderAt('sanford');
        if ($someoneElses['customer'] === 0) {
            $this->markTestSkipped('Picked the demo customer’s own order.');
        }

        $this->actingAs($this->customer())->getJson(route('customer.orders.show', $someoneElses['id']))->assertNotFound();
    }
}
