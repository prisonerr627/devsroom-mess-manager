<?php

namespace App\Http\Requests\My;

use Illuminate\Foundation\Http\FormRequest;

class SaveMyTodayMealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // No date field on purpose: the member may only ever write TODAY's
        // entry — the controller pins the date server-side.
        return [
            'breakfast' => ['nullable', 'boolean'],
            'lunch' => ['nullable', 'boolean'],
            'dinner' => ['nullable', 'boolean'],
        ];
    }
}
