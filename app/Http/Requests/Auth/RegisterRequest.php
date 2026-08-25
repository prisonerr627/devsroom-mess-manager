<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) config('tyro-login.registration.enabled', false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => strtolower(trim((string) $this->input('username'))),
            'email' => strtolower(trim((string) $this->input('email'))),
            'mobile' => preg_replace('/[\s\-]/', '', (string) $this->input('mobile')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9_.]+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            // Same BD-number rule the member forms use; unique so a manager can
            // add a person by phone unambiguously.
            'mobile' => ['required', 'string', 'max:30', 'regex:/^(01)[3-9]\d{8}$/', 'unique:users,mobile'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => __('Username may only contain lowercase letters, numbers, dots and underscores.'),
            'username.unique' => __('That username is already taken.'),
            'mobile.regex' => __('Mobile must be a valid BD number (e.g. 01700000000).'),
            'mobile.unique' => __('An account with this mobile number already exists.'),
        ];
    }
}
