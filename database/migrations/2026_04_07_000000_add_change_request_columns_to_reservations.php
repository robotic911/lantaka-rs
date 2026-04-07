<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds "Request for Changes" columns to both reservation tables.
 *
 * A single change request may contain:
 *   - a reschedule (new check-in / check-out dates), and/or
 *   - food reservation modifications
 *
 * The payload is stored as JSON in change_request_details so we avoid adding
 * individual date/food columns while keeping the schema flexible.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add change-request columns to Room_Reservation
        Schema::table('Room_Reservation', function (Blueprint $table) {
            $table->string('change_request_status')->nullable()->default(null)
                  ->comment('null=no request | pending | approved | rejected');
            $table->string('change_request_type')->nullable()->default(null)
                  ->comment('reschedule | food_modification | reschedule_and_food');
            $table->text('change_request_reason')->nullable()
                  ->comment('Optional explanation from the client');
            $table->json('change_request_details')->nullable()
                  ->comment('JSON payload: { check_in, check_out, foods[] }');
            $table->text('change_request_admin_note')->nullable();
            $table->unsignedBigInteger('change_request_processed_by')->nullable();
            $table->timestamp('change_request_requested_at')->nullable();
            $table->timestamp('change_request_processed_at')->nullable();
        });

        // Add change-request columns to Venue_Reservation
        Schema::table('Venue_Reservation', function (Blueprint $table) {
            $table->string('change_request_status')->nullable()->default(null)
                  ->comment('null=no request | pending | approved | rejected');
            $table->string('change_request_type')->nullable()->default(null)
                  ->comment('reschedule | food_modification | reschedule_and_food');
            $table->text('change_request_reason')->nullable()
                  ->comment('Optional explanation from the client');
            $table->json('change_request_details')->nullable()
                  ->comment('JSON payload: { check_in, check_out, foods[] }');
            $table->text('change_request_admin_note')->nullable();
            $table->unsignedBigInteger('change_request_processed_by')->nullable();
            $table->timestamp('change_request_requested_at')->nullable();
            $table->timestamp('change_request_processed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('Room_Reservation', function (Blueprint $table) {
            $table->dropColumn([
                'change_request_status',
                'change_request_type',
                'change_request_reason',
                'change_request_details',
                'change_request_admin_note',
                'change_request_processed_by',
                'change_request_requested_at',
                'change_request_processed_at',
            ]);
        });

        Schema::table('Venue_Reservation', function (Blueprint $table) {
            $table->dropColumn([
                'change_request_status',
                'change_request_type',
                'change_request_reason',
                'change_request_details',
                'change_request_admin_note',
                'change_request_processed_by',
                'change_request_requested_at',
                'change_request_processed_at',
            ]);
        });
    }
};
