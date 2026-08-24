<?php

namespace App\Http\Requests\Mess;

use App\Models\Mess;
use App\Support\MemberStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->canManageMess();
    }

    public function rules(): array
    {
        $memberId = $this->route('member')?->id;

        // Duplicate prevention: unique per mess, ignoring the member being edited,
        // and excluding null rows so optional contacts don't collide.
        $perMessUnique = function (string $column) use ($memberId) {
            return Rule::unique('members', $column)
                ->ignore($memberId)
                ->where(fn ($q) => $q->where('mess_id', Mess::activeId())->whereNotNull($column));
        };

        return [
            'name' => ['required', 'string', 'max:255'],
            // Phone is the member's unique identifier (email is optional).
            // Required + unique within the active mess, ignoring self on edit.
            'mobile' => ['required', 'string', 'max:30', 'regex:/^(01)[3-9]\d{8}$/', $perMessUnique('mobile')],
            // Email stays optional for a plain member, BUT is required when
            // creating a login account on edit — same constraint as on create:
            // users.email is NOT NULL and is the login identifier.
            'email' => ['nullable', 'email', 'max:255', $perMessUnique('email'), Rule::requiredIf(fn () => $this->boolean('create_account'))],
            'nid' => ['nullable', 'string', 'max:50'],
            'profession' => ['nullable', 'string', 'max:100'],
            'room_or_seat' => ['nullable', 'string', 'max:50'],
            'joining_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(MemberStatus::ALL)],
            'leaving_date' => ['nullable', 'date', 'required_if:status,former'],
            'emergency_contact' => ['nullable', 'string', 'max:100'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            // Login account: create one for a member without a login, or reset
            // the password of an existing login (blank = keep current password).
            'create_account' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'send_credentials' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => __('Mobile must be a valid BD number (e.g. 01700000000).'),
            'mobile.required' => __('Mobile number is required.'),
            'mobile.unique' => __('A member with this mobile number already exists in this mess.'),
            'email.unique' => __('A member with this email already exists in this mess.'),
            'email.required' => __('An email is required to create a login account.'),
            'leaving_date.required_if' => __('Leaving date is required when status is former.'),
            'photo.max' => __('Photo must be 2 MB or smaller.'),
        ];
    }
}
