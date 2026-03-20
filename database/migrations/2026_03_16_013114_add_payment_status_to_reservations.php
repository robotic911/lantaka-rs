<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_reservations', function (Blueprint $table) {
            // null = not yet checked out; 'paid' | 'unpaid' set on checkout
            $table->string('payment_status')->nullable()->default(null)->after('status');
        });

        Schema::table('Venue_Reservation', function (Blueprint $table) {
            $table->string('payment_status')->nullable()->default(null)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('room_reservations', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });

        Schema::table('Venue_Reservation', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
    }
};
