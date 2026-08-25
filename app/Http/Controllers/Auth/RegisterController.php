<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * App-owned signup, overriding the Tyro login package's /register so we can
 * collect a username and phone number. The new account has NO role and NO
 * mess: the /join chooser (join by code, or create a mess) assigns both.
 */
class RegisterController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (! config('tyro-login.registration.enabled', false)) {
            abort(404);
        }

        if (Auth::check()) {
            return redirect('/');
        }

        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // 'hashed' cast
        ]);
        $user->forceFill([
            'username' => $data['username'],
            'mobile' => $data['mobile'],
            // They chose this password themselves — no forced first-login reset.
            'password_changed_at' => now(),
        ])->save();

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('join.choose');
    }
}
