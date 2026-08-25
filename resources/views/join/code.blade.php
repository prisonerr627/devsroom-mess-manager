@extends('layouts.app')
@section('content')
    <div class="mx-auto max-w-lg">
        <header class="mb-6">
            <h1 class="text-2xl font-semibold leading-tight text-slate-900">{{ __('Join a mess') }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ __('Enter the join code your manager gave you, plus the details the mess keeps for each member.') }}</p>
        </header>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
            <form method="POST" action="{{ route('join.code.store') }}" class="flex flex-col gap-4">
                @csrf

                <div class="flex flex-col gap-1">
                    <label for="join_code" class="text-sm font-medium text-slate-900">
                        {{ __('Join code') }}<span class="text-red-600" aria-hidden="true">*</span>
                    </label>
                    <input type="text" name="join_code" id="join_code" value="{{ old('join_code') }}" required maxlength="20"
                        autocapitalize="characters" autocomplete="off" spellcheck="false"
                        class="input font-mono uppercase tracking-widest @error('join_code') border-red-500 @enderror">
                    @error('join_code') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="name" class="text-sm font-medium text-slate-900">
                        {{ __('Your name') }}<span class="text-red-600" aria-hidden="true">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', auth()->user()->name) }}" required maxlength="255"
                        class="input @error('name') border-red-500 @enderror">
                    @error('name') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="mobile" class="text-sm font-medium text-slate-900">
                        {{ __('Mobile') }}<span class="text-red-600" aria-hidden="true">*</span>
                    </label>
                    <input type="tel" name="mobile" id="mobile" value="{{ old('mobile') }}" required maxlength="30" placeholder="01700000000"
                        class="input @error('mobile') border-red-500 @enderror">
                    @error('mobile') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="room_or_seat" class="text-sm font-medium text-slate-900">{{ __('Room / seat') }}</label>
                    <input type="text" name="room_or_seat" id="room_or_seat" value="{{ old('room_or_seat') }}" maxlength="50" class="input">
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Join mess') }}</button>
                    <a href="{{ route('join.choose') }}" class="btn btn-ghost">{{ __('Back') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
