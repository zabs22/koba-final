<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AdminDashboardService;
use App\Services\Demo\DemoDataService;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private AdminDashboardService $admin, private DemoDataService $data) {}

    public function index(): View
    {
        $rows = $this->admin->customersTable();

        return view('admin.customers', [
            'rows' => $rows,
            'segments' => array_count_values(array_column($rows, 'segment')),
            'branches' => $this->data->branches(),
        ]);
    }
}
