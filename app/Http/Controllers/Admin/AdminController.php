<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AdminDashboardService;
use App\Services\Demo\DemoDataService;
use App\Services\Demo\DemoState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Branch admins: assign / reassign a branch, activate / deactivate.
 * Changes apply to the real demo login (branchadmin@test.com) immediately.
 */
class AdminController extends Controller
{
    public function __construct(private AdminDashboardService $admin, private DemoDataService $data, private DemoState $state) {}

    public function index(): View
    {
        return view('admin.admins', ['rows' => $this->admin->admins(), 'branches' => $this->data->branches()]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', Rule::in(array_column($this->admin->admins(), 'email'))],
            'branch' => ['sometimes', Rule::in(array_keys($this->data->branches()))],
            'active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['branch'])) {
            $this->state->put('admin-branch:'.$data['email'], $data['branch']);
        }
        if (isset($data['active'])) {
            $this->state->update('admins-inactive', fn ($list) => $data['active']
                ? array_values(array_diff($list, [$data['email']]))
                : array_values(array_unique([...$list, $data['email']])));
        }

        return response()->json(['message' => 'Admin updated.', 'rows' => $this->admin->admins()]);
    }
}
