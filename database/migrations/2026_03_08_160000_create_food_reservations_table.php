<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('food_reservations', function (Blueprint $table) {
            // This is the Primary Key your FoodReservation model expects
            $table->id('food_reservation_id'); 
            
            // This links to the reservations table
            $table->foreignId('venue_reservation_id')
            ->constrained('venue_reservations', 'Venue_Reservation_ID') 
            ->onDelete('cascade');
            
            // This links to the food table
            $table->foreignId('food_id')->constrained('food', 'food_id')->onDelete('cascade');
            
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
        Schema::dropIfExists('food_reservations');
    }
};