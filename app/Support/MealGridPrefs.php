<?php

namespace App\Support;

use App\Models\Mess;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Per-mess meal grid preferences, set by the admin on the Mess settings page:
 * which meal columns are shown on the meal grids, and which meals come
 * pre-ticked for days that have no saved entry yet.
 *
 * Stored as one settings row (key = meal_grid_prefs) shaped:
 *   { "visible": {"breakfast": true, ...}, "default_on": {"breakfast": false, ...} }
 * Missing row/keys fall back to the previous behavior: all meals visible,
 * nothing ticked by default.
 */
final class MealGridPrefs
{
    public const KEY = 'meal_grid_prefs';

    /**
     * @return array{visible: array<string, bool>, default_on: array<string, bool>}
     */
    public static function get(): array
    {
        $messId = Mess::activeId();

        $stored = $messId === null ? [] : Cache::remember(
            self::cacheKey($messId),
            now()->addHour(),
            fn () => Setting::query()
                ->where('mess_id', $messId)
                ->where('key', self::KEY)
                ->first()
                ?->value ?? []
        );

        $prefs = ['visible' => [], 'default_on' => []];

        foreach (MealType::ALL as $meal) {
            $prefs['visible'][$meal] = (bool) ($stored['visible'][$meal] ?? true);
            $prefs['default_on'][$meal] = (bool) ($stored['default_on'][$meal] ?? false);
        }

        return $prefs;
    }

    /**
     * Visible meals in grid order. Never empty: if a stored value somehow
     * hides everything, fall back to showing all meals rather than rendering
     * an unusable grid.
     *
     * @return list<string>
     */
    public static function visibleMeals(): array
    {
        $visible = array_keys(array_filter(self::get()['visible']));

        return $visible === [] ? MealType::ALL : $visible;
    }

    /**
     * @return array<string, bool> meal => pre-ticked for unsaved days
     */
    public static function defaultOn(): array
    {
        return self::get()['default_on'];
    }

    public static function cacheKey(int $messId): string
    {
        return "meal-grid-prefs:{$messId}";
    }

    public static function forgetFor(int $messId): void
    {
        Cache::forget(self::cacheKey($messId));
    }
}
