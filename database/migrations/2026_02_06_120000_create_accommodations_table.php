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
        if (Schema::hasTable('accommodations')) {
            return;
        }

       Schema::create('accommodations', function (Blueprint $table) {
            $table->id(); // This creates the 'id' column
            $table->string('display_name'); // e.g., "Hall A" or "Single Room"
            $table->string('category'); // e.g., "Venue" or "Room"
            $table->string('image')->nullable();
            $table->integer('capacity');
            $table->string('status')->default('Available');
            $table->decimal('external_price', 10, 2);
            $table->text('description')->nullable();
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
        Schema::dropIfExists('accommodations');
    }
};
