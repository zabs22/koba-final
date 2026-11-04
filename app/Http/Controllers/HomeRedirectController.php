<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * /dashboard (Fortify's "home" after login or registration) → the signed-in
 * user's own dashboard: /admin, /branch or /customer.
 */
class HomeRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->role()->homeRoute());
    }
}
