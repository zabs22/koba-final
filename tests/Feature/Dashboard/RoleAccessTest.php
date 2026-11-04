<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;

/** Login redirects, role separation and guest handling. */
class RoleAccessTest extends DashboardTestCase
{
    public function test_each_role_is_sent_to_its_own_dashboard_after_login(): void
    {
        foreach ([
            [$this->superAdmin(), 'admin.dashboard'],
            [$this->branchAdmin(), 'branch.dashboard'],
            [$this->customer(), 'customer.dashboard'],
        ] as [$user, $home]) {
            $this->post('/login', ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(route('dashboard', absolute: false));

            $this->get('/dashboard')->assertRedirect(route($home));
            $this->post('/logout');
            $this->app['auth']->forgetGuards();
        }
    }

    public function test_a_newly_registered_account_is_a_customer(): void
    {
        $user = User::factory()->create(['email' => 'someone.new@example.com']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('customer.dashboard'));
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_roles_cannot_open_each_others_dashboards(): void
    {
        $routes = [
            'admin' => ['admin.dashboard', 'admin.branches', 'admin.orders', 'admin.payments', 'admin.settings', 'admin.dashboard.metrics'],
            'branch' => ['branch.dashboard', 'branch.live-orders', 'branch.live-orders.feed', 'branch.orders', 'branch.settings', 'branch.dashboard.metrics'],
            'customer' => ['customer.dashboard', 'customer.orders', 'customer.orders.active', 'customer.favorites'],
        ];
        $users = ['admin' => $this->superAdmin(), 'branch' => $this->branchAdmin(), 'customer' => $this->customer()];

        foreach ($users as $own => $user) {
            foreach ($routes as $area => $names) {
                if ($area === $own) {
                    continue;
                }
                foreach ($names as $name) {
                    $this->actingAs($user)->get(route($name))->assertForbidden();
                }
            }
        }
    }

    public function test_state_changing_endpoints_are_role_protected(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->patchJson(route('branch.status'), ['status' => 'closed'])->assertForbidden();
        $this->actingAs($customer)->postJson(route('branch.live-orders.simulate'))->assertForbidden();
        $this->actingAs($customer)->putJson(route('admin.settings.update'), [])->assertForbidden();
        $this->actingAs($this->branchAdmin())->patchJson(route('admin.admins.update'), ['email' => 'branchadmin@test.com', 'active' => false])->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        foreach (['/dashboard', '/admin/dashboard', '/branch/live-orders', '/customer/dashboard'] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
        $this->getJson(route('branch.live-orders.feed'))->assertUnauthorized();
    }

    public function test_a_deactivated_branch_admin_loses_the_dashboard(): void
    {
        $branchAdmin = $this->branchAdmin();
        $this->actingAs($branchAdmin)->get(route('branch.dashboard'))->assertOk();

        $this->actingAs($this->superAdmin())
            ->patchJson(route('admin.admins.update'), ['email' => 'branchadmin@test.com', 'active' => false])
            ->assertOk();

        $this->actingAs($branchAdmin)->get(route('branch.dashboard'))->assertForbidden();
    }

    public function test_demo_roles_are_ignored_outside_local_and_testing(): void
    {
        config(['dashboard.demo_accounts_enabled' => false]);

        $this->actingAs($this->superAdmin())->get(route('admin.dashboard'))->assertForbidden();
    }
}
