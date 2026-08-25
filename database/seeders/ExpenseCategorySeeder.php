<?php

namespace Database\Seeders;

use App\Models\Mess;
use App\Support\DefaultExpenseCategories;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        // These 6 defaults replace the original 13 generic categories.
        // The migration 2026_07_06_000001_update_default_expense_categories.php
        // handles the transition for existing installations; the seeder ensures
        // fresh installations (or re-seeded environments) get the right defaults.
        Mess::all()->each(fn (Mess $mess) => DefaultExpenseCategories::seedFor($mess));
    }
}
