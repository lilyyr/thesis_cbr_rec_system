@extends('layouts.app')

@section('title', 'New Consultation')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900">New Consultation</h1>
        <p class="text-gray-600 mt-2">Enter customer details and get AI-powered insurance recommendations</p>
    </div>

    <form method="POST" action="{{ route('consultations.store') }}" id="consultationForm">
        @csrf

        <!-- Step 1: Customer Information -->
        <div class="bg-white border border-gray-200 p-8 mb-6">
            <div class="flex items-center mb-6">
                <div class="w-10 h-10 bg-black text-white rounded-full flex items-center justify-center font-bold mr-3">1</div>
                <h2 class="text-2xl font-bold text-gray-900">Customer Information</h2>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-gray-900 text-sm font-semibold mb-2">
                        Full Name <span class="text-gold-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        placeholder="John Doe"
                        class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gold-500 transition @error('name') border-red-500 @enderror"
                    >
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Gender -->
                <div>
                    <label for="gender" class="block text-gray-900 text-sm font-semibold mb-2">
                        Gender <span class="text-gold-500">*</span>
                    </label>
                    <select
                        id="gender"
                        name="gender"
                        required
                        class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gold-500 transition @error('gender') border-red-500 @enderror"
                    >
                        <option value="">Select gender...</option>
                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                    </select>
                    @error('gender')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Date of Birth -->
                <div>
                    <label for="dob" class="block text-gray-900 text-sm font-semibold mb-2">
                        Date of Birth <span class="text-gold-500">*</span>
                    </label>
                    <input
                        type="date"
                        id="dob"
                        name="dob"
                        value="{{ old('dob') }}"
                        required
                        max="{{ date('Y-m-d') }}"
                        class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gold-500 transition @error('dob') border-red-500 @enderror"
                    >
                    @error('dob')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Occupation -->
                <div>
                    <label for="occupation" class="block text-gray-900 text-sm font-semibold mb-2">
                        Occupation <span class="text-gold-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="occupation"
                        name="occupation"
                        value="{{ old('occupation') }}"
                        required
                        placeholder="Software Engineer"
                        class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gold-500 transition @error('occupation') border-red-500 @enderror"
                    >
                    @error('occupation')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Income -->
                <div>
                    <label for="income" class="block text-gray-900 text-sm font-semibold mb-2">
                        Monthly Income (Rp) <span class="text-gold-500">*</span>
                    </label>
                    <input
                        type="number"
                        id="income"
                        name="income"
                        value="{{ old('income') }}"
                        required
                        min="0"
                        step="100000"
                        placeholder="15000000"
                        class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gold-500 transition @error('income') border-red-500 @enderror"
                    >
                    @error('income')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Number of Dependents -->
                <div>
                    <label for="num_dependents" class="block text-gray-900 text-sm font-semibold mb-2">
                        Number of Dependents <span class="text-gold-500">*</span>
                    </label>
                    <input
                        type="number"
                        id="num_dependents"
                        name="num_dependents"
                        value="{{ old('num_dependents', 0) }}"
                        required
                        min="0"
                        max="20"
                        class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gold-500 transition @error('num_dependents') border-red-500 @enderror"
                    >
                    @error('num_dependents')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Step 2: Financial Goals -->
        <div class="bg-white border border-gray-200 p-8 mb-6">
            <div class="flex items-center mb-6">
                <div class="w-10 h-10 bg-black text-white rounded-full flex items-center justify-center font-bold mr-3">2</div>
                <h2 class="text-2xl font-bold text-gray-900">Financial Goals & Insurance Needs</h2>
            </div>

            <!-- Financial Goals -->
            <div class="mb-8">
                <label class="block text-gray-900 text-sm font-semibold mb-3">
                    Financial Goals <span class="text-gold-500">*</span>
                </label>
                <p class="text-sm text-gray-600 mb-4">Select all that apply (minimum 1)</p>

                <div class="grid md:grid-cols-2 gap-3">
                    @php
                        $goals = [
                            'family_protection' => 'Family Protection',
                            'health' => 'Health Coverage',
                            'retirement' => 'Retirement Planning',
                            'education' => 'Education Funding',
                            'critical_illness' => 'Critical Illness Coverage',
                            'income_protection' => 'Income Protection',
                            'savings' => 'Savings & Investment',
                            'wealth_protection' => 'Wealth Protection' // need to remove this
                        ];
                    @endphp

                    @foreach($goals as $key => $label)
                        <label class="flex items-center p-4 border border-gray-200 hover:border-gold-500 transition cursor-pointer">
                            <input
                                type="checkbox"
                                name="financial_goals[]"
                                value="{{ $key }}"
                                {{ in_array($key, old('financial_goals', [])) ? 'checked' : '' }}
                                class="rounded border-gray-400 text-gold-500 focus:ring-gold-500"
                            >
                            <span class="ml-3 text-gray-900">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('financial_goals')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Insurance Periods -->
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="insurance_period" class="block text-gray-900 text-sm font-semibold mb-2">
                        Insurance Period (years) <span class="text-gold-500">*</span>
                    </label>
                    <input
                        type="number"
                        id="insurance_period"
                        name="insurance_period"
                        value="{{ old('insurance_period', 20) }}"
                        min="1"
                        max="50"
                        required
                        class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gold-500 transition"
                    >
                    <p class="text-xs text-gray-500 mt-1">Coverage duration (1-50 years)</p>
                </div>

                <div>
                    <label for="premium_payment_period" class="block text-gray-900 text-sm font-semibold mb-2">
                        Premium Payment Period (years) <span class="text-gold-500">*</span>
                    </label>
                    <input
                        type="number"
                        id="premium_payment_period"
                        name="premium_payment_period"
                        value="{{ old('premium_payment_period', 15) }}"
                        min="1"
                        max="40"
                        required
                        class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gold-500 transition"
                    >
                    <p class="text-xs text-gray-500 mt-1">Payment duration (1-40 years)</p>
                </div>
            </div>

            <!-- Checkboxes -->
            <div class="grid md:grid-cols-2 gap-4">
                <label class="flex items-center p-4 border border-gray-200 hover:border-gold-500 transition cursor-pointer">
                    <input
                        type="checkbox"
                        name="overseas_plans"
                        value="1"
                        {{ old('overseas_plans') ? 'checked' : '' }}
                        class="rounded border-gray-400 text-gold-500 focus:ring-gold-500"
                    >
                    <span class="ml-3 text-gray-900">Plans to travel overseas</span>
                </label>

                <label class="flex items-center p-4 border border-gray-200 hover:border-gold-500 transition cursor-pointer">
                    <input
                        type="checkbox"
                        name="has_existing_health_insurance"
                        value="1"
                        {{ old('has_existing_health_insurance') ? 'checked' : '' }}
                        class="rounded border-gray-400 text-gold-500 focus:ring-gold-500"
                    >
                    <span class="ml-3 text-gray-900">Has existing health insurance</span>
                </label>
            </div>
        </div>

        <!-- Step 3: Health Information -->
        <div class="bg-white border border-gray-200 p-8 mb-6">
            <div class="flex items-center mb-6">
                <div class="w-10 h-10 bg-black text-white rounded-full flex items-center justify-center font-bold mr-3">3</div>
                <h2 class="text-2xl font-bold text-gray-900">Health Information</h2>
            </div>

            <!-- Height & Weight -->
            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <div>
                    <label for="height" class="block text-gray-900 text-sm font-semibold mb-2">
                        Height (cm) <span class="text-gold-500">*</span>
                    </label>
                    <input
                        type="number"
                        id="height"
                        name="height"
                        value="{{ old('height') }}"
                        min="100"
                        max="250"
                        step="0.1"
                        required
                        placeholder="170"
                        class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gold-500 transition"
                    >
                </div>

                <div>
                    <label for="weight" class="block text-gray-900 text-sm font-semibold mb-2">
                        Weight (kg) <span class="text-gold-500">*</span>
                    </label>
                    <input
                        type="number"
                        id="weight"
                        name="weight"
                        value="{{ old('weight') }}"
                        min="30"
                        max="200"
                        step="0.1"
                        required
                        placeholder="70"
                        class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gold-500 transition"
                    >
                </div>
            </div>

            <!-- Health History -->
            <div class="mb-6">
                <label class="block text-gray-900 text-sm font-semibold mb-3">
                    Health History (check all that apply)
                </label>

                <div class="grid md:grid-cols-2 gap-3">
                    @php
                        $healthQuestions = [
                            'weight_change_last_year' => 'Significant weight change in last year',
                            'smoked_last_year' => 'Smoked in the last year',
                            'hospitalization_last_5_years' => 'Hospitalized in last 5 years',
                            'lab_tests_last_5_years' => 'Lab tests in last 5 years',
                            'accident_poisoning_last_5_years' => 'Accidents/poisoning in last 5 years',
                            'has_disability' => 'Has disability',
                            'has_serious_illness' => 'Has serious illness',
                            'receiving_treatment' => 'Currently receiving medical treatment',
                            'family_medical_history' => 'Family medical history of serious conditions',
                            'is_pregnant' => 'Currently pregnant (if applicable)'
                        ];
                    @endphp

                    @foreach($healthQuestions as $key => $label)
                        <label class="flex items-start p-4 border border-gray-200 hover:border-gold-500 transition cursor-pointer">
                            <input
                                type="checkbox"
                                name="{{ $key }}"
                                value="1"
                                {{ old($key) ? 'checked' : '' }}
                                class="mt-1 rounded border-gray-400 text-gold-500 focus:ring-gold-500"
                            >
                            <span class="ml-3 text-gray-900 text-sm">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Health Details -->
            <div>
                <label for="health_details" class="block text-gray-900 text-sm font-semibold mb-2">
                    Additional Health Details (Optional)
                </label>
                <textarea
                    id="health_details"
                    name="health_details"
                    rows="4"
                    maxlength="1000"
                    placeholder="Provide any additional health information that may be relevant..."
                    class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gold-500 transition"
                >{{ old('health_details') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Maximum 1000 characters</p>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="bg-black text-white p-8">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-2xl font-bold mb-2">Ready to Get Recommendations?</h3>
                    <p class="text-gray-400">
                        Our AI will analyze {{ \App\Models\CaseModel::count() }}+ cases using 3 advanced algorithms
                    </p>
                </div>
                <button
                    type="submit"
                    class="gold-gradient text-black px-12 py-4 hover:opacity-90 transition font-bold text-lg"
                >
                    Generate
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.getElementById('consultationForm').addEventListener('submit', function(e) {
        const checkboxes = document.querySelectorAll('input[name="financial_goals[]"]');
        const checked = Array.from(checkboxes).some(cb => cb.checked);

        if (!checked) {
            e.preventDefault();
            alert('Please select at least one financial goal.');
            return false;
        }
    });
</script>
@endpush
@endsection
