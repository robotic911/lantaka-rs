<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('Account')) {
            return;
        }

        Schema::create('Account', function (Blueprint $table) {
            $table->bigIncrements('Account_ID');
            $table->string('Account_Name');
            $table->string('Account_Username')->unique();
            $table->string('Account_Email')->unique();
            $table->string('Account_Password')->nullable();
            $table->string('Account_Phone');
            $table->string('Account_Affiliation');
            $table->string('Account_Type')->nullable();
            $table->string('valid_id_path');
            $table->string('Account_Role')->default('client');
            $table->string('Account_Status')->default('pending');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->timestamp('password_set_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Account');
    }
};
