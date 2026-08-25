<?php

namespace App\Http\Controllers;

use App\Models\Mess;
use App\Services\InstallationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RootController extends Controller
{
    public function __invoke(Request $request, InstallationService $installation): RedirectResponse
    {
        if ($installation->shouldRunSetup()) {
            return redirect()->route('setup.create');
        }

        $user = $request->user();

        if (! $user) {
            return redirect()->guest('/login');
        }

        // Signed up but not attached to any mess yet: choose join-with-code
        // or create-mess before any role-based landing page makes sense.
        if (Mess::activeId() === null) {
            return redirect()->route('join.choose');
        }

        // Role-aware landing page. This controller is reached on the normal
        // login flow: a logged-out visitor hits "/" → guest('/login') stores
        // url.intended="/" → they log in → redirect()->intended('/post-login')
        // resolves the stored intended and sends them back to "/" authenticated.
        // Previously every logged-in user was sent to /dashboard, so mess members
        // (who can't access the admin panel) hit "ACCESS DENIED." and were stuck
        // with no logout link. Route each role to its own home instead. Mirrors
        // PostLoginRedirectController so the two never disagree on where a role
        // belongs.
        if ($user->hasRole('super-admin')) {
            return redirect('/dashboard');
        }

        if ($user->hasRole('manager')) {
            return redirect('/home');
        }

        if ($user->hasRole('mess-member')) {
            return redirect('/my');
        }

        // No recognized role: nothing this account can reach. Log them out and
        // return to login with a message rather than stranding them on a 403.
        // (redirect('/') here would re-enter this controller for an authed user
        // and loop, so we deliberately break the cycle with a logout.)
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('error', __('Your account does not have an assigned role. Please contact a manager.'));
    }
}
