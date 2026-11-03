<?php

namespace App\Services\Demo;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Everything a demo user changes, kept in a cache store instead of the
 * database: order status overrides, simulated orders, branch status and
 * edits, admin assignments, favourites, read notifications and settings.
 */
class DemoState
{
    private const PREFIX = 'koba-demo:';

    private Repository $store;

    public function __construct()
    {
        $this->store = Cache::store(config('dashboard.demo_store'));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store->get(self::PREFIX.$key, $default);
    }

    public function put(string $key, mixed $value, ?int $days = null): void
    {
        $days === null
            ? $this->store->forever(self::PREFIX.$key, $value)
            : $this->store->put(self::PREFIX.$key, $value, now()->addDays($days));
    }

    public function remember(string $key, callable $make): mixed
    {
        return $this->store->rememberForever(self::PREFIX.$key, $make);
    }

    /** Read-modify-write of an array value. */
    public function update(string $key, callable $change, ?int $days = null): array
    {
        $value = $change((array) $this->get($key, []));
        $this->put($key, $value, $days);

        return $value;
    }
}
