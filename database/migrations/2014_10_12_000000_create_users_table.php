<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->string('username')->unique(); // From Migration 1
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone');              // Required by Controller
            $table->string('affiliation');        // Required by Controller
            $table->string('usertype')->nullable(); // From Migration 2
            $table->string('valid_id_path');      // Required by Controller
            $table->string('role')->default('client'); // From Migration 1
            $table->string('status')->default('pending');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
