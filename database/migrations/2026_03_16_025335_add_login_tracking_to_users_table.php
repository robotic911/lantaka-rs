<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Set when admin approves and generates the user's first password
            $table->timestamp('password_set_at')->nullable()->after('password');
            // Set on the user's very first successful login
            $table->timestamp('last_login_at')->nullable()->after('password_set_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password_set_at', 'last_login_at']);
        });
    }
};
