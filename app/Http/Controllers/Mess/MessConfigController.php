<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mess\UpdateMessRequest;
use App\Models\Mess;
use App\Models\Setting;
use App\Services\BillPreviewService;
use App\Support\MealGridPrefs;
use App\Support\MealType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MessConfigController extends Controller
{
    public function __construct(
        private readonly BillPreviewService $billPreview,
    ) {}

    public function edit(): View
    {
        $mess = Mess::findOrFail(Mess::activeId());

        return view('mess.settings.edit', [
            'mess' => $mess,
            'mealValues' => $this->mealValues($mess->id),
            'mealGridPrefs' => MealGridPrefs::get(),
        ]);
    }

    public function update(UpdateMessRequest $request): RedirectResponse
    {
        $mess = Mess::findOrFail(Mess::activeId());
        $mess->update($request->validated());

        $this->persistMealValues($mess->id, $request);
        $this->persistMealGridPrefs($mess->id, $request);

        // Weights feed every meal total / rate, so drop their cache + the
        // current month's bill-preview so the next read recomputes.
        MealType::forgetFor($mess->id);
        $this->billPreview->invalidate(now()->year, now()->month);

        return redirect()
            ->route('mess.settings.edit')
            ->with('success', __('Mess settings updated.'));
    }

    /**
     * Current per-meal weights for the form, falling back to defaults when
     * a setting row is missing (e.g. mess created before this UI existed).
     *
     * @return array{breakfast:float,lunch:float,dinner:float}
     */
    private function mealValues(int $messId): array
    {
        $rows = Setting::query()
            ->where('mess_id', $messId)
            ->whereIn('key', MealType::SETTING_KEYS)
            ->get(['key', 'value'])
            ->keyBy('key');

        return [
            'breakfast' => $this->amount($rows, 'meal_breakfast', MealType::DEFAULT_BREAKFAST),
            'lunch' => $this->amount($rows, 'meal_lunch', MealType::DEFAULT_LUNCH),
            'dinner' => $this->amount($rows, 'meal_dinner', MealType::DEFAULT_DINNER),
        ];
    }

    private function amount($rows, string $key, float $default): float
    {
        $amount = $rows[$key]?->value['amount'] ?? null;

        return is_numeric($amount) ? (float) $amount : $default;
    }

    private function persistMealValues(int $messId, UpdateMessRequest $request): void
    {
        $labels = [
            'meal_breakfast' => 'Breakfast',
            'meal_lunch' => 'Lunch',
            'meal_dinner' => 'Dinner',
        ];

        foreach ($labels as $key => $label) {
            if (! $request->has($key)) {
                continue;
            }

            Setting::updateOrCreate(
                ['mess_id' => $messId, 'key' => $key],
                [
                    'value' => ['amount' => (float) $request->input($key)],
                    'type' => 'number',
                    'group' => 'meals',
                    'description' => $label.' meal value',
                ],
            );
        }
    }

    /**
     * Persist the meal grid preferences (visible columns + pre-ticked
     * defaults). Only runs when the form actually contained the section —
     * unchecked checkboxes are absent from the request, so without the
     * marker an unrelated form post would wrongly hide every meal.
     */
    private function persistMealGridPrefs(int $messId, UpdateMessRequest $request): void
    {
        if (! $request->boolean('meal_grid_prefs_present')) {
            return;
        }

        $prefs = ['visible' => [], 'default_on' => []];

        foreach (MealType::ALL as $meal) {
            $prefs['visible'][$meal] = (bool) $request->input("meal_visible.$meal", false);
            $prefs['default_on'][$meal] = (bool) $request->input("meal_default.$meal", false);
        }

        Setting::updateOrCreate(
            ['mess_id' => $messId, 'key' => MealGridPrefs::KEY],
            [
                'value' => $prefs,
                'type' => 'json',
                'group' => 'meals',
                'description' => 'Meal grid visibility and default ticks',
            ],
        );

        MealGridPrefs::forgetFor($messId);
    }
}
