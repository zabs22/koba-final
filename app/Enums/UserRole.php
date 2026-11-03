<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case BranchAdmin = 'branch_admin';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::BranchAdmin => 'Branch Admin',
            self::Customer => 'Customer',
        };
    }

    /** Route prefix and name prefix of this role's dashboard area. */
    public function area(): string
    {
        return match ($this) {
            self::SuperAdmin => 'admin',
            self::BranchAdmin => 'branch',
            self::Customer => 'customer',
        };
    }

    public function homeRoute(): string
    {
        return $this->area().'.dashboard';
    }
}
