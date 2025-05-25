<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_ftp_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->boolean('use_external_service')->default(false);
            $table->string('editor_theme')->default('monokai');
            $table->boolean('code_beautify')->default(true);
            $table->boolean('code_suggestion')->default(true);
            $table->boolean('auto_complete')->default(true);
            $table->integer('max_upload_size')->default(10); // In MB
            $table->boolean('allow_zip_operations')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('web_ftp_settings');
    }
};