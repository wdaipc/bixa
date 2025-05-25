<?php

namespace Database\Seeders;

use App\Models\IconCaptchaSetting;
use Illuminate\Database\Seeder;

class IconCaptchaSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'enabled',
                'value' => '1'
            ],
            [
                'key' => 'theme',
                'value' => 'light'
            ]
        ];

        foreach ($settings as $setting) {
            IconCaptchaSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}