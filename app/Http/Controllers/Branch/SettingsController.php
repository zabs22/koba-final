<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\BranchDashboardService;
use App\Services\Demo\DemoState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private BranchDashboardService $dashboard, private DemoState $state) {}

    public function edit(Request $request): View
    {
        return view('branch.settings', ['settings' => $this->dashboard->settings($request->user()->branchKey())]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'opens' => ['required', 'date_format:H:i'],
            'closes' => ['required', 'date_format:H:i', 'after:opens'],
            'weekend_opens' => ['required', 'date_format:H:i'],
            'weekend_closes' => ['required', 'date_format:H:i', 'after:weekend_opens'],
            'prep_target' => ['required', 'integer', 'min:5', 'max:60'],
            'slot_minutes' => ['required', 'integer', 'in:10,15,20,30'],
            'max_per_slot' => ['required', 'integer', 'min:1', 'max:60'],
            'auto_accept' => ['required', 'boolean'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $this->state->put('branch-settings:'.$request->user()->branchKey(), $data);

        return response()->json(['message' => 'Branch settings saved.']);
    }
}
