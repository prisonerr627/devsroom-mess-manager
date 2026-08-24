<?php

namespace App\Http\Requests\My;

use App\Support\MealGridPrefs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMyGuestMealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // No date and no member_id on purpose: the member may only record a
        // guest meal for THEMSELVES and for TODAY — the controller pins both
        // server-side. Meal type is limited to what the admin shows on the
        // grid, so a hidden meal can't be charged through the back door.
        return [
            'guest_name' => ['required', 'string', 'max:100'],
            'meal_type' => ['required', Rule::in(MealGridPrefs::visibleMeals())],
            'quantity' => ['required', 'numeric', 'min:1', 'max:20'],
        ];
    }
}
