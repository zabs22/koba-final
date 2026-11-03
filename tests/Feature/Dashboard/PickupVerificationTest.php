<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Services\Demo\DemoDataService;
use App\Services\Demo\DemoState;
use App\Support\Dashboard\PickupCode;

/**
 * Website order → pickup code and QR → the branch finds it by code → staff
 * confirm the hand-over. Finding never completes an order, and a branch only
 * ever sees its own orders.
 */
class PickupVerificationTest extends DashboardTestCase
{
    private function readyOrder(string $branch = 'bole-atlas'): array
    {
        $data = app(DemoDataService::class);
        $order = $data->setStatus($data->placeOrder($branch), 'preparing');

        return $data->setStatus($order, 'ready');
    }

    private function sanfordAdmin(): User
    {
        return User::factory()->create(['email' => 'sanford@test.com', 'name' => 'Hanna Girma']);
    }

    private function cakeOrder(array $overrides = []): array
    {
        return [
            'name' => 'Eyob Shemeles',
            'phone' => '+251 911 000 000',
            'branch' => 'sanford',
            'items' => [['slug' => 'trio', 'name' => 'Le Damier (Trio)', 'qty' => 1, 'size' => 'large']],
            'notes' => 'Happy birthday, Hana',
            ...$overrides,
        ];
    }

    public function test_codes_carry_the_branch_letter_and_read_back_from_any_form(): void
    {
        $order = $this->readyOrder();
        $code = PickupCode::for($order);

        $this->assertMatchesRegularExpression('/^KB-B['.PickupCode::ALPHABET.']{5}$/', $code);
        foreach ([$code, strtolower($code), substr($code, 3), ' '.substr($code, 3, 3).' '.substr($code, 6).' ', PickupCode::payload($order)] as $input) {
            $this->assertSame($code, PickupCode::normalize($input), "Input: $input");
        }
        $this->assertNull(PickupCode::normalize('KB-B0O1IL'));
        $this->assertStringNotContainsString(':customer', PickupCode::payload($order));
    }

    public function test_finding_an_order_does_not_complete_it(): void
    {
        $order = $this->readyOrder();

        $this->actingAs($this->branchAdmin())->getJson(route('branch.verify.lookup', ['code' => strtolower(substr(PickupCode::for($order), 3))]))
            ->assertOk()->assertJsonPath('state', 'found')->assertJsonPath('order.id', $order['id']);

        $this->assertSame('ready', app(DemoDataService::class)->findOrder($order['id'])['status']);
    }

    public function test_confirmed_pickup_is_seen_everywhere_and_can_be_undone(): void
    {
        $order = $this->readyOrder();
        $admin = $this->branchAdmin();

        $this->actingAs($admin)->postJson(route('branch.verify.confirm', $order['id']), ['via' => 'qr'])
            ->assertOk()->assertJsonPath('order.status', 'completed');
        $this->actingAs($admin)->getJson(route('branch.verify.lookup', ['code' => PickupCode::for($order)]))
            ->assertJsonPath('state', 'already_picked_up')->assertJsonPath('order.picked_up.by', 'Abel Tesfaye');
        $this->actingAs($this->superAdmin())->getJson(route('admin.orders.show', $order['id']))->assertJsonPath('status', 'completed');

        $this->actingAs($admin)->postJson(route('branch.verify.undo', $order['id']))->assertOk()->assertJsonPath('order.status', 'ready');
        $this->assertSame('ready', app(DemoDataService::class)->findOrder($order['id'])['status']);
    }

    public function test_an_order_still_in_the_kitchen_cannot_be_confirmed(): void
    {
        $order = app(DemoDataService::class)->placeOrder('bole-atlas');
        $admin = $this->branchAdmin();

        $this->actingAs($admin)->getJson(route('branch.verify.lookup', ['code' => PickupCode::for($order)]))->assertJsonPath('state', 'not_ready');
        $this->actingAs($admin)->postJson(route('branch.verify.confirm', $order['id']))->assertUnprocessable();
    }

    public function test_another_branchs_code_shows_where_to_collect_it_and_nothing_else(): void
    {
        $order = $this->readyOrder('sanford');
        $code = PickupCode::for($order);
        $boleAtlas = $this->branchAdmin();

        $this->actingAs($boleAtlas)->getJson(route('branch.verify.lookup', ['code' => $code]))
            ->assertOk()->assertJsonPath('state', 'wrong_branch')->assertJsonPath('branch', 'KOBA Sanford')->assertJsonMissingPath('order');
        $this->actingAs($boleAtlas)->postJson(route('branch.verify.confirm', $order['id']))->assertNotFound();

        $this->actingAs($this->sanfordAdmin())->getJson(route('branch.verify.lookup', ['code' => $code]))->assertJsonPath('state', 'found');
    }

    public function test_wrong_codes_are_reported(): void
    {
        $admin = $this->branchAdmin();

        $this->actingAs($admin)->getJson(route('branch.verify.lookup', ['code' => 'hello']))->assertJsonPath('state', 'invalid');
        $this->actingAs($admin)->getJson(route('branch.verify.lookup', ['code' => 'KB-SZZZZZ']))->assertJsonPath('state', 'not_found');
    }

    public function test_a_website_cake_order_reaches_the_branch_with_a_pickup_pass(): void
    {
        $response = $this->postJson(route('order.store'), $this->cakeOrder())->assertOk()->assertJsonStructure(['redirect', 'order']);
        $order = app(DemoDataService::class)->findOrder($response->json('order'));

        $this->assertSame('sanford', $order['branch']);
        $this->assertSame('web', $order['source']);
        $this->assertSame(3500.0, $order['total'], 'Le Damier, large: 250 × 14 slices (demo price)');

        $this->get($response->json('redirect'))->assertOk()
            ->assertSee(PickupCode::display(PickupCode::for($order)))->assertSee('Le Damier · Large')->assertSee('Happy birthday, Hana');
        $this->get(route('order.confirmed', $order['id']))->assertForbidden();

        $pending = $this->actingAs($this->sanfordAdmin())->getJson(route('branch.live-orders.feed'))->json('columns.pending');
        $this->assertContains($order['id'], array_column($pending, 'id'));
        $this->assertSame('Eyob Shemeles', collect($pending)->firstWhere('id', $order['id'])['customer']);
    }

    public function test_a_signed_in_customer_finds_the_website_order_and_its_qr_on_the_dashboard(): void
    {
        $customer = $this->customer();
        $id = $this->actingAs($customer)->postJson(route('order.store'), $this->cakeOrder(['branch' => 'bole-atlas']))->assertOk()->json('order');

        $this->actingAs($customer)->getJson(route('customer.orders.show', $id))
            ->assertOk()->assertJsonPath('status', 'pending')->assertJsonPath('pickup_code', PickupCode::for(app(DemoDataService::class)->findOrder($id)));

        // The tracker follows the soonest active pickup (the demo history may hold an earlier one): it carries the QR.
        $active = $this->actingAs($customer)->getJson(route('customer.orders.active'))->assertOk()->json('order');
        $this->assertStringStartsWith('<svg', $active['qr']);
    }

    public function test_a_paused_branch_takes_no_website_orders(): void
    {
        app(DemoState::class)->put('branch-status:sanford', 'paused');

        $this->postJson(route('order.store'), $this->cakeOrder())->assertUnprocessable()->assertJsonValidationErrors('branch');
    }
}
