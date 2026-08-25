<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Signup now collects a username and phone number so a manager can add a
 * signed-up person to their mess by either, without a join code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->unique()->after('name');
            $table->string('mobile', 30)->nullable()->unique()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropUnique(['mobile']);
            $table->dropColumn(['username', 'mobile']);
        });
    }
};
