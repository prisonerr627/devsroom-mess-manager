<?php

namespace App\Services;

use App\Models\GrocerySubmission;
use App\Models\Member;
use App\Models\Mess;
use App\Models\MonthlyClosing;
use App\Support\GroceryStatus;
use App\Support\NotificationType;
use App\Support\StorageProvider;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GrocerySubmissionService
{
    public function __construct(
        private readonly ExpenseService $expenses,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Member files a grocery purchase claim. Managers are notified; nothing
     * touches the meal rate until approve().
     *
     * @param  array{date:string, amount:numeric, vendor?:?string, description?:?string}  $data
     */
    public function submit(Member $member, array $data, ?UploadedFile $receipt = null): GrocerySubmission
    {
        $this->assertMonthOpen(Carbon::parse($data['date']), 'date');

        $submission = GrocerySubmission::create([
            'mess_id' => Mess::activeId(),
            'member_id' => $member->id,
            'submitted_by' => auth()->id(),
            'date' => $data['date'],
            'amount' => $data['amount'],
            'vendor' => $data['vendor'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => GroceryStatus::PENDING,
        ]);

        if ($receipt) {
            $ext = $receipt->getClientOriginalExtension();
            $path = "grocery-receipts/{$submission->id}.{$ext}";
            StorageProvider::store($path, $receipt);
            $submission->update(['receipt_path' => $path]);
        }

        $this->notifications->broadcastToManagers(NotificationType::GROCERY_SUBMITTED, [
            'member' => $member->name,
            'amount' => (float) $submission->amount,
            'date' => $submission->date->toDateString(),
            'submission_id' => $submission->id,
        ]);

        return $submission;
    }

    /**
     * Manager approves: creates the real bazar Expense (purchased_by = the
     * member, receipt carried over) and links it. The amount may be corrected
     * at approval time; the claim keeps the member's original figure.
     */
    public function approve(GrocerySubmission $submission, int $reviewerId, int $categoryId, ?float $amount = null): GrocerySubmission
    {
        $this->assertPending($submission);
        $this->assertMonthOpen($submission->date, 'submission');

        DB::transaction(function () use ($submission, $reviewerId, $categoryId, $amount): void {
            $expense = $this->expenses->create([
                'expense_category_id' => $categoryId,
                'date' => $submission->date->toDateString(),
                'purchased_by' => $submission->member_id,
                'vendor' => $submission->vendor,
                'description' => $submission->description,
                'amount' => $amount ?? (float) $submission->amount,
            ]);

            if ($submission->receipt_path) {
                $expense->update(['receipt_path' => $submission->receipt_path]);
            }

            $submission->update([
                'status' => GroceryStatus::APPROVED,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'expense_id' => $expense->id,
            ]);
        });

        $this->notifyMember($submission, $amount ?? (float) $submission->amount);

        return $submission;
    }

    public function reject(GrocerySubmission $submission, int $reviewerId, string $reason): GrocerySubmission
    {
        $this->assertPending($submission);

        $submission->update([
            'status' => GroceryStatus::REJECTED,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->notifyMember($submission, (float) $submission->amount);

        return $submission;
    }

    private function notifyMember(GrocerySubmission $submission, float $amount): void
    {
        $user = $submission->member?->user;
        if (! $user) {
            return;
        }

        $this->notifications->send($user, NotificationType::GROCERY_DECISION, [
            'status' => $submission->status,
            'amount' => $amount,
            'date' => $submission->date->toDateString(),
            'reason' => $submission->rejection_reason,
        ]);
    }

    private function assertPending(GrocerySubmission $submission): void
    {
        if ($submission->status !== GroceryStatus::PENDING) {
            throw ValidationException::withMessages([
                'status' => __('This submission has already been reviewed.'),
            ]);
        }
    }

    /**
     * D-19: a closed month's ledger is frozen — no new expense may land in it.
     */
    private function assertMonthOpen(Carbon $date, string $field): void
    {
        $closed = MonthlyClosing::query()
            ->where('year', $date->year)
            ->where('month', $date->month)
            ->exists();

        if ($closed) {
            throw ValidationException::withMessages([
                $field => __('The month :month is already closed, so this purchase cannot be added to it.', ['month' => $date->format('F Y')]),
            ]);
        }
    }
}
