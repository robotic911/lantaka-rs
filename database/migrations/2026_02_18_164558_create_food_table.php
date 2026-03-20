<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Food', function (Blueprint $table) {
            $table->bigIncrements('Food_ID');
            $table->unsignedBigInteger('admin_id')->nullable();
            // Note: no FK constraint on admin_id (matches DB)

            $table->string('Food_Name', 50);
            $table->string('Food_Category', 50);
            $table->decimal('Food_Price', 10, 2);
            $table->string('Food_Status', 20)->default('available');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Food');
    }
};
