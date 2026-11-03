<?php

namespace App\Support\Dashboard;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Sidebar / top navigation per role; also feeds the command palette.
 * Hiding a link is not access control: every route is guarded by role middleware.
 */
final class Navigation
{
    /** @return list<array{label: ?string, items: list<array{label: string, route: string, icon: string, keywords?: string}>}> */
    public static function groups(User $user): array
    {
        return match ($user->role()) {
            UserRole::SuperAdmin => [
                ['label' => null, 'items' => [
                    ['label' => 'Overview', 'route' => 'admin.dashboard', 'icon' => 'layout-dashboard', 'keywords' => 'home kpi analytics'],
                ]],
                ['label' => 'Operations', 'items' => [
                    ['label' => 'Branches', 'route' => 'admin.branches', 'icon' => 'store', 'keywords' => 'cafes locations'],
                    ['label' => 'Branch admins', 'route' => 'admin.admins', 'icon' => 'user-cog', 'keywords' => 'managers staff'],
                    ['label' => 'Customers', 'route' => 'admin.customers', 'icon' => 'users'],
                    ['label' => 'All orders', 'route' => 'admin.orders', 'icon' => 'receipt-text'],
                ]],
                ['label' => 'Menu', 'items' => [
                    ['label' => 'Products', 'route' => 'admin.products', 'icon' => 'croissant', 'keywords' => 'items menu'],
                    ['label' => 'Categories', 'route' => 'admin.categories', 'icon' => 'tags'],
                ]],
                ['label' => 'Finance', 'items' => [
                    ['label' => 'Payments', 'route' => 'admin.payments', 'icon' => 'credit-card', 'keywords' => 'transactions telebirr chapa'],
                    ['label' => 'Platform revenue', 'route' => 'admin.revenue', 'icon' => 'trending-up', 'keywords' => 'commission payouts'],
                    ['label' => 'Reports', 'route' => 'admin.reports', 'icon' => 'chart-column', 'keywords' => 'csv export analytics'],
                ]],
                ['label' => 'System', 'items' => [
                    ['label' => 'Platform settings', 'route' => 'admin.settings', 'icon' => 'settings'],
                ]],
            ],
            UserRole::BranchAdmin => [
                ['label' => null, 'items' => [
                    ['label' => 'Overview', 'route' => 'branch.dashboard', 'icon' => 'layout-dashboard', 'keywords' => 'home kpi'],
                    ['label' => 'Live orders', 'route' => 'branch.live-orders', 'icon' => 'flame', 'keywords' => 'kitchen board kanban'],
                    ['label' => 'Pickup verification', 'route' => 'branch.verify', 'icon' => 'scan-qr-code', 'keywords' => 'qr kb code scan collect counter'],
                ]],
                ['label' => 'Service', 'items' => [
                    ['label' => 'Orders', 'route' => 'branch.orders', 'icon' => 'receipt-text'],
                    ['label' => 'Pickup schedule', 'route' => 'branch.pickups', 'icon' => 'calendar-clock', 'keywords' => 'slots timeline'],
                    ['label' => 'Customers', 'route' => 'branch.customers', 'icon' => 'users'],
                ]],
                ['label' => 'Menu', 'items' => [
                    ['label' => 'Products', 'route' => 'branch.products', 'icon' => 'croissant', 'keywords' => 'stock availability'],
                    ['label' => 'Categories', 'route' => 'branch.categories', 'icon' => 'tags'],
                ]],
                ['label' => 'Money', 'items' => [
                    ['label' => 'Payments', 'route' => 'branch.payments', 'icon' => 'credit-card'],
                    ['label' => 'Reports', 'route' => 'branch.reports', 'icon' => 'chart-column', 'keywords' => 'csv export'],
                ]],
                ['label' => 'Branch', 'items' => [
                    ['label' => 'Branch settings', 'route' => 'branch.settings', 'icon' => 'settings', 'keywords' => 'hours slots'],
                ]],
            ],
            UserRole::Customer => [
                ['label' => null, 'items' => [
                    ['label' => 'Dashboard', 'route' => 'customer.dashboard', 'icon' => 'house'],
                    ['label' => 'My orders', 'route' => 'customer.orders', 'icon' => 'receipt-text', 'keywords' => 'history reorder'],
                    ['label' => 'Pickups', 'route' => 'customer.pickups', 'icon' => 'calendar-clock'],
                    ['label' => 'Favorites', 'route' => 'customer.favorites', 'icon' => 'heart', 'keywords' => 'cafes items'],
                    ['label' => 'Notifications', 'route' => 'customer.notifications', 'icon' => 'bell'],
                ]],
            ],
        };
    }

    /** Flat list for the command palette (pages, profile, the shop, theme). */
    public static function palette(User $user): array
    {
        $area = $user->role()->area();
        $items = [];

        foreach (self::groups($user) as $group) {
            foreach ($group['items'] as $item) {
                $items[] = ['label' => $item['label'], 'href' => route($item['route']), 'icon' => $item['icon'], 'group' => 'Pages', 'keywords' => $item['keywords'] ?? ''];
            }
        }

        return [
            ...$items,
            ['label' => 'Profile', 'href' => route("$area.profile"), 'icon' => 'user-round', 'group' => 'Account', 'keywords' => 'password email name'],
            ['label' => 'Toggle dark mode', 'action' => 'theme', 'icon' => 'moon', 'group' => 'Account', 'keywords' => 'theme light dark'],
            ['label' => 'Open the KOBA website', 'href' => route('menu'), 'icon' => 'external-link', 'group' => 'Account', 'keywords' => 'shop menu cart'],
        ];
    }
}
