<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Venue_Reservation', function (Blueprint $table) {
            $table->bigIncrements('Venue_Reservation_ID');

            $table->unsignedBigInteger('Venue_ID');
            $table->foreign('Venue_ID')->references('Venue_ID')->on('Venue')->onDelete('cascade');

            $table->unsignedBigInteger('Admin_ID')->nullable();
            $table->foreign('Admin_ID')->references('Account_ID')->on('Account');

            $table->unsignedBigInteger('Client_ID');
            $table->foreign('Client_ID')->references('Account_ID')->on('Account')->onDelete('cascade');

            $table->unsignedBigInteger('Staff_ID')->nullable();
            $table->foreign('Staff_ID')->references('Account_ID')->on('Account');

            $table->timestamp('Venue_Reservation_Date')->useCurrent();
            $table->dateTime('Venue_Reservation_Check_In_Time');
            $table->dateTime('Venue_Reservation_Check_Out_Time');
            $table->dateTime('Venue_Reservation_Actual_Check_Out')->nullable();

            $table->decimal('Venue_Reservation_Total_Price', 10, 2);
            $table->decimal('Venue_Reservation_Additional_Fees', 10, 2)->nullable();
            $table->string('Venue_Reservation_Additional_Fees_Desc', 255)->nullable();

            $table->integer('Venue_Reservation_Pax');
            $table->string('Venue_Reservation_Status')->default('Pending');
            $table->timestamps();

            $table->decimal('Venue_Reservation_Discount', 10, 2)->default(0.00);

            // NOTE: The local DB has a TYPO here ('Purppse' — double p).
            // This migration uses the CORRECT spelling to match the PHP code.
            // You must also fix the column name in your local DB:
            //   ALTER TABLE "Venue_Reservation" RENAME COLUMN "Venue_Reservation_Purppse" TO "Venue_Reservation_Purpose";
            $table->text('Venue_Reservation_Purpose')->nullable();

            $table->string('Venue_Reservation_Payment_Status')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Venue_Reservation');
    }
};
