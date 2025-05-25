<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('popup_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->boolean('is_enabled')->default(false);
            $table->string('type')->default('info'); // info, success, warning, danger
            $table->boolean('allow_dismiss')->default(true);
            $table->boolean('show_once')->default(true);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
        });
        
        // User dismissed popup notifications
        Schema::create('popup_notification_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('popup_notification_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('dismissed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('popup_notification_users');
        Schema::dropIfExists('popup_notifications');
    }
};