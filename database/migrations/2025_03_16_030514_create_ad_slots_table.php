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
        Schema::create('ad_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name');        // Display name (e.g. "Sidebar Top Banner")
            $table->string('code')->unique();  // Slot code (e.g. "sidebar_top")
            $table->string('page');        // Page where displayed (e.g. "home", "product_detail")
            $table->enum('type', ['predefined', 'dynamic'])->default('predefined');
            $table->string('selector')->nullable();  // CSS selector (for dynamic slots)
            $table->enum('position', ['before', 'after', 'prepend', 'append'])->nullable(); // Insertion position
            $table->text('description')->nullable();  // Description of this position
            $table->string('image')->nullable();      // Visual representation of position
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_slots');
    }
};