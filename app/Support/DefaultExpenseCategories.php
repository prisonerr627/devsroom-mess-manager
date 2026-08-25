<?php

namespace App\Support;

use App\Models\ExpenseCategory;
use App\Models\Mess;
use Illuminate\Support\Str;

/**
 * The default expense categories every mess starts with. Used both by the
 * ExpenseCategorySeeder (existing installs) and by mess creation, so a mess
 * created from the join chooser is immediately usable — without a bazar-kind
 * category, grocery submissions cannot be approved and no bazar expense can
 * be recorded.
 */
final class DefaultExpenseCategories
{
    /** @return list<array{name:string, kind:string, sort_order:int}> */
    public static function all(): array
    {
        return [
            ['name' => 'Electricity Bill', 'kind' => ExpenseKind::FIXED, 'sort_order' => 1],
            ['name' => 'Bua Bill', 'kind' => ExpenseKind::FIXED, 'sort_order' => 2],
            ['name' => 'Gas Bill', 'kind' => ExpenseKind::FIXED, 'sort_order' => 3],
            ['name' => 'Dust Bill', 'kind' => ExpenseKind::FIXED, 'sort_order' => 4],
            ['name' => 'Rent', 'kind' => ExpenseKind::FIXED, 'sort_order' => 5],
            ['name' => 'Others', 'kind' => ExpenseKind::BAZAR, 'sort_order' => 99],
        ];
    }

    /** Idempotent: creates only the defaults the mess is missing. */
    public static function seedFor(Mess $mess): void
    {
        foreach (self::all() as $cat) {
            ExpenseCategory::withoutGlobalScopes()->firstOrCreate(
                ['mess_id' => $mess->id, 'slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                    'kind' => $cat['kind'],
                    'is_default' => true,
                    'sort_order' => $cat['sort_order'],
                ]
            );
        }
    }
}
