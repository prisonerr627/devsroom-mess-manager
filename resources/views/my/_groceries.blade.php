<h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('Groceries (bazar)') }}</h2>
<p class="mt-1 text-sm text-slate-600">{{ __('Bought groceries for the mess? Submit the purchase here. Once your manager approves it, it is added to the mess expenses under your name.') }}</p>

<form method="POST" action="{{ route('my.groceries.store') }}" enctype="multipart/form-data" class="mt-4 rounded-lg border border-slate-200 p-4">
    @csrf
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="flex flex-col gap-1">
            <label for="date" class="text-sm font-medium text-slate-900">{{ __('Purchase date') }}<span class="text-red-600" aria-hidden="true">*</span></label>
            <input type="date" name="date" id="date" value="{{ old('date', now()->toDateString()) }}" required max="{{ now()->toDateString() }}"
                class="input @error('date') border-red-500 @enderror">
            @error('date') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div class="flex flex-col gap-1">
            <label for="amount" class="text-sm font-medium text-slate-900">{{ __('Amount') }}<span class="text-red-600" aria-hidden="true">*</span></label>
            <input type="number" name="amount" id="amount" value="{{ old('amount') }}" required min="1" step="0.01"
                class="input @error('amount') border-red-500 @enderror">
            @error('amount') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div class="flex flex-col gap-1 sm:col-span-2">
            <label for="description" class="text-sm font-medium text-slate-900">{{ __('What did you buy?') }}<span class="text-red-600" aria-hidden="true">*</span></label>
            <textarea name="description" id="description" rows="2" required maxlength="500" placeholder="{{ __('e.g. Rice 5kg, potatoes, onions, chicken 2kg') }}"
                class="input @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
            @error('description') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div class="flex flex-col gap-1">
            <label for="vendor" class="text-sm font-medium text-slate-900">{{ __('Shop / vendor') }}</label>
            <input type="text" name="vendor" id="vendor" value="{{ old('vendor') }}" maxlength="100" class="input">
            @error('vendor') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div class="flex flex-col gap-1">
            <label for="receipt" class="text-sm font-medium text-slate-900">{{ __('Receipt photo') }}</label>
            <input type="file" name="receipt" id="receipt" accept="image/jpeg,image/png,image/webp" class="input">
            <p class="text-xs text-slate-500">{{ __('Optional. JPG, PNG or WebP, up to 2 MB.') }}</p>
            @error('receipt') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
    </div>
    <button type="submit" class="btn btn-primary mt-4">{{ __('Submit purchase') }}</button>
</form>

<h3 class="mt-6 text-base font-semibold text-slate-900">{{ __('My submissions') }}</h3>
@if ($grocerySubmissions->isEmpty())
    <p class="mt-2 text-sm text-slate-600">{{ __('No grocery purchases submitted yet.') }}</p>
@else
    <ul class="mt-2 divide-y divide-slate-200">
        @foreach ($grocerySubmissions as $submission)
            <li class="py-3 text-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-slate-600">{{ $submission->date->format('d M Y') }}</span>
                    <span class="font-semibold text-slate-900">{{ number_format((float) $submission->amount, 2) }}</span>
                    <x-status-pill :variant="$submission->status" />
                </div>
                <p class="mt-1 text-slate-700">{{ $submission->description }}@if ($submission->vendor) <span class="text-slate-500">— {{ $submission->vendor }}</span>@endif</p>
                @if ($submission->status === \App\Support\GroceryStatus::REJECTED && $submission->rejection_reason)
                    <p class="mt-1 text-red-700"><span class="font-medium">{{ __('Reason:') }}</span> {{ $submission->rejection_reason }}</p>
                @elseif ($submission->status === \App\Support\GroceryStatus::APPROVED && $submission->expense && (float) $submission->expense->amount !== (float) $submission->amount)
                    <p class="mt-1 text-xs text-slate-500">{{ __('Approved for :amount.', ['amount' => number_format((float) $submission->expense->amount, 2)]) }}</p>
                @endif
            </li>
        @endforeach
    </ul>
    <div class="mt-3">{{ $grocerySubmissions->links() }}</div>
@endif
