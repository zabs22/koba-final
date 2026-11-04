<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class DashboardTestCase extends TestCase
{
    use RefreshDatabase;

    protected function superAdmin(): User
    {
        return User::factory()->create(['email' => 'superadmin@test.com', 'name' => 'Meron Alemu']);
    }

    /** Runs Bole Atlas (config/dashboard.php). */
    protected function branchAdmin(): User
    {
        return User::factory()->create(['email' => 'branchadmin@test.com', 'name' => 'Abel Tesfaye']);
    }

    protected function customer(): User
    {
        return User::factory()->create(['email' => 'customer@test.com', 'name' => 'Selam Tesfaye']);
    }
}
