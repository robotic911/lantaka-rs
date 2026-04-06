<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Legacy generic reservations table (not actively used by the app).
    // Kept for DB parity.
    public function up(): void
    {
        if (Schema::hasTable('reservations')) {
            return;
        }

        Schema::create('reservations', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('Account_ID')->on('Account')->onDelete('cascade');

            $table->unsignedBigInteger('accommodation_id');
            $table->string('type');
            $table->decimal('additional_fees', 10, 2)->default(0);
            $table->string('additional_fees_desc')->nullable();
            $table->date('check_in');
            $table->date('check_out');
            $table->integer('pax');
            $table->decimal('total_amount', 10, 2);
            $table->string('status')->default('Pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
