<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\CaseModel;
use Faker\Factory as Faker;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID'); // Indonesian locale

        $financial_goals_options = [
            'family_protection', 'health', 'retirement', 'education',
            'critical_illness', 'income_protection', 'savings', 'wealth_protection'
        ];

        // Create 30 sample customers with cases
        for ($i = 1; $i <= 30; $i++) {
            $gender = $faker->randomElement(['male', 'female']);
            $age = $faker->numberBetween(25, 60);
            $dob = now()->subYears($age)->format('Y-m-d');
            $marital_status = $faker->randomElement(['single', 'married']);
            $income_range = $faker->randomElement(['below_50m','50m_100m','100m_300m','300m_500m','500m_1b','above_1b']);

            // Create customer
            $customer = Customer::create([
                'name' => $faker->name($gender === 'male' ? 'male' : 'female'),
                'gender' => $gender,
                'marital_status' => $marital_status,
                'dob' => $dob,
                'occupation_id' => $faker->numberBetween(1,207),
                'income_range' => $income_range,
                'num_dependents' => $faker->numberBetween(0, 5)
            ]);

            // Create 3-5 cases for each customer
            $numCases = $faker->numberBetween(3, 5);

            for ($j = 0; $j < $numCases; $j++) {
                $height = $faker->numberBetween(150, 190);
                $weight = $faker->numberBetween(45, 100);
                $bmi = $weight / (($height / 100) ** 2);

                // Random financial goals (2-4 goals)
                $numGoals = $faker->numberBetween(2, 4);
                $goals = $faker->randomElements($financial_goals_options, $numGoals);

                // Random health conditions
                $healthConditions = [
                    'weight_change_last_year' => $faker->boolean(20),
                    'smoked_last_year' => $faker->boolean(15),
                    'hospitalization_last_5_years' => $faker->boolean(25),
                    'lab_tests_last_5_years' => $faker->boolean(40),
                    'accident_poisoning_last_5_years' => $faker->boolean(10),
                    'has_disability' => $faker->boolean(5),
                    'has_serious_illness' => $faker->boolean(8),
                    'receiving_treatment' => $faker->boolean(12),
                    'family_medical_history' => $faker->boolean(30),
                    'is_pregnant' => $gender === 'female' && $age < 45 ? $faker->boolean(10) : null,
                ];

                // Calculate health risk score
                $health_risk = 0;
                $health_risk += $healthConditions['weight_change_last_year'] ? 1.0 : 0;
                $health_risk += $healthConditions['smoked_last_year'] ? 2.0 : 0;
                $health_risk += $healthConditions['hospitalization_last_5_years'] ? 3.0 : 0;
                $health_risk += $healthConditions['lab_tests_last_5_years'] ? 1.0 : 0;
                $health_risk += $healthConditions['accident_poisoning_last_5_years'] ? 2.5 : 0;
                $health_risk += $healthConditions['has_disability'] ? 4.0 : 0;
                $health_risk += $healthConditions['has_serious_illness'] ? 5.0 : 0;
                $health_risk += $healthConditions['receiving_treatment'] ? 3.0 : 0;
                $health_risk += $healthConditions['family_medical_history'] ? 1.5 : 0;
                if ($healthConditions['is_pregnant']) $health_risk += 0.5;

                // BMI risk
                if ($bmi < 18.5) $health_risk += 2.0;
                elseif ($bmi >= 30) $health_risk += 3.0;
                elseif ($bmi >= 25) $health_risk += 1.0;

                // Create feature vector
                $feature_vector = $this->createFeatureVector(
                    $age, $gender, $marital_status, $income_range, $customer->occupation_id, $customer->num_dependents,
                    $bmi, $faker->numberBetween(5, 30), $faker->numberBetween(5, 25), $health_risk,
                    $faker->boolean(30), $faker->boolean(20), $faker->boolean(15), $faker->numberBetween(100000, 10000000),
                    $faker->randomElement(['adik/kakak kandung','anak kandung,cucu/cicit','nenek/kakek kandung','orang tua kandung','suami/istri','lainnya']),
                    $goals
                );

                // Create case
                CaseModel::create([
                    'customer_id' => $customer->id,
                    'product_id' => $faker->numberBetween(1, 6), // Random product
                    'financial_goals' => $goals,
                    'insurance_period' => $faker->numberBetween(5, 30),
                    'premium_payment_period' => $faker->numberBetween(5, 25),
                    'overseas_plans' => $faker->boolean(30),
                    'has_existing_health_insurance' => $faker->boolean(20),
                    'high_risk_hobby' => $faker->boolean(15),
                    'premium_budget' => $faker->numberBetween(100000, 10000000),
                    'beneficiary_name' => $faker->name,
                    'beneficiary_relationship' => $faker->randomElement(['adik/kakak kandung','anak kandung','cucu/cicit','nenek/kakek kandung','orang tua kandung','suami/istri','lainnya']),
                    'height' => $height,
                    'weight' => $weight,
                    'bmi' => round($bmi, 2),
                    ...$healthConditions,
                    'health_risk_score' => $health_risk,
                    'feature_vector' => $feature_vector,
                ]);
            }
        }

        $this->command->info('✓ Seeded 30 customers with cases');
    }

    private function createFeatureVector($age, $gender, $marital_status, $income_range, $occupation_id, $dependents, $bmi,
                                        $ins_period, $prem_period, $health_risk, $overseas, $health_ins,
                                        $high_risk_hobby, $premium_budget, $beneficiary_relationship, $goals)
    {

        $incomeMap = [
            'below_50m' => 25000000,
            '50m_100m' => 75000000,
            '100m_300m' => 200000000,
            '300m_500m' => 400000000,
            '500m_1b' => 750000000,
            'above_1b' => 1500000000
        ];

        $income = $incomeMap[$income_range] ?? 0;

        $occupation_risk = $occupation_id <= 50 ? 1 : ($occupation_id <= 150 ? 2 : 3);

        $relationship_map = [
            'orang tua kandung'=> 1.0,
            'suami/istri'=> 0.9,
            'anak kandung'=> 0.8,
            'adik/kakak kandung'=> 0.7,
            'nenek/kakek kandung'=> 0.6,
            'cucu/cicit'=> 0.5,
            'lainnya'=> 0.3
        ];


        // Normalize values
        $age_norm = ($age - 18) / (70 - 18);
        $gender_enc = $gender === 'male' ? 1 : 0;
        $marital_enc = $marital_status === 'married' ? 1 : 0;
        $income_norm = $income / 1500000000;
        $occupation_risk_norm = ($occupation_risk - 1) / (3 - 1);
        $dep_norm = $dependents / 10;
        $bmi_norm = ($bmi - 15) / (40 - 15);
        $ins_norm = ($ins_period - 1) / (50 - 1);
        $prem_norm = ($prem_period - 1) / (40 - 1);
        $health_risk_norm = $health_risk / 25;
        $overseas_enc = $overseas ? 1 : 0;
        $health_ins_enc = $health_ins ? 1 : 0;
        $high_risk_hobby_enc = $high_risk_hobby ? 1 : 0;
        $premium_budget_norm = $premium_budget / 10000000;
        $beneficiary_enc = $relationship_map[$beneficiary_relationship] ?? 0.3;

        // Goals encoding
        $goal_family = in_array('family_protection', $goals) ? 1 : 0;
        $goal_health = in_array('health', $goals) ? 1 : 0;
        $goal_retirement = in_array('retirement', $goals) ? 1 : 0;
        $goal_education = in_array('education', $goals) ? 1 : 0;
        $goal_critical = in_array('critical_illness', $goals) ? 1 : 0;
        $goal_income = in_array('income_protection', $goals) ? 1 : 0;
        $goal_savings = in_array('savings', $goals) ? 1 : 0;
        $goal_wealth = in_array('wealth_protection', $goals) ? 1 : 0;

        return [
            $age_norm, $gender_enc, $marital_enc, $income_norm, $occupation_risk_norm, $dep_norm, $bmi_norm,
            $ins_norm, $prem_norm, $overseas_enc, $health_ins_enc, $health_risk_norm,
            $high_risk_hobby_enc, $premium_budget_norm, $beneficiary_enc,
            $goal_family, $goal_health, $goal_retirement, $goal_education,
            $goal_critical, $goal_income, $goal_savings, $goal_wealth
        ];
    }
}
