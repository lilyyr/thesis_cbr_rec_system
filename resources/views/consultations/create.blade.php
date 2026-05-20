@extends('layouts.app')

@section('title', 'New Consultation')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-4xl font-bold text-gray-900 mb-8">New Consultation</h1>

    <!-- Error display -->
    <div id="error-message" class="hidden bg-red-50 border border-red-200 text-red-800 p-4 rounded mb-6"></div>

    <!-- Success message -->
    <div id="success-message" class="hidden bg-green-50 border border-green-200 text-green-800 p-4 rounded mb-6"></div>

    <form id="consultation-form" class="space-y-8">
        <!-- Step 1: Customer Information -->
        <div class="bg-white border border-gray-200 p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Customer Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                    <input type="text" name="name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:border-yellow-600 focus:ring-1 focus:ring-yellow-600">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Gender</label>
                    <select name="gender" required
                            class="w-full px-4 py-2 border border-gray-300 rounded focus:border-yellow-600 focus:ring-1 focus:ring-yellow-600">
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Date of Birth</label>
                    <input type="date" name="dob" required
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:border-yellow-600 focus:ring-1 focus:ring-yellow-600">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Marital Status</label>
                    <select name="marital_status" required
                            class="w-full px-4 py-2 border border-gray-300 rounded focus:border-yellow-600 focus:ring-1 focus:ring-yellow-600">
                        <option value="">Select Status</option>
                        <option value="single">Single</option>
                        <option value="married">Married</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Occupation</label>
                    <select name="occupation_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded focus:border-yellow-600 focus:ring-1 focus:ring-yellow-600">
                        <option value="">Select Occupation</option>
                        @foreach($occupations as $occupation)
                            <option value="{{ $occupation->id }}">{{ $occupation->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Income Range</label>
                    <select name="income_range" required
                            class="w-full px-4 py-2 border border-gray-300 rounded focus:border-yellow-600 focus:ring-1 focus:ring-yellow-600">
                        <option value="">Select Range</option>
                        <option value="below_50m">Below Rp 50M</option>
                        <option value="50m_100m">Rp 50M - 100M</option>
                        <option value="100m_300m">Rp 100M - 300M</option>
                        <option value="300m_500m">Rp 300M - 500M</option>
                        <option value="500m_1b">Rp 500M - 1B</option>
                        <option value="above_1b">Above Rp 1B</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Number of Dependents</label>
                    <input type="number" name="num_dependents" min="0" max="20" required
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:border-yellow-600 focus:ring-1 focus:ring-yellow-600">
                </div>
            </div>
        </div>

        <!-- Step 2: Beneficiary Information -->
        <div class="bg-white border border-gray-200 p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Beneficiary Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Beneficiary Name</label>
                    <input type="text" name="beneficiary_name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:border-yellow-600 focus:ring-1 focus:ring-yellow-600">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Beneficiary DOB</label>
                    <input type="date" name="beneficiary_dob" required
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:border-yellow-600 focus:ring-1 focus:ring-yellow-600">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Beneficiary Gender</label>
                    <select name="beneficiary_gender" required
                            class="w-full px-4 py-2 border border-gray-300 rounded focus:border-yellow-600 focus:ring-1 focus:ring-yellow-600">
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Relationship</label>
                    <select name="beneficiary_relationship" required
                            class="w-full px-4 py-2 border border-gray-300 rounded focus:border-yellow-600 focus:ring-1 focus:ring-yellow-600">
                        <option value="">Select Relationship</option>
                        <option value="adik/kakak kandung">Adik/Kakak Kandung</option>
                        <option value="anak kandung">Anak Kandung</option>
                        <option value="cucu/cicit">Cucu/Cicit</option>
                        <option value="nenek/kakek kandung">Nenek/Kakek Kandung</option>
                        <option value="orang tua kandung">Orang Tua Kandung</option>
                        <option value="suami/istri">Suami/Istri</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Step 3: Financial Goals -->
        <div class="bg-white border border-gray-200 p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Financial Goals & Insurance Needs</h2>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Financial Goals (select all that apply)</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <label class="flex items-center">
                        <input type="checkbox" name="financial_goals[]" value="family_protection" class="mr-2">
                        Family Protection
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="financial_goals[]" value="health" class="mr-2">
                        Health
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="financial_goals[]" value="retirement" class="mr-2">
                        Retirement
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="financial_goals[]" value="education" class="mr-2">
                        Education
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="financial_goals[]" value="critical_illness" class="mr-2">
                        Critical Illness
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="financial_goals[]" value="income_protection" class="mr-2">
                        Income Protection
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="financial_goals[]" value="savings" class="mr-2">
                        Savings
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="financial_goals[]" value="wealth_protection" class="mr-2">
                        Wealth Protection
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Insurance Period (years)</label>
                    <input type="number" name="insurance_period" min="1" max="50" required
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:border-yellow-600 focus:ring-1 focus:ring-yellow-600">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Premium Payment Period (years)</label>
                    <input type="number" name="premium_payment_period" min="1" max="40" required
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:border-yellow-600 focus:ring-1 focus:ring-yellow-600">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Premium Budget (Rp)</label>
                    <input type="number" name="premium_budget" min="0" required
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:border-yellow-600 focus:ring-1 focus:ring-yellow-600">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <label class="flex items-center">
                    <input type="checkbox" name="overseas_plans" value="1" class="mr-2">
                    Plans to travel/work overseas
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="has_existing_health_insurance" value="1" class="mr-2">
                    Has existing health insurance
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="high_risk_hobby" value="1" class="mr-2">
                    Has high-risk hobby
                </label>
            </div>
        </div>

        <!-- Step 4: Health Information -->
        <div class="bg-white border border-gray-200 p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Health Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Height (cm)</label>
                    <input type="number" name="height" min="100" max="250" required
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:border-yellow-600 focus:ring-1 focus:ring-yellow-600">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Weight (kg)</label>
                    <input type="number" name="weight" min="30" max="200" required
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:border-yellow-600 focus:ring-1 focus:ring-yellow-600">
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <label class="flex items-center">
                    <input type="checkbox" name="weight_change_last_year" value="1" class="mr-2">
                    Weight change last year
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="smoked_last_year" value="1" class="mr-2">
                    Smoked last year
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="hospitalization_last_5_years" value="1" class="mr-2">
                    Hospitalization (5 yrs)
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="lab_tests_last_5_years" value="1" class="mr-2">
                    Lab tests (5 yrs)
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="accident_poisoning_last_5_years" value="1" class="mr-2">
                    Accident/poisoning (5 yrs)
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="has_disability" value="1" class="mr-2">
                    Has disability
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="has_serious_illness" value="1" class="mr-2">
                    Has serious illness
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="receiving_treatment" value="1" class="mr-2">
                    Receiving treatment
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="family_medical_history" value="1" class="mr-2">
                    Family medical history
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="is_pregnant" value="1" class="mr-2">
                    Is pregnant (if female)
                </label>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Additional Health Details</label>
                <textarea name="health_details" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded focus:border-yellow-600 focus:ring-1 focus:ring-yellow-600"></textarea>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('consultations.index') }}"
               class="px-6 py-3 border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" id="submit-btn"
                    class="bg-gradient-to-r from-yellow-600 to-yellow-500 text-white px-8 py-3 rounded hover:from-yellow-700 hover:to-yellow-600">
                Generate Recommendation
            </button>
        </div>
    </form>
</div>

<script>
const apiToken = document.querySelector('meta[name="api-token"]').content;

document.getElementById('consultation-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submit-btn');
    const errorDiv = document.getElementById('error-message');
    const successDiv = document.getElementById('success-message');

    // Hide previous messages
    errorDiv.classList.add('hidden');
    successDiv.classList.add('hidden');

    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="inline-block animate-spin mr-2">⟳</span> Processing...';

    // Collect form data
    const formData = new FormData(this);
    const data = {};

    // First, set all boolean fields to false by default
    const booleanFields = [
        'overseas_plans', 'has_existing_health_insurance', 'high_risk_hobby',
        'weight_change_last_year', 'smoked_last_year', 'hospitalization_last_5_years',
        'lab_tests_last_5_years', 'accident_poisoning_last_5_years', 'has_disability',
        'has_serious_illness', 'receiving_treatment', 'family_medical_history', 'is_pregnant'
    ];

    booleanFields.forEach(field => {
        data[field] = false;
    });

    // Convert FormData to JSON
    for (let [key, value] of formData.entries()) {
        if (key === 'financial_goals[]') {
            if (!data.financial_goals) data.financial_goals = [];
            data.financial_goals.push(value);
        } else if (booleanFields.includes(key)) {
            data[key] = true; // If checkbox is checked, it will appear in formData
        } else {
            data[key] = value;
        }
    }

    // Convert FormData to JSON
    // for (let [key, value] of formData.entries()) {
    //     if (key === 'financial_goals[]') {
    //         if (!data.financial_goals) data.financial_goals = [];
    //         data.financial_goals.push(value);
    //     } else if (value === 'on') {
    //         // Checkboxes without explicit value
    //         data[key.replace('[]', '')] = true;
    //     } else {
    //         data[key.replace('[]', '')] = value;
    //     }
    // }

    // Convert checkboxes to boolean
    // const booleanFields = ['overseas_plans', 'has_existing_health_insurance', 'high_risk_hobby',
    //     'weight_change_last_year', 'smoked_last_year', 'hospitalization_last_5_years',
    //     'lab_tests_last_5_years', 'accident_poisoning_last_5_years', 'has_disability',
    //     'has_serious_illness', 'receiving_treatment', 'family_medical_history', 'is_pregnant'];

    // booleanFields.forEach(field => {
    //     data[field] = data[field] === true || data[field] === '1';
    // });

    // Convert numeric strings to numbers
    const numericFields = ['occupation_id', 'num_dependents', 'insurance_period',
            'premium_payment_period', 'premium_budget', 'height', 'weight'];
    numericFields.forEach(field => {
        if (data[field]) {
            data[field] = parseFloat(data[field]);
        }
    });

    console.log('Submitting data:', data);

    try {
        // Call API
        const response = await fetch('/api/recommendations', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${apiToken}`
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (!response.ok) {
            // Validation errors
            if (result.errors) {
                let errorText = '<ul class="list-disc list-inside">';
                for (let field in result.errors) {
                    result.errors[field].forEach(error => {
                        errorText += `<li>${error}</li>`;
                    });
                }
                errorText += '</ul>';
                errorDiv.innerHTML = errorText;
                errorDiv.classList.remove('hidden');
            } else {
                errorDiv.textContent = result.message || 'An error occurred';
                errorDiv.classList.remove('hidden');
            }

            submitBtn.disabled = false;
            submitBtn.textContent = 'Generate Recommendation';
            return;
        }

        // Success! Redirect to show page
        window.location.href = `/consultations/${result.data.consultation_id}`;

    } catch (error) {
        console.error('Error:', error);
        errorDiv.textContent = 'Network error. Please try again.';
        errorDiv.classList.remove('hidden');

        submitBtn.disabled = false;
        submitBtn.textContent = 'Generate Recommendation';
    }
});
</script>
@endsection
