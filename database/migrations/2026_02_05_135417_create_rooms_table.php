<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('Room')) {
            return;
        }

        Schema::create('Room', function (Blueprint $table) {
            $table->bigIncrements('Room_ID');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('Account_ID')->on('Account')->onDelete('cascade');

            $table->string('Room_Number');
            $table->string('Room_Type');
            $table->string('Room_Image')->nullable();
            $table->integer('Room_Capacity');
            $table->string('Room_Status')->default('Available');
            $table->decimal('Room_Internal_Price', 10, 2);
            $table->decimal('Room_External_Price', 10, 2);
            $table->text('Room_Description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Room');
    }
};
