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
        // Pre-flight: detect rows that contain non-integer Food_Set_ID values
        // (e.g. "buffet:350" or JSON strings written after the up() migration).
        // Attempting to cast those to BIGINT would abort the entire migration,
        // leaving the database in a partially rolled-back state.
        $bad = DB::selectOne(
            'SELECT COUNT(*) AS cnt FROM "Food_Reservation" WHERE "Food_Set_ID" !~ \'^[0-9]+$\''
        );

        if ($bad && (int) $bad->cnt > 0) {
            throw new \RuntimeException(
                "Cannot roll back migration: Food_Reservation contains {$bad->cnt} row(s) with " .
                "non-integer Food_Set_ID values (e.g. buffet strings or JSON). " .
                "Remove or reassign those rows manually before rolling back."
            );
        }

        // Safe to cast back – all remaining values are plain integers.
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
