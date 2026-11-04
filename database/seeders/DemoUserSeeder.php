<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local-only test accounts for the three dashboards. Roles and branches come
 * from config/dashboard.php (no role column yet); branches are KOBA's real
 * cafés from the demo catalogue (branchadmin@test.com runs Bole Atlas,
 * sanford@test.com Sanford and 4killo@test.com 4 Killo).
 */
class DemoUserSeeder extends Seeder
{
    public const PASSWORD = 'Password123!';

    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('DemoUserSeeder only runs in the local environment.');

            return;
        }

        foreach ([
            ['email' => 'superadmin@test.com', 'name' => 'Meron Alemu'],
            ['email' => 'branchadmin@test.com', 'name' => 'Abel Tesfaye'],
            ['email' => 'sanford@test.com', 'name' => 'Hanna Girma'],
            ['email' => '4killo@test.com', 'name' => 'Dawit Alemu'],
            ['email' => 'customer@test.com', 'name' => 'Selam Tesfaye'],
        ] as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                ['name' => $account['name'], 'password' => Hash::make(self::PASSWORD), 'email_verified_at' => now()],
            );
        }

        $this->command?->info('Demo accounts ready (password: '.self::PASSWORD.'): superadmin@test.com · branchadmin@test.com · sanford@test.com · 4killo@test.com · customer@test.com');
    }
}
