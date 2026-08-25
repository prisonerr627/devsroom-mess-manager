<?php

namespace App\Http\Requests\Join;

use App\Models\Mess;
use Illuminate\Foundation\Http\FormRequest;

class JoinMessRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only a logged-in user who is not yet attached to any mess.
        return $this->user() !== null && Mess::activeId() === null;
    }

    public function rules(): array
    {
        return [
            'join_code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            // Same constraint the manager's member form enforces.
            'mobile' => ['required', 'string', 'max:30', 'regex:/^(01)[3-9]\d{8}$/'],
            'room_or_seat' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => __('Mobile must be a valid BD number (e.g. 01700000000).'),
        ];
    }
}
