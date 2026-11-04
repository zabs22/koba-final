<?php

/*
|--------------------------------------------------------------------------
| KOBA dashboards (super admin · branch admin · customer)
|--------------------------------------------------------------------------
|
| The dashboards run entirely on demo data for now: there are no order,
| product or branch tables. Everything the dashboards show comes from
| App\Services\Demo\DemoDataService, and anything a demo user changes
| (order status, branch open/closed, favourites, settings) is kept in the
| cache store below, so every role sees the same "live" state.
|
*/

return [

    // All demo times are Addis Ababa local time.
    'timezone' => env('DASHBOARD_TIMEZONE', 'Africa/Addis_Ababa'),

    'currency' => 'ETB',

    // How often live views (Live Orders, order tracker, KPIs on "Today") refresh.
    'poll_seconds' => 15,

    // Days of generated order history (custom ranges are capped at half of this).
    'history_days' => 120,

    // Cache store for demo state. Kept off the database on purpose.
    'demo_store' => env('DASHBOARD_DEMO_STORE', 'file'),

    /*
    | Roles without a database column: accounts listed here get a dashboard
    | role (and branch); every other account is a customer. Only honoured in
    | local/testing, so nobody can register one of these emails elsewhere and
    | become an admin. Replace with a users.role column when going live.
    */
    'demo_accounts_enabled' => in_array(env('APP_ENV', 'production'), ['local', 'testing'], true),

    'accounts' => [
        'superadmin@test.com' => ['role' => 'super_admin'],
        'branchadmin@test.com' => ['role' => 'branch_admin', 'branch' => 'atlas'],
        'kebena@test.com' => ['role' => 'branch_admin', 'branch' => 'kebena'],
        '4kilo@test.com' => ['role' => 'branch_admin', 'branch' => '4-kilo'],
        'peacock@test.com' => ['role' => 'branch_admin', 'branch' => 'peacock'],
    ],

];
