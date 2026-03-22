<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Room_Reservation', function (Blueprint $table) {
            $table->bigIncrements('Room_Reservation_ID');

            $table->unsignedBigInteger('Room_ID');
            $table->foreign('Room_ID')->references('Room_ID')->on('Room')->onDelete('cascade');

            $table->unsignedBigInteger('Admin_ID')->nullable();
            $table->foreign('Admin_ID')->references('Account_ID')->on('Account');

            $table->unsignedBigInteger('Client_ID');
            $table->foreign('Client_ID')->references('Account_ID')->on('Account')->onDelete('cascade');

            $table->unsignedBigInteger('Staff_ID')->nullable();
            $table->foreign('Staff_ID')->references('Account_ID')->on('Account');

            $table->timestamp('Room_Reservation_Date')->useCurrent();
            $table->dateTime('Room_Reservation_Check_In_Time');
            $table->dateTime('Room_Reservation_Check_Out_Time');
            $table->dateTime('Room_Reservation_Actual_Check_Out')->nullable();

            $table->decimal('Room_Reservation_Discount', 5, 2)->default(0.00);
            $table->integer('Room_Reservation_Quantity')->default(1);
            $table->integer('Room_Reservation_Pax');
            $table->decimal('Room_Reservation_Total_Price', 10, 2);

            $table->decimal('Room_Reservation_Additional_Fees', 10, 2)->nullable();
            $table->string('Room_Reservation_Additional_Fees_Desc')->nullable();

            $table->string('Room_Reservation_Status')->default('pending');
            $table->timestamps();

            $table->text('Room_Reservation_Purpose')->nullable();
            $table->string('Room_Reservation_Payment_Status')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Room_Reservation');
    }
};
