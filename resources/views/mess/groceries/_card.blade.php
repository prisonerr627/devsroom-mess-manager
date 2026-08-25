@props(['submission', 'bazarCategories'])

<article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <header class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-base font-semibold text-slate-900">{{ $submission->member?->name ?? '—' }}</h3>
            <p class="text-sm text-slate-600">{{ $submission->date->format('d M Y') }}@if ($submission->vendor) · {{ $submission->vendor }}@endif</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-lg font-semibold text-slate-900">{{ number_format((float) $submission->amount, 2) }}</span>
            <x-status-pill :variant="$submission->status" />
        </div>
    </header>

    <div class="mt-3">
        <p class="text-sm text-slate-700">{{ $submission->description }}</p>
        <p class="mt-1 text-xs text-slate-500">
            {{ __('Submitted: :when', ['when' => $submission->created_at->diffForHumans()]) }}
            @if ($submission->receipt_path)
                · <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($submission->receipt_path) }}" target="_blank" rel="noopener" class="text-emerald-700 hover:underline">{{ __('View receipt') }}</a>
            @endif
        </p>

        @if ($submission->status === \App\Support\GroceryStatus::REJECTED && $submission->rejection_reason)
            <p class="mt-2 text-sm text-red-700"><span class="font-medium">{{ __('Rejection reason:') }}</span> {{ $submission->rejection_reason }}</p>
        @endif

        @if ($submission->status === \App\Support\GroceryStatus::APPROVED)
            <p class="mt-2 text-xs text-slate-500">
                {{ __('Approved by :who', ['who' => $submission->reviewedBy?->name ?? '—']) }}
                @if ($submission->expense)
                    · <a href="{{ route('mess.expenses.show', $submission->expense) }}" class="text-emerald-700 hover:underline">{{ __('Open expense (:amount)', ['amount' => number_format((float) $submission->expense->amount, 2)]) }}</a>
                @endif
            </p>
        @endif

        @if ($submission->status === \App\Support\GroceryStatus::PENDING)
            <div class="mt-4 flex flex-col gap-3">
                <form method="POST" action="{{ route('mess.groceries.approve', $submission) }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    @csrf
                    @method('PATCH')
                    <div class="flex flex-1 flex-col gap-1">
                        <label class="text-xs font-medium text-slate-700" for="category-{{ $submission->id }}">{{ __('Category') }}</label>
                        <select name="expense_category_id" id="category-{{ $submission->id }}" required class="input">
                            @foreach ($bazarCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex w-40 flex-col gap-1">
                        <label class="text-xs font-medium text-slate-700" for="amount-{{ $submission->id }}">{{ __('Approve amount') }}</label>
                        <input type="number" name="amount" id="amount-{{ $submission->id }}" value="{{ $submission->amount }}" min="0" step="0.01" class="input">
                    </div>
                    <button type="submit" class="btn btn-primary" @disabled($bazarCategories->isEmpty())>{{ __('Approve') }}</button>
                </form>

                <form method="POST" action="{{ route('mess.groceries.reject', $submission) }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    @csrf
                    @method('PATCH')
                    <input type="text" name="rejection_reason" required minlength="3" maxlength="500"
                        placeholder="{{ __('Reason for rejection (required)') }}" class="input flex-1">
                    <button type="submit" class="btn btn-secondary">{{ __('Reject') }}</button>
                </form>
            </div>
            @error('expense_category_id') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            @error('amount') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            @error('rejection_reason') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            @error('submission') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            @error('status') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        @endif
    </div>
</article>
