<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('staff_ratings', function (Blueprint $table) {
            $table->id();
            // Tham chiếu đến bảng tickets
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            
            // Tham chiếu đến bảng messages (không tạo foreign key constraint)
            // Vì bảng messages được config trong package
            $table->unsignedBigInteger('message_id');
            
            // Tham chiếu đến người dùng đánh giá
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Tham chiếu đến admin nhận đánh giá
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            
            // Thông tin đánh giá
            $table->integer('rating')->comment('Đánh giá từ 1-5 sao');
            $table->text('comment')->nullable()->comment('Nhận xét của người dùng');
            
            $table->timestamps();
            
            // Tạo index unique để tránh đánh giá trùng lặp
            $table->unique(['message_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_ratings');
    }
};