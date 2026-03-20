<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('Food_Reservation', function (Blueprint $table) {
            // This is the Primary Key your FoodReservation model expects
            $table->id('Food_Reservation_ID');

            // This links to the reservations table
            $table->foreignId('Venue_Reservation_ID')
            ->constrained('Venue_Reservation', 'Venue_Reservation_ID')
            ->onDelete('cascade');

            // This links to the food table
            $table->foreignId('Food_ID')->constrained('Food', 'Food_ID')->onDelete('cascade');

            // Adding the extra pivot columns your model uses
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->string('status')->default('pending');
            $table->string('serving_time')->nullable();
            $table->decimal('total_price', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('Food_Reservation');
    }
};
