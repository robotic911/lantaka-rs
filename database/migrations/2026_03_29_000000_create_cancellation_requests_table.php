<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancellation_requests', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Which reservation this request belongs to
            $table->unsignedBigInteger('reservation_id');
            $table->string('reservation_type'); // 'room' | 'venue'

            // The client who submitted the request
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('Account_ID')->on('Account')->onDelete('cascade');

            // Why the client wants to cancel
            $table->text('reason');

            // Admin decision
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->text('admin_note')->nullable();

            // Which admin processed the request
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->foreign('processed_by')->references('Account_ID')->on('Account')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_requests');
    }
};
