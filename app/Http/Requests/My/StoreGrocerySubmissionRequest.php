<?php

namespace App\Http\Requests\My;

use Illuminate\Foundation\Http\FormRequest;

class StoreGrocerySubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // Not in the future, and not older than 60 days (closed months are
            // additionally rejected by the service).
            'date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.now()->subDays(60)->toDateString()],
            'amount' => ['required', 'numeric', 'min:1', 'max:99999999.99'],
            'vendor' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:500'],
            'receipt' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.before_or_equal' => __('The purchase date cannot be in the future.'),
            'date.after_or_equal' => __('Purchases older than 60 days must be entered by the manager.'),
            'description.required' => __('List what you bought.'),
        ];
    }
}
