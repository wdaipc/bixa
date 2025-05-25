<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdSlot;

class AdSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default predefined ad slots
        $defaultSlots = [
            [
                'name' => 'Header Banner',
                'code' => 'header_banner',
                'page' => 'all',
                'type' => 'predefined',
                'description' => 'Main banner at the top of the page, displayed on all pages.',
                'is_active' => true,
            ],
            [
                'name' => 'Content Top Banner',
                'code' => 'content_top',
                'page' => 'all',
                'type' => 'predefined',
                'description' => 'Banner displayed at the top of the main content area.',
                'is_active' => true,
            ],
            [
                'name' => 'Content Bottom Banner',
                'code' => 'content_bottom',
                'page' => 'all',
                'type' => 'predefined',
                'description' => 'Banner displayed at the bottom of the main content area.',
                'is_active' => true,
            ],
            [
                'name' => 'Sidebar Top',
                'code' => 'sidebar_top',
                'page' => 'all',
                'type' => 'predefined',
                'description' => 'Banner displayed at the top of the sidebar.',
                'is_active' => true,
            ],
            [
                'name' => 'Sidebar Bottom',
                'code' => 'sidebar_bottom',
                'page' => 'all',
                'type' => 'predefined',
                'description' => 'Banner displayed at the bottom of the sidebar.',
                'is_active' => true,
            ],
            [
                'name' => 'Footer Banner',
                'code' => 'footer_banner',
                'page' => 'all',
                'type' => 'predefined',
                'description' => 'Banner displayed in the footer area.',
                'is_active' => true,
            ],
        ];

        foreach ($defaultSlots as $slot) {
            AdSlot::firstOrCreate(['code' => $slot['code']], $slot);
        }
    }
}