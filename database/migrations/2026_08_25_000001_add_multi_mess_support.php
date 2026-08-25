<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Multi-mess support: every user belongs to one mess (users.mess_id), and
 * every mess has a shareable join code. Backfills existing rows so a
 * single-mess installation keeps working exactly as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messes', function (Blueprint $table) {
            $table->string('join_code', 12)->nullable()->unique()->after('status');
            $table->foreignId('created_by')->nullable()->after('join_code')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('mess_id')->nullable()->after('id')
                ->constrained('messes')->nullOnDelete();
        });

        // Every existing mess gets a join code.
        foreach (DB::table('messes')->whereNull('join_code')->pluck('id') as $id) {
            DB::table('messes')->where('id', $id)->update([
                'join_code' => $this->uniqueCode(),
            ]);
        }

        // Members: the mess of their member record.
        $links = DB::table('members')
            ->whereNotNull('user_id')
            ->select('user_id', 'mess_id')
            ->get();
        foreach ($links as $link) {
            DB::table('users')->where('id', $link->user_id)->whereNull('mess_id')
                ->update(['mess_id' => $link->mess_id]);
        }

        // Everyone else (super-admin / managers of a single-mess install):
        // the first mess, which is exactly what Mess::activeId() resolved before.
        $firstMessId = DB::table('messes')->orderBy('id')->value('id');
        if ($firstMessId !== null) {
            DB::table('users')->whereNull('mess_id')->update(['mess_id' => $firstMessId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mess_id');
        });

        Schema::table('messes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropUnique(['join_code']);
            $table->dropColumn('join_code');
        });
    }

    private function uniqueCode(): string
    {
        do {
            // Unambiguous alphabet: no 0/O/1/I so codes survive being read aloud.
            $code = substr(str_replace(['0', 'O', '1', 'I', 'L'], '', strtoupper(Str::random(32))), 0, 8);
        } while (strlen($code) < 8 || DB::table('messes')->where('join_code', $code)->exists());

        return $code;
    }
};
