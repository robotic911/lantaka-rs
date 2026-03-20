<?php

// Intentionally empty — password_set_at and last_login_at are now defined in
// 2014_10_12_000000_create_users_table.php (Account table)

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void {}
    public function down(): void {}
};
