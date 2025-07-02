<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First clear existing users to avoid conflicts
        User::truncate();
        
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'username' => 'mohamed',
            'email' => 'admin@example.com',
            'whatsapp' => '6281234567890',
            'active' => true,
            'role' => 'admin',
            'password' => bcrypt('thalib123'),
        ]);
        
        // Create a test user
        User::create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'whatsapp' => '6281234567891',
            'active' => true,
            'role' => 'user',
            'password' => bcrypt('password'),
        ]);
        
        // Create additional random users for pagination testing
        //User::factory(50)->create();
    }
}
