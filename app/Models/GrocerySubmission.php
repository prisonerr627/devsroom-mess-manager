<?php

namespace App\Models;

use App\Models\Concerns\BelongsToActiveMess;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * A member's grocery (bazar) purchase claim. Pending until a manager
 * approves it into a real Expense (expense_id) or rejects it.
 */
#[Fillable([
    'mess_id', 'member_id', 'submitted_by', 'date', 'amount', 'vendor',
    'description', 'receipt_path', 'status', 'reviewed_by', 'reviewed_at',
    'rejection_reason', 'expense_id',
])]
class GrocerySubmission extends Model implements AuditableContract
{
    use Auditable, BelongsToActiveMess;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
