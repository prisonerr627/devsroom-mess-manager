<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Sign up') }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <main class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-4 py-10">
        <div class="mb-6 text-center">
            <a href="{{ url('/') }}" class="text-xl font-semibold text-emerald-700">{{ config('app.name') }}</a>
            <h1 class="mt-3 text-2xl font-semibold leading-tight">{{ __('Create your account') }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ __('Next you can join a mess with a code from your manager, or create your own mess.') }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-4">
                @csrf

                <div class="flex flex-col gap-1">
                    <label for="name" class="text-sm font-medium">{{ __('Full name') }}<span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name"
                        class="input @error('name') border-red-500 @enderror">
                    @error('name') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="username" class="text-sm font-medium">{{ __('Username') }}<span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="text" name="username" id="username" value="{{ old('username') }}" required minlength="3" maxlength="30" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="e.g. rahim_99"
                        class="input @error('username') border-red-500 @enderror">
                    <p class="text-xs text-slate-500">{{ __('Your manager can add you to a mess by this username.') }}</p>
                    @error('username') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="mobile" class="text-sm font-medium">{{ __('Mobile number') }}<span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="tel" name="mobile" id="mobile" value="{{ old('mobile') }}" required maxlength="30" autocomplete="tel" placeholder="01700000000"
                        class="input @error('mobile') border-red-500 @enderror">
                    @error('mobile') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="email" class="text-sm font-medium">{{ __('Email') }}<span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email"
                        class="input @error('email') border-red-500 @enderror">
                    <p class="text-xs text-slate-500">{{ __('You sign in with your email.') }}</p>
                    @error('email') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="password" class="text-sm font-medium">{{ __('Password') }}<span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="password" name="password" id="password" required minlength="8" autocomplete="new-password"
                        class="input @error('password') border-red-500 @enderror">
                    @error('password') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="password_confirmation" class="text-sm font-medium">{{ __('Confirm password') }}<span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8" autocomplete="new-password" class="input">
                </div>

                <button type="submit" class="btn btn-primary mt-2 w-full">{{ __('Sign up') }}</button>
            </form>
        </div>

        <p class="mt-4 text-center text-sm text-slate-600">
            {{ __('Already have an account?') }}
            <a href="{{ url('/login') }}" class="font-medium text-emerald-700 hover:underline">{{ __('Log in') }}</a>
        </p>
    </main>
</body>
</html>
