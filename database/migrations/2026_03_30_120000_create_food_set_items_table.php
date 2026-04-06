<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds Food_Set_Food_IDs (JSON text column) directly to Food_Set.
 * Stores an array of Food_ID integers — no pivot table needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Food_Set', function (Blueprint $table) {
            // Nullable — existing sets start with no foods
            $table->text('Food_Set_Food_IDs')->nullable()->after('Food_Set_Status');
        });
    }

    public function down(): void
    {
        Schema::table('Food_Set', function (Blueprint $table) {
            $table->dropColumn('Food_Set_Food_IDs');
        });
    }
};
