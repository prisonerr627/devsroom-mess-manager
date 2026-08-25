<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Member-submitted grocery (bazar) purchases. A submission is a CLAIM: it
 * only affects the meal rate once a manager approves it, at which point a
 * real Expense row is created and linked (expense_id). Keeping claims in
 * their own table means every existing expense sum stays untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grocery_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained('messes')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date');
            $table->decimal('amount', 10, 2);
            $table->string('vendor', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->timestamps();

            $table->index(['mess_id', 'status']);
            $table->index(['mess_id', 'date']);
            $table->index(['member_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grocery_submissions');
    }
};
