<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add cancellation-request columns to Room_Reservation
        Schema::table('Room_Reservation', function (Blueprint $table) {
            $table->string('cancellation_status')->nullable()->default(null)
                  ->comment('null=no request | pending | approved | rejected');
            $table->text('cancellation_reason')->nullable();
            $table->text('cancellation_admin_note')->nullable();
            $table->unsignedBigInteger('cancellation_processed_by')->nullable();
            $table->timestamp('cancellation_requested_at')->nullable();
            $table->timestamp('cancellation_processed_at')->nullable();
        });

        // Add cancellation-request columns to Venue_Reservation
        Schema::table('Venue_Reservation', function (Blueprint $table) {
            $table->string('cancellation_status')->nullable()->default(null)
                  ->comment('null=no request | pending | approved | rejected');
            $table->text('cancellation_reason')->nullable();
            $table->text('cancellation_admin_note')->nullable();
            $table->unsignedBigInteger('cancellation_processed_by')->nullable();
            $table->timestamp('cancellation_requested_at')->nullable();
            $table->timestamp('cancellation_processed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('Room_Reservation', function (Blueprint $table) {
            $table->dropColumn([
                'cancellation_status',
                'cancellation_reason',
                'cancellation_admin_note',
                'cancellation_processed_by',
                'cancellation_requested_at',
                'cancellation_processed_at',
            ]);
        });

        Schema::table('Venue_Reservation', function (Blueprint $table) {
            $table->dropColumn([
                'cancellation_status',
                'cancellation_reason',
                'cancellation_admin_note',
                'cancellation_processed_by',
                'cancellation_requested_at',
                'cancellation_processed_at',
            ]);
        });
    }
};
