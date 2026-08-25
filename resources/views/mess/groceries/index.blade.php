@extends('layouts.app')
@section('content')
    <header class="mb-4">
        <h1 class="text-2xl font-semibold leading-tight text-slate-900">{{ __('Grocery submissions') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ __('Grocery purchases members paid for themselves. Approving one records it as a bazar expense under that member.') }}</p>
    </header>

    @php
        $tabs = [
            ['key' => 'pending', 'label' => __('Pending (:n)', ['n' => $counts['pending'] ?? 0]), 'url' => route('mess.groceries.index', ['tab' => 'pending'])],
            ['key' => 'approved', 'label' => __('Approved (:n)', ['n' => $counts['approved'] ?? 0]), 'url' => route('mess.groceries.index', ['tab' => 'approved'])],
            ['key' => 'rejected', 'label' => __('Rejected (:n)', ['n' => $counts['rejected'] ?? 0]), 'url' => route('mess.groceries.index', ['tab' => 'rejected'])],
        ];
    @endphp

    <x-tab-nav :tabs="$tabs" :active-key="$tab" class="mb-6" />

    @if ($tab === 'pending' && $bazarCategories->isEmpty())
        <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ __('No bazar-kind expense category exists yet — add one under Categories before approving submissions.') }}
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($submissions as $submission)
            @include('mess.groceries._card', ['submission' => $submission, 'bazarCategories' => $bazarCategories])
        @empty
            <x-empty-state
                :title="__('No :status grocery submissions.', ['status' => $tab])"
                :description="__('When members submit grocery purchases from their dashboard, they will appear here.')"
            />
        @endforelse
    </div>

    <div class="mt-4">{{ $submissions->links() }}</div>
@endsection
