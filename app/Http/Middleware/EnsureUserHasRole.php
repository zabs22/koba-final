<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('role:super_admin') or 'role:branch_admin,super_admin'.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $allowed = array_map(fn (string $role) => UserRole::from($role), $roles);

        abort_unless($user && $user->hasRole(...$allowed), 403);

        // A deactivated (or unassigned) branch admin keeps their login but loses the dashboard.
        if ($user->hasRole(UserRole::BranchAdmin)) {
            abort_unless($user->isActiveAdmin() && $user->branchKey(), 403, 'This branch admin account is not active.');
        }

        return $next($request);
    }
}
