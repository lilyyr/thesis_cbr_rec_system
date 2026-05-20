<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create default admin account
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@insurance.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'active' => true,
        ]);

        // Create sample agent
        $agent = User::create([
            'name' => 'John Agent',
            'email' => 'agent@insurance.com',
            'password' => Hash::make('password234'),
            'role' => 'agent',
            'active' => true,
            'created_by' => 1, // Created by admin
        ]);

        // Create sample client
        User::create([
            'name' => 'Jane Client',
            'email' => 'client@insurance.com',
            'password' => Hash::make('password345'),
            'role' => 'client',
            'active' => true,
            'created_by' => $agent->id, // Created by agent
        ]);
    }
}
