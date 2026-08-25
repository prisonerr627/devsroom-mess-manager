<?php

namespace App\Http\Requests\Mess;

use Illuminate\Foundation\Http\FormRequest;

class RejectGrocerySubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->canManageMess();
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }
}
