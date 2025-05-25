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
        Schema::create('iperf_servers', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->integer('port')->default(5201);
            $table->string('country_code', 2);
            $table->string('country_name');
            $table->string('provider')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Add index for faster lookups
            $table->index('country_code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iperf_servers');
    }
};