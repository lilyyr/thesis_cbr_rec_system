<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Weight;

class WeightSeeder extends Seeder
{
    public function run(): void
    {
        $weights = [
            // Demographics (4 features)
            ['feature_name' => 'age', 'weight' => 0.15, 'description' => 'Customer age (normalized 18-70)'],
            ['feature_name' => 'gender', 'weight' => 0.05, 'description' => 'Gender (male=1, female=0)'],
            ['feature_name' => 'income', 'weight' => 0.12, 'description' => 'Monthly income (normalized 0-100M)'],
            ['feature_name' => 'num_dependents', 'weight' => 0.10, 'description' => 'Number of dependents (0-10)'],

            // Health Metrics (2 features)
            ['feature_name' => 'bmi', 'weight' => 0.08, 'description' => 'Body Mass Index (normalized 15-40)'],

            // Insurance Needs (4 features)
            ['feature_name' => 'insurance_period', 'weight' => 0.07, 'description' => 'Insurance period in years (1-50)'],
            ['feature_name' => 'premium_payment_period', 'weight' => 0.06, 'description' => 'Premium payment period (1-40)'],
            ['feature_name' => 'overseas_plans', 'weight' => 0.04, 'description' => 'Plans to travel overseas'],
            ['feature_name' => 'has_existing_health_insurance', 'weight' => 0.05, 'description' => 'Already has health insurance'],
            ['feature_name' => 'health_risk_score', 'weight' => 0.15, 'description' => 'Health risk score (0-25)'],

            // Financial Goals (8 features)
            ['feature_name' => 'goal_family_protection', 'weight' => 0.09, 'description' => 'Family protection goal'],
            ['feature_name' => 'goal_health', 'weight' => 0.08, 'description' => 'Health coverage goal'],
            ['feature_name' => 'goal_retirement', 'weight' => 0.07, 'description' => 'Retirement planning goal'],
            ['feature_name' => 'goal_education', 'weight' => 0.08, 'description' => 'Education funding goal'],
            ['feature_name' => 'goal_critical_illness', 'weight' => 0.09, 'description' => 'Critical illness coverage goal'],
            ['feature_name' => 'goal_income_protection', 'weight' => 0.07, 'description' => 'Income protection goal'],
            ['feature_name' => 'goal_savings', 'weight' => 0.06, 'description' => 'Savings and investment goal'],
            ['feature_name' => 'goal_wealth_protection', 'weight' => 0.06, 'description' => 'Wealth protection goal'],
        ];

        foreach ($weights as $weight) {
            Weight::create($weight);
        }

        $this->command->info('✓ Seeded 18 feature weights');
    }
}
