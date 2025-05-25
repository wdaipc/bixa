<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Administrator',
            'email' => 'hi@bixa.app',
            'email_verified_at' => now(),
            'password' => Hash::make('123456'),
            'role' => 'admin',
        ]);

        // Create a test user
        User::create([
            'name' => 'Test User',
            'email' => 'test@bixa.app',
            'email_verified_at' => now(),
            'password' => Hash::make('123456'),
            'role' => 'user',
        ]);
    }
}