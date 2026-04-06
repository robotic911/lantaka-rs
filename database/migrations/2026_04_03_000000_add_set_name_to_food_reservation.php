<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Replace the string-based Food_Reservation_Set_Name approach with a proper
     * Food_Set_ID foreign key. This lets us:
     *   - Store ONE Food_Reservation row per set selection (not one per food item)
     *   - Look up full set info (name, foods, price) via the FK at any time
     *
     * Food_ID is made nullable so set-reservation rows don't need an individual food.
     */
    public function up(): void
    {
        // 1. Make Food_ID nullable (set-reservation rows have no individual Food_ID)
        DB::statement('ALTER TABLE "Food_Reservation" ALTER COLUMN "Food_ID" DROP NOT NULL');

        // 2. Add Food_Set_ID FK column
        Schema::table('Food_Reservation', function (Blueprint $table) {
            $table->unsignedBigInteger('Food_Set_ID')
                  ->nullable()
                  ->after('Food_ID');

            $table->foreign('Food_Set_ID')
                  ->references('Food_Set_ID')
                  ->on('Food_Set')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('Food_Reservation', function (Blueprint $table) {
            $table->dropForeign(['Food_Set_ID']);
            $table->dropColumn('Food_Set_ID');
        });

        // Restore NOT NULL constraint on Food_ID
        DB::statement('ALTER TABLE "Food_Reservation" ALTER COLUMN "Food_ID" SET NOT NULL');
    }
};
