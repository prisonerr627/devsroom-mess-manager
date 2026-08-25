<?php

namespace App\Http\Controllers;

use App\Models\Mess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostLoginRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Mirrors RootController: no mess yet -> the join/create chooser.
        if ($user && Mess::activeId() === null) {
            return redirect()->route('join.choose');
        }

        if ($user?->hasRole('super-admin')) {
            return redirect('/dashboard');
        }

        if ($user?->hasRole('manager')) {
            return redirect('/home');
        }

        if ($user?->hasRole('mess-member')) {
            return redirect('/my');
        }

        return redirect('/');
    }
}
