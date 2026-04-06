<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Food_Set', function (Blueprint $table) {
            if (!Schema::hasColumn('Food_Set', 'Food_Set_Food_IDs')) {
                $table->text('Food_Set_Food_IDs')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('Food_Set', function (Blueprint $table) {
            $table->dropColumn('Food_Set_Food_IDs');
        });
    }
};
