<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('popup_notification_users', function (Blueprint $table) {
            $table->timestamps(); // Thêm created_at và updated_at
            $table->unique(['popup_notification_id', 'user_id']); // Thêm ràng buộc unique
        });
    }

    public function down(): void
    {
        Schema::table('popup_notification_users', function (Blueprint $table) {
            $table->dropTimestamps();
            $table->dropUnique(['popup_notification_id', 'user_id']);
        });
    }
};