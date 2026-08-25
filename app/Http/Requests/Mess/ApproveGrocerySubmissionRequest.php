<?php

namespace App\Http\Requests\Mess;

use App\Models\Mess;
use App\Support\ExpenseKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveGrocerySubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->canManageMess();
    }

    public function rules(): array
    {
        return [
            // Must be a bazar-kind category of the active mess.
            'expense_category_id' => [
                'required',
                'integer',
                Rule::exists('expense_categories', 'id')->where(
                    fn ($q) => $q->where('mess_id', Mess::activeId())->where('kind', ExpenseKind::BAZAR)
                ),
            ],
            // Optional correction of the claimed amount.
            'amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }
}
