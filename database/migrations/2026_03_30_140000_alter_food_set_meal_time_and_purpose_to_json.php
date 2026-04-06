<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Widen columns to text so they can hold JSON
        DB::statement('ALTER TABLE "Food_Set" ALTER COLUMN "Food_Set_Meal_Time" TYPE text');
        DB::statement('ALTER TABLE "Food_Set" ALTER COLUMN "Food_Set_Purpose"   TYPE text');

        // Step 2: Convert any existing plain-string values to JSON arrays
        $sets = DB::table('Food_Set')->get();
        foreach ($sets as $set) {
            $updates = [];

            if (!empty($set->Food_Set_Meal_Time) && $set->Food_Set_Meal_Time[0] !== '[') {
                $updates['Food_Set_Meal_Time'] = json_encode([$set->Food_Set_Meal_Time]);
            }

            if (!empty($set->Food_Set_Purpose) && $set->Food_Set_Purpose[0] !== '[') {
                $updates['Food_Set_Purpose'] = json_encode([$set->Food_Set_Purpose]);
            }

            if (!empty($updates)) {
                DB::table('Food_Set')
                    ->where('Food_Set_ID', $set->Food_Set_ID)
                    ->update($updates);
            }
        }
    }

    public function down(): void
    {
        // Revert JSON arrays back to the first element as a plain string
        $sets = DB::table('Food_Set')->get();
        foreach ($sets as $set) {
            $updates = [];

            $mt = json_decode($set->Food_Set_Meal_Time, true);
            if (is_array($mt)) {
                $updates['Food_Set_Meal_Time'] = $mt[0] ?? '';
            }

            $pu = json_decode($set->Food_Set_Purpose, true);
            if (is_array($pu)) {
                $updates['Food_Set_Purpose'] = $pu[0] ?? '';
            }

            if (!empty($updates)) {
                DB::table('Food_Set')
                    ->where('Food_Set_ID', $set->Food_Set_ID)
                    ->update($updates);
            }
        }

        DB::statement('ALTER TABLE "Food_Set" ALTER COLUMN "Food_Set_Meal_Time" TYPE varchar(50)');
        DB::statement('ALTER TABLE "Food_Set" ALTER COLUMN "Food_Set_Purpose"   TYPE varchar(50)');
    }
};
