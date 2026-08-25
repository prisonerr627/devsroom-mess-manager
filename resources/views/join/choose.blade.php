@extends('layouts.app')
@section('content')
    <div class="mx-auto max-w-2xl">
        <header class="mb-6 text-center">
            <h1 class="text-2xl font-semibold leading-tight text-slate-900">{{ __('Welcome, :name!', ['name' => auth()->user()->name]) }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ __('You are not part of a mess yet. Join one with a code from your manager, or create a new mess and manage it yourself.') }}</p>
        </header>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <a href="{{ route('join.code') }}" class="block rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-emerald-400 hover:shadow">
                <h2 class="text-lg font-semibold text-slate-900">{{ __('Join a mess with a code') }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ __('Your manager can find the join code on the Mess settings page. You will be added as a member.') }}</p>
                <span class="btn btn-primary mt-4">{{ __('Enter join code') }}</span>
            </a>

            <a href="{{ route('onboarding.create') }}" class="block rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-emerald-400 hover:shadow">
                <h2 class="text-lg font-semibold text-slate-900">{{ __('Create a new mess') }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ __('Set up a mess and become its manager. You will get a join code to share with your members.') }}</p>
                <span class="btn btn-secondary mt-4">{{ __('Create mess') }}</span>
            </a>
        </div>

        <div class="mt-6 text-center text-sm text-slate-500">
            <form method="POST" action="{{ route('tyro-login.logout') }}" class="inline">
                @csrf
                <button type="submit" class="underline hover:text-slate-700">{{ __('Log out') }}</button>
            </form>
        </div>
    </div>
@endsection
