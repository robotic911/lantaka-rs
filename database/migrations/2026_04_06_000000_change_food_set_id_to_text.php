<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Change Food_Set_ID in Food_Reservation from a foreign-key bigInteger
 * to a TEXT column that stores the set ID AND all user-chosen customizations
 * (rice, drinks, dessert, fruit) in one compact string:
 *
 *   "setId",["riceId","drinksId","dessertId","fruitId"]
 *
 * This eliminates the need for separate FoodReservation rows per-customization
 * and fixes the spiritual-event bug where duplicate <select> names caused PHP
 * to overwrite the selected card's rice/drink values with empty strings.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop the FK constraint so we can change the column type
        Schema::table('Food_Reservation', function (Blueprint $table) {
            $table->dropForeign(['Food_Set_ID']);
        });

        // 2. Change the column type from unsignedBigInteger → TEXT
        //    USING clause safely coerces existing integer values to their string form.
        DB::statement(
            'ALTER TABLE "Food_Reservation" ALTER COLUMN "Food_Set_ID" TYPE TEXT '
            . 'USING "Food_Set_ID"::TEXT'
        );
    }

    public function down(): void
    {
        // Revert TEXT back to BIGINT (only works if all values are plain integers)
        DB::statement(
            'ALTER TABLE "Food_Reservation" ALTER COLUMN "Food_Set_ID" TYPE BIGINT '
            . 'USING ("Food_Set_ID"::TEXT)::BIGINT'
        );

        // Restore the FK constraint
        Schema::table('Food_Reservation', function (Blueprint $table) {
            $table->foreign('Food_Set_ID')
                  ->references('Food_Set_ID')
                  ->on('Food_Set')
                  ->onDelete('set null');
        });
    }
};
