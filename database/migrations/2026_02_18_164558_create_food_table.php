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
        Schema::create('food', function (Blueprint $table) {
            $table->id('Food_ID');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('Food_Name', 50);
            $table->string('Food_Category', 50); // e.g., breakfast, lunch, snack
            $table->decimal('Food_Price', 10, 2);
            $table->string('Food_Availability', 20)->default('available'); // Add this line!
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
        Schema::dropIfExists('food');
    }
};
