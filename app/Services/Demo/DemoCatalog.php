<?php

namespace App\Services\Demo;

use Illuminate\Support\Str;

/**
 * Fixed demo reference data: KOBA's real branches (from lang/en/koba.php) and
 * menu, with demo prices in ETB, plus a pool of demo customers.
 */
class DemoCatalog
{
    public const STATUSES = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];

    public const STATUS_LABELS = [
        'pending' => 'New',
        'preparing' => 'Preparing',
        'ready' => 'Ready',
        'completed' => 'Picked up',
        'cancelled' => 'Cancelled',
    ];

    public const PAYMENTS = [
        'telebirr' => ['label' => 'Telebirr', 'share' => 42],
        'cbe_birr' => ['label' => 'CBE Birr', 'share' => 22],
        'chapa' => ['label' => 'Chapa', 'share' => 14],
        'cash' => ['label' => 'Cash on pickup', 'share' => 22],
    ];

    public const CATEGORIES = [
        'coffee' => ['name' => 'Coffee & Tea', 'icon' => 'coffee'],
        'cold' => ['name' => 'Cold Drinks', 'icon' => 'glass-water'],
        'pastries' => ['name' => 'Pastries', 'icon' => 'croissant'],
        'cakes' => ['name' => 'Cakes', 'icon' => 'cake-slice'],
        'breakfast' => ['name' => 'Breakfast', 'icon' => 'egg-fried'],
        'dishes' => ['name' => 'Lunch Dishes', 'icon' => 'utensils'],
        'chocolates' => ['name' => 'Chocolates', 'icon' => 'candy'],
    ];

    /** Demand by hour of day for each category (multiplies a product's base popularity). */
    public const CATEGORY_HOURS = [
        'breakfast' => [6 => 3, 7 => 3.4, 8 => 3, 9 => 2.4, 10 => 1.4, 11 => 0.6],
        'dishes' => [11 => 1.2, 12 => 3, 13 => 3, 14 => 2, 18 => 1.4, 19 => 1.6, 20 => 1.2],
        'cakes' => [15 => 1.8, 16 => 2, 17 => 1.8, 18 => 1.4, 19 => 1.3],
        'cold' => [12 => 1.4, 13 => 1.6, 14 => 1.8, 15 => 1.8, 16 => 1.5],
    ];

    /** [name on the menu, category, demo price ETB, image key, popularity] */
    private const PRODUCTS = [
        ['Macchiato', 'coffee', 85, 'drink-macchiato', 10],
        ['Cappuccino', 'coffee', 120, 'drink-cappuccino', 8],
        ['Flat White', 'coffee', 130, 'drink-flat-white', 5],
        ['Iced coffee', 'coffee', 150, 'drink-iced-latte', 5],
        ['Mocha', 'coffee', 155, 'drink-mocha', 4],
        ['Jebena Buna', 'coffee', 95, null, 6],
        ['Tea', 'coffee', 70, 'drink-tea-pour', 6],
        ['Hot Chocolate', 'coffee', 160, 'drink-chocolate-pour', 3],
        ['Affogato', 'coffee', 170, 'drink-affogato', 2],
        ['Lime & mint cooler', 'cold', 165, 'drink-mojito', 4],
        ['Strawberry cooler', 'cold', 175, 'drink-strawberry-mojito', 3],
        ['Pineapple cooler', 'cold', 175, 'drink-pineapple-mojito', 2],
        ['Layered fresh juice', 'cold', 145, 'drink-layered', 4],
        ['KOBA Croissant', 'pastries', 110, 'croissant', 9],
        ['Almond Croissant', 'pastries', 145, 'almond-croissant', 6],
        ['Éclair', 'pastries', 130, 'eclair', 4],
        ['Pecan Tart', 'pastries', 165, 'pecan-tart', 3],
        ['Bomboloni', 'pastries', 95, 'bomboloni', 4],
        ['White & Dark Chocolate Muffins', 'pastries', 120, null, 3],
        ['Vegetable Focaccia', 'pastries', 140, null, 2],
        ['Opera Caramel', 'cakes', 220, 'cake-opera-caramel', 4],
        ['Chocolate Fudge', 'cakes', 230, 'cake-chocolate-fudge', 4],
        ['Cheesecake', 'cakes', 240, 'cake-cheesecake', 4],
        ['Trio', 'cakes', 250, 'cake-trio', 2],
        ['Vanilla Éclair', 'cakes', 220, 'cake-vanilla', 2],
        ['Mocha Slice', 'cakes', 210, 'mocha-slice', 3],
        ['Chechebsa', 'breakfast', 260, null, 4],
        ['French Toast', 'breakfast', 290, null, 4],
        ['Eggs Benedict', 'breakfast', 340, null, 3],
        ['KOBA Croissant Sandwich', 'breakfast', 310, null, 4],
        ['Breakfast Combo', 'breakfast', 195, 'hero-croissant', 5],
        ['Ful Medames', 'breakfast', 230, null, 3],
        ['Club Sandwich', 'dishes', 380, null, 4],
        ['Honey Garlic Chicken', 'dishes', 520, null, 3],
        ['Beef Lasagna', 'dishes', 560, null, 2],
        ['Vegetable Curry', 'dishes', 420, null, 2],
        ['Thai Nile Perch', 'dishes', 610, null, 2],
        ['Chocolate Brownie', 'chocolates', 135, null, 3],
        ['Chocolate Fondant', 'chocolates', 210, null, 2],
    ];

    /** Demo-only branch figures: share of platform volume and kitchen speed. */
    private const BRANCH_PROFILE = [
        'sanford' => ['volume' => 50, 'prep' => 0, 'status' => 'open', 'manager' => 'Hanna Girma'],
        'bole-atlas' => ['volume' => 64, 'prep' => -1, 'status' => 'open', 'manager' => 'Abel Tesfaye'],
        '4-killo' => ['volume' => 38, 'prep' => 2, 'status' => 'open', 'manager' => 'Dawit Alemu'],
    ];

    private const FIRST = [
        'Selam', 'Hana', 'Meron', 'Liya', 'Bethlehem', 'Saron', 'Mahlet', 'Rahel', 'Tsion', 'Eden', 'Ruth', 'Kidist',
        'Helen', 'Feven', 'Yordanos', 'Hiwot', 'Senait', 'Tigist', 'Blen', 'Bezawit', 'Martha', 'Lidya', 'Mekdes', 'Sara',
        'Abel', 'Dawit', 'Yonas', 'Samuel', 'Kaleb', 'Nahom', 'Henok', 'Biniyam', 'Robel', 'Mikiyas', 'Natnael', 'Yared',
        'Eyob', 'Ermias', 'Tewodros', 'Bereket', 'Fitsum', 'Daniel', 'Elias', 'Solomon', 'Kirubel', 'Amanuel', 'Leul', 'Yohannes',
    ];

    private const LAST = [
        'Tesfaye', 'Alemu', 'Bekele', 'Kebede', 'Girma', 'Tadesse', 'Assefa', 'Desta', 'Mengistu', 'Haile', 'Gebre', 'Tilahun',
        'Worku', 'Negash', 'Ayele', 'Lemma', 'Abate', 'Mekonnen', 'Teshome', 'Yilma', 'Tsegaye', 'Fikre', 'Shiferaw', 'Wolde',
        'Abebe', 'Getachew', 'Mulugeta', 'Hailu', 'Demissie', 'Berhanu', 'Asfaw',
    ];

    public const CUSTOMER_COUNT = 160;

    private ?array $branches = null;

    private ?array $products = null;

    private ?array $customers = null;

    /** @return array<string, array> keyed by branch key */
    public function branches(): array
    {
        return $this->branches ??= collect(trans('koba.locations.branches'))
            ->map(fn (array $b, string $key) => [
                'key' => $key,
                'name' => $b['name'],
                'short' => $b['short'],
                'area' => $b['area'],
                'phone' => $b['phone'],
                'maps' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($b['maps']),
                'image' => $b['image'],
                'hours' => $b['open'],
                ...self::BRANCH_PROFILE[$key] ?? ['volume' => 20, 'prep' => 0, 'status' => 'open', 'manager' => null],
            ])->all();
    }

    /** @return array<int, array> keyed by product id */
    public function products(): array
    {
        if ($this->products !== null) {
            return $this->products;
        }

        $products = [];
        foreach (self::PRODUCTS as $i => [$name, $category, $price, $image, $popularity]) {
            $slug = Str::slug($name);
            $note = trans('koba.menu_notes.'.$slug);
            $id = $i + 1;
            $products[$id] = [
                'id' => $id,
                'sku' => 'KB-'.str_pad((string) $id, 3, '0', STR_PAD_LEFT),
                'slug' => $slug,
                'name' => $name,
                'title' => is_array($note) ? ($note['title'] ?? $name) : $name,
                'category' => $category,
                'price' => $price,
                'image' => $image,
                'image_url' => self::image($image),
                'popularity' => $popularity,
            ];
        }

        return $this->products = $products;
    }

    /**
     * Index 0 is the demo customer account (customer@test.com).
     *
     * @return array<int, array>
     */
    public function customers(): array
    {
        if ($this->customers !== null) {
            return $this->customers;
        }

        $branchKeys = array_keys($this->branches());
        $weights = array_column($this->branches(), 'volume');
        $customers = [];

        for ($i = 0; $i < self::CUSTOMER_COUNT; $i++) {
            $first = self::FIRST[($i * 7) % count(self::FIRST)];
            $last = self::LAST[($i * 11 + 3) % count(self::LAST)];
            $seed = crc32("customer|$i");
            // Home branch roughly in proportion to each branch's volume.
            $pick = $seed % array_sum($weights);
            $home = $branchKeys[0];
            foreach ($weights as $k => $w) {
                if ($pick < $w) {
                    $home = $branchKeys[$k];
                    break;
                }
                $pick -= $w;
            }

            $customers[$i] = [
                'id' => $i,
                'name' => "$first $last",
                'email' => Str::lower("$first.$last").($i >= 48 ? $i : '').'@example.com',
                'phone' => sprintf('+251 9%02d %03d %03d', 10 + $seed % 89, intdiv($seed, 100) % 1000, intdiv($seed, 7) % 1000),
                'home' => $home,
            ];
        }

        $customers[0] = [...$customers[0], 'name' => 'Selam Tesfaye', 'email' => 'customer@test.com', 'home' => 'bole-atlas'];

        return $this->customers = $customers;
    }

    /** URL of the smallest rendition of a KOBA photograph (config/koba.php), like the public site's menu. */
    public static function image(?string $key): ?string
    {
        $widths = $key ? config("koba.images.$key")[2] ?? null : null;

        return $widths ? asset("images/koba/$key-{$widths[0]}.webp") : null;
    }

    public static function initials(string $name): string
    {
        return collect(explode(' ', $name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
    }
}
