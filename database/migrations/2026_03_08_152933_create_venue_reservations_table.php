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
        Schema::create('venue_reservations', function (Blueprint $table) {
            $table->id('Venue_Reservation_ID'); // Matches ERD PK

            // Foreign Keys
            $table->foreignId('venue_id')->constrained('venues', 'id')->onDelete('cascade');
            $table->foreignId('Admin_ID')->nullable()->constrained('users');
            $table->foreignId('Client_ID')->constrained('users')->onDelete('cascade');
            $table->foreignId('Staff_ID')->nullable()->constrained('users');

            // The Missing Date and Time Details
            $table->timestamp('Venue_Reservation_Date')->useCurrent(); 
            $table->dateTime('Venue_Reservation_Check_In_Time');
            $table->dateTime('Venue_Reservation_Check_Out_Time');
            $table->dateTime('Venue_Reservation_Actual_Check_Out')->nullable();

            // Financials
            $table->decimal('Venue_Reservation_Total_Price', 10, 2);
            $table->decimal('Venue_Reservation_Additional_Fees', 10, 2)->nullable();
            $table->string('Venue_Reservation_Additional_Fees_Desc', 255)->nullable();
            
            $table->integer('pax'); // Included for your controller logic
            $table->string('status')->default('Pending');
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
        Schema::dropIfExists('venue_reservations');
    }
};
