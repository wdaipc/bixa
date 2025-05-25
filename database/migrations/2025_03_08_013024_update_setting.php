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
        // No need to create a new table if we're using the existing settings table
        // Just make sure the settings table exists
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
        
        // Add Imgur settings to the settings table
        DB::table('settings')->insertOrIgnore([
            ['key' => 'imgur_client_id', 'value' => null, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'imgur_client_secret', 'value' => null, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'enable_image_upload', 'value' => '0', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Imgur settings from the settings table
        DB::table('settings')->whereIn('key', [
            'imgur_client_id',
            'imgur_client_secret',
            'enable_image_upload',
        ])->delete();
    }
};