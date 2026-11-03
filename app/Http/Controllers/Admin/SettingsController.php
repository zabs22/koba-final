<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AdminDashboardService;
use App\Services\Demo\DemoState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private AdminDashboardService $admin, private DemoState $state) {}

    public function edit(): View
    {
        return view('admin.settings', ['settings' => $this->admin->settings()]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform_name' => ['required', 'string', 'max:60'],
            'currency' => ['required', 'in:ETB'],
            'commission' => ['required', 'numeric', 'min:0', 'max:30'],
            'opens' => ['required', 'date_format:H:i'],
            'closes' => ['required', 'date_format:H:i', 'after:opens'],
            'prep_target' => ['required', 'integer', 'min:5', 'max:60'],
            'slot_minutes' => ['required', 'integer', 'in:10,15,20,30'],
            'support_email' => ['required', 'email'],
        ]);

        $this->state->put('settings', $data);

        return response()->json(['message' => 'Platform settings saved.']);
    }
}
