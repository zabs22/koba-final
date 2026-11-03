<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Profile inside each dashboard. The forms post to Jetstream/Fortify's own
 * endpoints (user-profile-information.update, user-password.update), so
 * validation and hashing stay with the existing auth system.
 */
class ProfileController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('dashboard.profile', [
            'user' => $request->user(),
            'layout' => 'layouts.'.$request->user()->role()->area(),
        ]);
    }
}
