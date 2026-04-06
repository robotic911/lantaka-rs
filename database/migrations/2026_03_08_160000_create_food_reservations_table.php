<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('Food_Reservation')) {
            return;
        }

        Schema::create('Food_Reservation', function (Blueprint $table) {
            $table->bigIncrements('Food_Reservation_ID');

            $table->unsignedBigInteger('Venue_Reservation_ID');
            $table->foreign('Venue_Reservation_ID')
                ->references('Venue_Reservation_ID')->on('Venue_Reservation')
                ->onDelete('cascade');

            $table->unsignedBigInteger('Food_ID');
            $table->foreign('Food_ID')->references('Food_ID')->on('Food')->onDelete('cascade');

            $table->unsignedBigInteger('Client_ID')->nullable();
            $table->unsignedBigInteger('Staff_ID')->nullable();

            $table->string('Food_Reservation_Status')->default('pending');
            $table->string('Food_Reservation_Serving_Date')->nullable();
            $table->decimal('Food_Reservation_Total_Price', 10, 2)->default(0);
            $table->timestamps();
            $table->text('Food_Reservation_Meal_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Food_Reservation');
    }
};
