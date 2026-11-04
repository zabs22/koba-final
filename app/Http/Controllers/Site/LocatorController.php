<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Locator\BranchLocator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "Find the nearest café": a full-screen KOBA map that locates the visitor,
 * ranks the cafés by driving time and sends the choice back to the form that
 * asked (?for=order|contact) as ?branch=…
 */
class LocatorController extends Controller
{
    /** Forms that can send visitors here, by route name. Nothing else is a valid return. */
    private const FORMS = ['order' => 'order', 'contact' => 'contact'];

    public function __construct(private BranchLocator $locator) {}

    public function show(Request $request): View
    {
        $for = array_key_exists((string) $request->query('for'), self::FORMS) ? (string) $request->query('for') : null;

        return view('locator.index', [
            'for' => $for,
            // Standalone (from the Locations page), "use this café" starts a cake order there.
            'returnTo' => route(self::FORMS[$for] ?? 'order'),
            'back' => $for ? route(self::FORMS[$for]) : $this->previous(),
            'branches' => $this->locator->branches(),
            'demoLocation' => config('dashboard.demo_accounts_enabled') ? config('koba.locator.demo_location') : null,
        ]);
    }

    public function nearest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'for' => ['nullable', Rule::in(array_keys(self::FORMS))],
        ]);

        return response()->json($this->locator->nearest(
            (float) $data['lat'],
            (float) $data['lng'],
            isset($data['accuracy']) ? (float) $data['accuracy'] : null,
            $data['for'] ?? null,
        ));
    }

    /** The road to another café, when the visitor taps it on the list. */
    public function route(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'branch' => ['required', 'string', Rule::in(array_column($this->locator->branches(), 'key'))],
        ]);

        return response()->json(['route' => $this->locator->route((float) $data['lat'], (float) $data['lng'], $data['branch'])]);
    }

    /** Back to where the visitor came from on this site, else the Locations page. */
    private function previous(): string
    {
        $previous = url()->previous();

        return str_starts_with($previous, url('/')) && ! str_contains($previous, '/find-a-cafe') ? $previous : route('locations');
    }
}
