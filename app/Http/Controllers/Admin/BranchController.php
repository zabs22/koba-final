<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AdminDashboardService;
use App\Services\Dashboard\BranchDashboardService;
use App\Services\Demo\DemoDataService;
use App\Services\Demo\DemoState;
use App\Support\Dashboard\DateRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(
        private AdminDashboardService $admin,
        private BranchDashboardService $branches,
        private DemoDataService $data,
        private DemoState $state,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $rows = $this->admin->branchesTable(DateRange::fromRequest($request, '30d'));

        return $request->wantsJson() ? response()->json(['rows' => $rows]) : view('admin.branches', ['rows' => $rows]);
    }

    public function show(Request $request, string $branch): View
    {
        $info = $this->data->branch($branch) ?? abort(404);

        return view('admin.branch-show', [
            'branch' => $info,
            'payload' => $this->branches->overview($branch, DateRange::fromRequest($request, '30d')),
            'settings' => $this->branches->settings($branch),
        ]);
    }

    public function metrics(Request $request, string $branch): JsonResponse
    {
        abort_unless($this->data->branch($branch), 404);

        return response()->json($this->branches->overview($branch, DateRange::fromRequest($request, '30d')));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $key = Str::slug($data['short']);
        abort_if($this->data->branch($key), 422, 'A branch with that name already exists.');

        $this->state->update('branch-created', fn ($created) => [...$created, $key => [
            'key' => $key,
            'name' => $data['name'],
            'short' => $data['short'],
            'area' => $data['area'],
            'phone' => $data['phone'],
            'manager' => $data['manager'] ?? null,
            'maps' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode('KOBA '.$data['area']),
            'image' => 'croissant',
            'hours' => ['weekday' => [7, 22], 'weekend' => [7, 22]],
            'volume' => 0,
            'prep' => 0,
            'status' => $data['status'],
        ]]);
        $this->state->put("branch-status:$key", $data['status']);

        return response()->json(['message' => "{$data['short']} created.", 'rows' => $this->admin->branchesTable(DateRange::make('30d'))]);
    }

    public function update(Request $request, string $branch): JsonResponse
    {
        abort_unless($this->data->branch($branch), 404);
        $data = $this->validated($request, partial: true);

        if (isset($data['status'])) {
            $this->state->put("branch-status:$branch", $data['status']);
            unset($data['status']);
        }
        if ($data) {
            $this->state->update('branch-edits', fn ($edits) => [...$edits, $branch => [...($edits[$branch] ?? []), ...$data]]);
        }

        return response()->json(['message' => 'Branch updated.', 'branch' => $this->data->branch($branch), 'rows' => $this->admin->branchesTable(DateRange::make('30d'))]);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $rule = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$rule, 'string', 'max:80'],
            'short' => [$partial ? 'prohibited' : 'required', 'string', 'max:40'],
            'area' => [$rule, 'string', 'max:80'],
            'phone' => [$rule, 'string', 'max:30'],
            'manager' => ['nullable', 'string', 'max:80'],
            'status' => [$rule, Rule::in(['open', 'paused', 'closed'])],
        ]);
    }
}
