<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('Food_Set')) {
            return;
        }

        Schema::create('Food_Set', function (Blueprint $table) {
            $table->bigIncrements('Food_Set_ID');
            $table->string('Food_Set_Name', 255);
            $table->decimal('Food_Set_Price', 10, 2)->default(0);
            $table->string('Food_Set_Purpose', 50);  // e.g. breakfast, lunch, dinner
            $table->string('Food_Set_Meal_Time', 50); // breakfast | am_snack | lunch | pm_snack | dinner
            $table->string('Food_Set_Status', 50)->default('available'); // available | unavailable
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Food_Set');
    }
};
