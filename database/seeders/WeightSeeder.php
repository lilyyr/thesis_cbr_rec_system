<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Weight;

class WeightSeeder extends Seeder
{
    public function run(): void
    {
        $weights = [

            // Demographics
            [
                'feature_name' => 'age',
                'weight' => 0.10,
                'description' => 'Customer age'
            ],
            [
                'feature_name' => 'gender',
                'weight' => 0.03,
                'description' => 'Gender'
            ],
            [
                'feature_name' => 'marital_status',
                'weight' => 0.07,
                'description' => 'Marital status'
            ],
            [
                'feature_name' => 'occupation_risk',
                'weight' => 0.08,
                'description' => 'Occupation risk level'
            ],
            [
                'feature_name' => 'num_dependents',
                'weight' => 0.08,
                'description' => 'Number of dependents'
            ],

            // Health
            [
                'feature_name' => 'bmi',
                'weight' => 0.08,
                'description' => 'BMI'
            ],
            [
                'feature_name' => 'insurance_period',
                'weight' => 0.05,
                'description' => 'Insurance period'
            ],
            [
                'feature_name' => 'health_risk_score',
                'weight' => 0.12,
                'description' => 'Health risk score'
            ],
            [
                'feature_name' => 'overseas_medical_plans',
                'weight' => 0.03,
                'description' => 'Overseas plans'
            ],
            [
                'feature_name' => 'existing_health_insurance',
                'weight' => 0.05,
                'description' => 'Existing health insurance'
            ],
            [
                'feature_name' => 'high_risk_hobby',
                'weight' => 0.06,
                'description' => 'High risk hobby'
            ],
            [
                'feature_name' => 'nominal_received',
                'weight' => 0.10,
                'description' => 'Nominal received'
            ],
            [
                'feature_name' => 'beneficiary_relationship',
                'weight' => 0.05,
                'description' => 'Beneficiary relationship closeness'
            ],

            // Coverage Regions
            [
                'feature_name' => 'coverage_asia_exc',
                'weight' => 0.05,
                'description' => 'Coverage for Asia except HKG, SG, JPN'
            ],
            [
                'feature_name' => 'coverage_hkg_sg_jpn',
                'weight' => 0.05,
                'description' => 'Coverage for HKG, SG, JPN'
            ],
            [
                'feature_name' => 'coverage_europe',
                'weight' => 0.05,
                'description' => 'Coverage for Europe'
            ],
            [
                'feature_name' => 'coverage_north_america',
                'weight' => 0.05,
                'description' => 'Coverage for North America'
            ],
            [
                'feature_name' => 'coverage_south_america',
                'weight' => 0.05,
                'description' => 'Coverage for South America'
            ],
            [
                'feature_name' => 'coverage_africa',
                'weight' => 0.05,
                'description' => 'Coverage for Africa'
            ],
            [
                'feature_name' => 'coverage_oceania',
                'weight' => 0.05,
                'description' => 'Coverage for Oceania'
            ],


            // Financial Goals
            [
                'feature_name' => 'goal_life',
                'weight' => 0.07,
                'description' => 'Family protection goal'
            ],
            [
                'feature_name' => 'goal_health',
                'weight' => 0.07,
                'description' => 'Health goal'
            ],
            [
                'feature_name' => 'goal_retirement',
                'weight' => 0.06,
                'description' => 'Retirement goal'
            ],
            [
                'feature_name' => 'goal_education',
                'weight' => 0.06,
                'description' => 'Education goal'
            ],
            [
                'feature_name' => 'goal_critical_illness',
                'weight' => 0.08,
                'description' => 'Critical illness goal'
            ],
            [
                'feature_name' => 'goal_income_protection',
                'weight' => 0.06,
                'description' => 'Income protection goal'
            ],
            [
                'feature_name' => 'goal_savings',
                'weight' => 0.05,
                'description' => 'Savings goal'
            ],
            [
                'feature_name' => 'goal_accidents',
                'weight' => 0.05,
                'description' => 'Accidents goal'
            ],
            [
                'feature_name' => 'holder_income',
                'weight' => 0.05,
                'description' => 'Holder income'
            ],
            [
                'feature_name' => 'holder_relationship_to_insured',
                'weight' => 0.05,
                'description' => 'Holder relationship status to insured'
            ],
        ];

        Weight::truncate();

        foreach ($weights as $weight) {
            Weight::create($weight);
        }
    }
}
