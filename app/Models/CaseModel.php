<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseModel extends Model
{
    use HasFactory;

    protected $table = 'cases';

    protected $fillable = [
        'customer_id',
        'product_id',
        'agent_id',
        'financial_goals',
        'insurance_period',
        'premium_payment_period',
        'overseas_plans',
        'has_existing_health_insurance',
        'height',
        'weight',
        'bmi',
        'weight_change_last_year',
        'smoked_last_year',
        'hospitalization_last_5_years',
        'lab_tests_last_5_years',
        'accident_poisoning_last_5_years',
        'has_disability',
        'has_serious_illness',
        'receiving_treatment',
        'family_medical_history',
        'is_pregnant',
        'health_details',
        'health_risk_score',
        'feature_vector',
        'euclidean_score',
        'weighted_euclidean_score',
        'random_forest_score'
    ];

    protected $casts = [
        'financial_goals' => 'array',
        'feature_vector' => 'array',
        'overseas_plans' => 'boolean',
        'has_existing_health_insurance' => 'boolean',
        'weight_change_last_year' => 'boolean',
        'smoked_last_year' => 'boolean',
        'hospitalization_last_5_years' => 'boolean',
        'lab_tests_last_5_years' => 'boolean',
        'accident_poisoning_last_5_years' => 'boolean',
        'has_disability' => 'boolean',
        'has_serious_illness' => 'boolean',
        'receiving_treatment' => 'boolean',
        'family_medical_history' => 'boolean',
        'is_pregnant' => 'boolean',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'bmi' => 'decimal:2',
        'health_risk_score' => 'decimal:2',
        'euclidean_score' => 'decimal:6',
        'weighted_euclidean_score' => 'decimal:6',
        'random_forest_score' => 'decimal:6',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
