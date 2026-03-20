<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Venue', function (Blueprint $table) {
            $table->bigIncrements('Venue_ID');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('Account_ID')->on('Account')->onDelete('cascade');

            $table->string('Venue_Name');
            $table->string('Venue_Image')->nullable();
            $table->integer('Venue_Capacity');
            $table->string('Venue_Status')->default('Available');
            $table->decimal('Venue_Internal_Price', 10, 2);
            $table->decimal('Venue_External_Price', 10, 2);
            $table->text('Venue_Description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Venue');
    }
};
