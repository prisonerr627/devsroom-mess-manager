@php
    $mealLabels = collect(['breakfast' => __('Breakfast'), 'lunch' => __('Lunch'), 'dinner' => __('Dinner')])
        ->only($visibleMeals ?? ['breakfast', 'lunch', 'dinner']);
@endphp

<section class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50/50 p-4">
    <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __("Today's meal") }} <span class="text-sm font-normal text-slate-600">({{ now()->format('d M, l') }})</span></h2>
    @if ($canEditToday ?? false)
        <form method="POST" action="{{ route('my.meals.today.save') }}" class="mt-3">
            @csrf
            <div class="flex flex-wrap gap-4">
                @foreach ($mealLabels as $meal => $mealLabel)
                    <label class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-900">
                        <input type="checkbox" name="{{ $meal }}" value="1"
                            @checked($todayMeals[$meal] ?? false)
                            class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring focus:ring-emerald-600 focus:ring-offset-1">
                        {{ $mealLabel }}
                    </label>
                @endforeach
            </div>
            <button type="submit" class="btn btn-primary mt-3">{{ __("Save today's meal") }}</button>
        </form>
    @else
        <p class="mt-2 text-sm text-slate-600">{{ __("Today's meal can't be changed right now (mess closed, month closed, or you are on meal off). Contact your manager if this looks wrong.") }}</p>
    @endif
</section>

<section class="mb-6 rounded-lg border border-slate-200 p-4">
    <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('Guest meals') }}</h2>
    <p class="mt-1 text-xs text-slate-500">{{ __('Bringing a guest today? Record their meal here — it is charged to your bill.') }}</p>
    @if ($canEditToday ?? false)
        <form method="POST" action="{{ route('my.guest-meals.store') }}" class="mt-3 flex flex-wrap items-end gap-3">
            @csrf
            <div class="flex min-w-[10rem] flex-1 flex-col gap-1">
                <label for="guest_name" class="text-sm font-medium text-slate-900">{{ __('Guest name') }}</label>
                <input type="text" name="guest_name" id="guest_name" value="{{ old('guest_name') }}" required maxlength="100"
                    class="input @error('guest_name') border-red-500 @enderror">
            </div>
            <div class="flex flex-col gap-1">
                <label for="meal_type" class="text-sm font-medium text-slate-900">{{ __('Meal') }}</label>
                <select name="meal_type" id="meal_type" required class="input @error('meal_type') border-red-500 @enderror">
                    @foreach ($mealLabels as $meal => $mealLabel)
                        <option value="{{ $meal }}" @selected(old('meal_type') === $meal)>{{ $mealLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex w-24 flex-col gap-1">
                <label for="quantity" class="text-sm font-medium text-slate-900">{{ __('Guests') }}</label>
                <input type="number" name="quantity" id="quantity" value="{{ old('quantity', 1) }}" required min="1" max="20" step="1"
                    class="input @error('quantity') border-red-500 @enderror">
            </div>
            <button type="submit" class="btn btn-secondary">{{ __('Add guest meal') }}</button>
        </form>
        @error('guest_name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        @error('meal_type') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        @error('quantity') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    @endif

    @if (($myGuestMeals ?? collect())->isNotEmpty())
        <ul class="mt-3 divide-y divide-slate-200">
            @foreach ($myGuestMeals as $guestMeal)
                <li class="flex items-center justify-between gap-2 py-2 text-sm">
                    <span class="text-slate-600">{{ $guestMeal->date->format('d M') }}</span>
                    <span class="flex-1 truncate text-slate-900">{{ $guestMeal->guest_name }}</span>
                    <span class="text-slate-600">{{ $mealLabels[$guestMeal->meal_type] ?? ucfirst($guestMeal->meal_type) }} × {{ rtrim(rtrim(number_format((float) $guestMeal->quantity, 2), '0'), '.') }}</span>
                    <span class="font-medium text-slate-900">{{ number_format((float) $guestMeal->charge_amount, 2) }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</section>

<h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('My meals (this month)') }}</h2>
@if ($mealEntries->isEmpty())
    <p class="mt-2 text-sm text-slate-600">{{ __('No meals recorded for you this month yet.') }}</p>
@else
    <ul class="mt-3 divide-y divide-slate-200">
        @foreach ($mealEntries as $entry)
            <li class="flex items-center justify-between py-2 text-sm">
                <span class="text-slate-600">{{ $entry->date->format('d M, l') }}</span>
                <span class="text-slate-900">
                    @php
                        $meals = collect([
                            $entry->breakfast ? 'B' : null,
                            $entry->lunch ? 'L' : null,
                            $entry->dinner ? 'D' : null,
                        ])->filter()->implode(', ');
                    @endphp
                    {{ $meals ?: '—' }}
                </span>
            </li>
        @endforeach
    </ul>
@endif
