<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data (optional - be careful in production!)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \App\Models\User::truncate();
        \App\Models\Product::truncate();
        \App\Models\Weight::truncate();
        \App\Models\Customer::truncate();
        \App\Models\CaseModel::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Seed in order
        $this->call([
            AdminSeeder::class,
            ProductSeeder::class,
            WeightSeeder::class,
            OccupationSeeder::class,
            CustomerSeeder::class
        ]);

        $this->command->info('Default Accounts:');
        $this->command->info('  Admin: admin@insurance.com / password123');
        $this->command->info('  Agent: agent@insurance.com / password234');
        $this->command->info('  Client: client@insurance.com / password345');
    }
}
