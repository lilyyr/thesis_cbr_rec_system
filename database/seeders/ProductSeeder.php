<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Life Protection Plus',
                'description' => 'Comprehensive life insurance with critical illness coverage',
                'categories' => ['life', 'critical_illness'],
                'base_premium' => 5000000,
                'active' => true
            ],
            [
                'name' => 'Family Shield',
                'description' => 'Family protection with education benefits',
                'categories' => ['life', 'family_protection', 'education'],
                'base_premium' => 7500000,
                'active' => true
            ],
            [
                'name' => 'Health Care Plus',
                'description' => 'Medical and hospitalization coverage',
                'categories' => ['health', 'medical'],
                'base_premium' => 4000000,
                'active' => true
            ],
            [
                'name' => 'Retirement Savings Plan',
                'description' => 'Long-term savings with retirement benefits',
                'categories' => ['savings', 'retirement'],
                'base_premium' => 10000000,
                'active' => true
            ],
            [
                'name' => 'Income Protection',
                'description' => 'Income replacement in case of disability',
                'categories' => ['income_protection', 'disability'],
                'base_premium' => 3500000,
                'active' => true
            ],
            [
                'name' => 'Wealth Builder',
                'description' => 'Investment-linked insurance for wealth accumulation',
                'categories' => ['savings', 'wealth_protection', 'investment'],
                'base_premium' => 12000000,
                'active' => true
            ]
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        $this->command->info('✓ Seeded 6 products');
    }
}
