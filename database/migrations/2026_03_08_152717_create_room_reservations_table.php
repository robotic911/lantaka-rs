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
        Schema::create('room_reservations', function (Blueprint $table) {
            // Match ERD PK name
            $table->id('Room_Reservation_ID'); 

            // Foreign Keys exactly as per ERD
            $table->foreignId('room_id')->constrained('rooms', 'id')->onDelete('cascade');
            $table->foreignId('Admin_ID')->nullable()->constrained('users');
            $table->foreignId('Client_ID')->constrained('users')->onDelete('cascade');
            $table->foreignId('Staff_ID')->nullable()->constrained('users');

            // Reservation Details (Using ERD PascalCase names)
            $table->timestamp('Room_Reservation_Date')->useCurrent();
            $table->dateTime('Room_Reservation_Check_In_Time');
            $table->dateTime('Room_Reservation_Check_Out_Time');
            $table->dateTime('Room_Reservation_Actual_Check_Out')->nullable();
            
            // Financials and Logic
            $table->decimal('Room_Discount', 5, 2)->default(0.00);
            $table->integer('Room_Reservation_Quantity')->default(1);
            $table->integer('pax'); // Note: 'pax' is in your code but not explicitly in this ERD snippet
            $table->decimal('Room_Reservation_Total_Price', 10, 2);
            
            // Fees Section
            $table->decimal('Room_Reservation_Additional_Fees', 10, 2)->nullable();
            $table->string('Room_Reservation_Additional_Fees_Desc', 255)->nullable();
            
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
        Schema::dropIfExists('room_reservations');
    }
};
