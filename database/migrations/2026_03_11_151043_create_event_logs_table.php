<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The event_logs table serves TWO purposes:
     *   1. Audit trail  — every system action is recorded (user_id = actor)
     *   2. Notifications — entries with notifiable_user_id set surface in the
     *                      client notification bell and employee badge.
     */
    public function up()
    {
        Schema::create('event_logs', function (Blueprint $table) {
            $table->id();

            // Who performed the action (admin / staff / client)
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Who should see this as a notification (nullable — null = audit-only)
            $table->unsignedBigInteger('notifiable_user_id')->nullable();
            $table->foreign('notifiable_user_id')->references('id')->on('users')->onDelete('cascade');

            // Audit / notification content
            $table->string('action');                   // e.g. 'reservation_confirmed', 'account_approved'
            $table->string('title')->nullable();        // Short heading shown in notification bell
            $table->text('message');                    // Human-readable detail
            $table->string('type')->nullable();         // Dot colour key: confirmed, cancelled, rejected …
            $table->string('link')->nullable();         // URL to navigate to on click

            // Notification read state (only meaningful when notifiable_user_id is set)
            $table->boolean('is_read')->default(false);

            $table->timestamps();                       // created_at doubles as Event_Logs_DateTime
        });
    }

    public function down()
    {
        Schema::dropIfExists('event_logs');
    }
};
