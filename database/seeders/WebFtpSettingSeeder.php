<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WebFtpSetting;
use Illuminate\Support\Facades\DB;

class WebFtpSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Kiểm tra xem đã có bản ghi nào chưa
        if (DB::table('web_ftp_settings')->count() === 0) {
            WebFtpSetting::create([
                'enabled' => true,
                'use_external_service' => false,
                'editor_theme' => 'monokai',
                'code_beautify' => true,
                'code_suggestion' => true,
                'auto_complete' => true,
                'max_upload_size' => 10,
                'allow_zip_operations' => true
            ]);
            
            $this->command->info('WebFTP settings created successfully!');
        } else {
            $this->command->info('WebFTP settings already exist. Skipping...');
        }
    }
}