<?php

namespace App\Services\Demo;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Who is an admin, and of which branch, without a database column: the
 * accounts in config/dashboard.php, plus what the super admin changed in the
 * Admin Management screen (stored in DemoState).
 */
class DemoAccounts
{
    public function __construct(private DemoState $state) {}

    public function role(User $user): UserRole
    {
        return UserRole::tryFrom((string) ($this->account($user)['role'] ?? '')) ?? UserRole::Customer;
    }

    public function branch(User $user): ?string
    {
        if ($this->role($user) !== UserRole::BranchAdmin) {
            return null;
        }

        return $this->state->get('admin-branch:'.$this->key($user), $this->account($user)['branch'] ?? null);
    }

    public function active(User $user): bool
    {
        return ! in_array($this->key($user), (array) $this->state->get('admins-inactive', []), true);
    }

    private function account(User $user): array
    {
        if (! config('dashboard.demo_accounts_enabled')) {
            return [];
        }

        return config('dashboard.accounts')[$this->key($user)] ?? [];
    }

    private function key(User $user): string
    {
        return Str::lower((string) $user->email);
    }
}
