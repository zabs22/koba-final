<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local-only test accounts for the three dashboards. Roles and branches come
 * from config/dashboard.php (no role column yet); branches are KOBA's real
 * cafés from lang/en/koba.php: branchadmin@test.com runs Atlas, and
 * kebena@, 4kilo@ and peacock@test.com run the other three.
 */
class DemoUserSeeder extends Seeder
{
    public const PASSWORD = 'Password123!';

    /** Demo logins of branches that were renamed (Sanford → Kebena, 4 Killo → 4 Kilo). */
    private const RETIRED = ['sanford@test.com', '4killo@test.com'];

    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('DemoUserSeeder only runs in the local environment.');

            return;
        }

        User::whereIn('email', self::RETIRED)->delete();

        $accounts = [
            ['email' => 'superadmin@test.com', 'name' => 'Meron Alemu'],
            ['email' => 'branchadmin@test.com', 'name' => 'Abel Tesfaye'],
            ['email' => 'kebena@test.com', 'name' => 'Hanna Girma'],
            ['email' => '4kilo@test.com', 'name' => 'Dawit Alemu'],
            ['email' => 'peacock@test.com', 'name' => 'Rahel Bekele'],
            ['email' => 'customer@test.com', 'name' => 'Selam Tesfaye'],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                ['name' => $account['name'], 'password' => Hash::make(self::PASSWORD), 'email_verified_at' => now()],
            );
        }

        $this->command?->info('Demo accounts ready (password: '.self::PASSWORD.'): '.implode(' · ', array_column($accounts, 'email')));
    }
}
