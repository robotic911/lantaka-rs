<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add notes column to Room_Reservation table
        if (Schema::hasTable('Room_Reservation') && !Schema::hasColumn('Room_Reservation', 'Room_Reservation_Notes')) {
            Schema::table('Room_Reservation', function (Blueprint $table) {
                $table->text('Room_Reservation_Notes')->nullable()->after('Room_Reservation_Purpose');
            });
        }

        // Add notes column to Venue_Reservation table
        if (Schema::hasTable('Venue_Reservation') && !Schema::hasColumn('Venue_Reservation', 'Venue_Reservation_Notes')) {
            Schema::table('Venue_Reservation', function (Blueprint $table) {
                $table->text('Venue_Reservation_Notes')->nullable()->after('Venue_Reservation_Purpose');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('Room_Reservation') && Schema::hasColumn('Room_Reservation', 'Room_Reservation_Notes')) {
            Schema::table('Room_Reservation', function (Blueprint $table) {
                $table->dropColumn('Room_Reservation_Notes');
            });
        }

        if (Schema::hasTable('Venue_Reservation') && Schema::hasColumn('Venue_Reservation', 'Venue_Reservation_Notes')) {
            Schema::table('Venue_Reservation', function (Blueprint $table) {
                $table->dropColumn('Venue_Reservation_Notes');
            });
        }
    }
};
