<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Event_Logs', function (Blueprint $table) {
            $table->bigIncrements('Event_Logs_ID');

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('Account_ID')->on('Account')->onDelete('set null');

            $table->unsignedBigInteger('Event_Logs_Notifiable_User_ID')->nullable();
            $table->foreign('Event_Logs_Notifiable_User_ID')->references('Account_ID')->on('Account')->onDelete('cascade');

            $table->string('Event_Logs_Action');
            $table->string('Event_Logs_Title')->nullable();
            $table->text('Event_Logs_Message');
            $table->string('Event_Logs_Type')->nullable();
            $table->string('Event_Logs_Link')->nullable();
            $table->boolean('Event_Logs_isRead')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Event_Logs');
    }
};
