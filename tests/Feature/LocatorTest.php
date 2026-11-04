<?php

namespace Tests\Feature;

use App\Services\Demo\DemoState;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * "Find the nearest café": ranked by time on the road, not distance as the crow flies,
 * still useful when routing is down or the visitor is abroad, and the choice goes
 * back to the form that asked.
 */
class LocatorTest extends TestCase
{
    /** Meskel Square: Peacock is closest in a straight line (1.9 km), Atlas next (2.3 km). */
    private const MESKEL = ['lat' => 9.0106, 'lng' => 38.7613];

    /** Fakes OSRM: a table of driving minutes per café, and a short route. */
    private function fakeRoads(array $minutes): void
    {
        $branches = trans('koba.locations.branches');
        $keyAt = fn (string $point) => collect($branches)->search(function (array $b) use ($point) {
            [$lng, $lat] = array_map('floatval', explode(',', $point));

            return abs($b['coords'][0] - $lat) < 1e-5 && abs($b['coords'][1] - $lng) < 1e-5;
        });

        Http::fake(function (Request $request) use ($minutes, $keyAt) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if (str_starts_with($path, '/table/v1/driving/')) {
                $destinations = array_slice(explode(';', substr($path, strlen('/table/v1/driving/'))), 1);
                $durations = [0, ...array_map(fn ($p) => $minutes[$keyAt($p)] * 60, $destinations)];
                $distances = [0, ...array_map(fn ($p) => $minutes[$keyAt($p)] * 450, $destinations)];

                return Http::response(['code' => 'Ok', 'durations' => [$durations], 'distances' => [$distances]]);
            }

            return Http::response(['code' => 'Ok', 'routes' => [[
                'distance' => 3092, 'duration' => 236,
                'geometry' => ['type' => 'LineString', 'coordinates' => [[38.7612, 9.0103], [38.7702, 9.0051], [38.7802, 9.0009]]],
            ]]]);
        });
    }

    public function test_cafes_are_ranked_by_driving_time_not_straight_line(): void
    {
        $this->fakeRoads(['peacock' => 14, 'atlas' => 6, '4-kilo' => 11, 'kebena' => 12]);

        $result = $this->postJson(route('locator.nearest'), [...self::MESKEL, 'accuracy' => 20])->assertOk()->json();

        $this->assertTrue($result['in_area']);
        $this->assertSame('roads', $result['source']);
        $this->assertSame('peacock', collect($result['branches'])->sortBy('straight_m')->first()['key'], 'Closest as the crow flies…');
        $this->assertSame('atlas', $result['recommended'], '…but Atlas is quicker by road');
        $this->assertSame(['atlas', '4-kilo', 'kebena', 'peacock'], array_column($result['branches'], 'key'));
        $this->assertGreaterThanOrEqual(6 * 60, $result['branches'][0]['drive_s'], 'Traffic only ever adds time');
        $this->assertCount(19, $result['origin']['ring']);
        Http::assertSentCount(1); // one table request; the road line is asked for separately
    }

    public function test_the_routing_service_only_gets_the_area_cell_for_ranking(): void
    {
        $this->fakeRoads(['peacock' => 14, 'atlas' => 6, '4-kilo' => 11, 'kebena' => 12]);

        $this->postJson(route('locator.nearest'), ['lat' => 9.0142, 'lng' => 38.7655])->assertOk();

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/table/') && ! str_contains($request->url(), '38.765500,9.014200'));
    }

    public function test_estimates_keep_the_page_working_when_routing_is_down(): void
    {
        Http::fake(fn () => Http::response('Service unavailable', 503));

        $result = $this->postJson(route('locator.nearest'), self::MESKEL)->assertOk()->json();

        $this->assertSame('estimate', $result['source']);
        $this->assertSame('peacock', $result['recommended'], 'Straight-line order, scaled to roads');
        $this->assertTrue(collect($result['branches'])->every(fn ($b) => $b['estimated'] && $b['drive_s'] > 0));
        $this->postJson(route('locator.route'), [...self::MESKEL, 'branch' => 'peacock'])->assertOk()->assertJsonPath('route', null);
    }

    public function test_visitors_far_from_addis_get_distances_only(): void
    {
        Http::fake();

        $result = $this->postJson(route('locator.nearest'), ['lat' => 52.3676, 'lng' => 4.9041])->assertOk()->json();

        $this->assertFalse($result['in_area']);
        $this->assertGreaterThan(5_000_000, $result['branches'][0]['straight_m']);
        $this->assertNull($result['origin']['cell']);
        Http::assertNothingSent();
    }

    public function test_a_cafe_not_taking_pre_orders_is_skipped_for_cake_orders(): void
    {
        $this->fakeRoads(['peacock' => 14, 'atlas' => 6, '4-kilo' => 11, 'kebena' => 12]);
        app(DemoState::class)->put('branch-status:atlas', 'paused');

        $order = $this->postJson(route('locator.nearest'), [...self::MESKEL, 'for' => 'order'])->assertOk();
        $order->assertJsonPath('nearest', 'atlas')->assertJsonPath('recommended', '4-kilo');

        $this->postJson(route('locator.nearest'), [...self::MESKEL, 'for' => 'contact'])->assertJsonPath('recommended', 'atlas');
    }

    public function test_coordinates_are_validated(): void
    {
        $this->postJson(route('locator.nearest'), ['lat' => 123, 'lng' => 38.7])->assertUnprocessable()->assertJsonValidationErrors('lat');
        $this->postJson(route('locator.nearest'), [...self::MESKEL, 'for' => 'elsewhere'])->assertUnprocessable();
        $this->postJson(route('locator.route'), [...self::MESKEL, 'branch' => 'nowhere'])->assertUnprocessable();
    }

    public function test_route_to_another_cafe(): void
    {
        $this->fakeRoads(['peacock' => 14, 'atlas' => 6, '4-kilo' => 11, 'kebena' => 12]);

        $this->postJson(route('locator.route'), [...self::MESKEL, 'branch' => 'kebena'])
            ->assertOk()->assertJsonPath('route.branch', 'kebena')->assertJsonCount(3, 'route.coordinates');
    }

    public function test_the_page_hands_the_cafe_back_to_the_form_that_asked(): void
    {
        $this->get(route('locator', ['for' => 'order']))->assertOk()
            ->assertSee(route('order'), false)
            ->assertSee(trans('koba.locator.use'))
            ->assertSee(trans('koba.locations.branches.peacock.short'));

        // Only known forms are valid returns; anything else is the standalone page.
        $this->get(route('locator', ['for' => 'https://evil.example']))->assertOk()
            ->assertDontSee('evil.example')->assertSee(trans('koba.locator.order_here'));
    }

    public function test_the_forms_preselect_the_cafe_from_the_locator(): void
    {
        $this->get(route('order', ['branch' => 'atlas']))->assertOk()
            ->assertSee('value="atlas" selected', false)
            ->assertSee(route('locator', ['for' => 'order']), false);
        $this->get(route('contact', ['branch' => 'kebena']))->assertOk()->assertSee('value="kebena" selected', false);
        $this->get(route('order', ['branch' => 'nowhere']))->assertOk()->assertDontSee('value="nowhere"', false);
    }
}
