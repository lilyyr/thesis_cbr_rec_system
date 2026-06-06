@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600 mt-2">System overview and analytics</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid md:grid-cols-3 gap-6 mb-8">
        <!-- Total Consultations -->
        <div class="bg-white border border-gray-200 p-6 hover-gold transition">
            <div class="flex justify-between items-start mb-4">
                <div class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Consultations</div>
                <div class="w-10 h-10 bg-black rounded-full flex items-center justify-center">
                    <span class="text-gold-500 text-lg">📊</span>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900">{{ $total_consultations }}</div>
            <div class="text-xs text-gray-500 mt-2">Total processed</div>
        </div>

        <!-- Products -->
        <div class="bg-white border border-gray-200 p-6 hover-gold transition">
            <div class="flex justify-between items-start mb-4">
                <div class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Products</div>
                <div class="w-10 h-10 bg-black rounded-full flex items-center justify-center">
                    <span class="text-gold-500 text-lg">🏢</span>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900">{{ $total_products }}</div>
            <div class="text-xs text-gray-500 mt-2">{{ $active_products }} active</div>
        </div>

        <!-- Agents -->
        <div class="bg-white border border-gray-200 p-6 hover-gold transition">
            <div class="flex justify-between items-start mb-4">
                <div class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Agents</div>
                <div class="w-10 h-10 bg-black rounded-full flex items-center justify-center">
                    <span class="text-gold-500 text-lg">👥</span>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900">{{ $total_agents }}</div>
            <div class="text-xs text-gray-500 mt-2">{{ $active_agents }} active</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Quick Actions -->
        <div class="lg:col-span-1">
            <div class="bg-black text-white p-6 mb-6">
                <h2 class="text-xl font-bold mb-6 flex items-center">
                    <span class="text-gold-500 mr-2">⚡</span> Quick Actions
                </h2>
                <div class="space-y-3">
                    <a href="{{ route('consultations.create') }}" class="block w-full bg-white text-black px-4 py-3 hover:bg-gray-100 transition text-center font-semibold">
                        New Consultation
                    </a>
                    <a href="{{ route('admin.agents.create') }}" class="block w-full bg-dark-700 text-white px-4 py-3 hover:bg-dark-600 transition text-center font-semibold">
                        Add Agent
                    </a>
                    <a href="{{ route('admin.products.create') }}" class="block w-full bg-dark-700 text-white px-4 py-3 hover:bg-dark-600 transition text-center font-semibold">
                        Add Product
                    </a>
                    <a href="{{ route('clients.create') }}" class="block w-full bg-dark-700 text-white px-4 py-3 hover:bg-dark-600 transition text-center font-semibold">
                        Add Client
                    </a>
                </div>
            </div>

            <!-- Model Training -->
            <div class="bg-white border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                    <span class="text-gold-500 mr-2">🤖</span> ML Model
                </h3>
                <p class="text-sm text-gray-600 mb-4">
                    Train Random Forest model with current case data for improved accuracy.
                </p>
                <form action="{{ route('admin.train-model') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full gold-gradient text-black px-4 py-3 hover:opacity-90 transition font-semibold">
                        Train Model Now
                    </button>
                </form>
                <p class="text-xs text-gray-500 mt-3">Last trained: {{ file_exists(base_path('python/models/rf_model.pkl')) ? date('M d, Y', filemtime(base_path('python/models/rf_model.pkl'))) : 'Never' }}</p>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="lg:col-span-2">
            <div class="bg-white border border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Recent Consultations</h2>

                @if($recent_consultations->count() > 0)
                    <div class="space-y-4">
                        @foreach($recent_consultations as $consultation)
                            <div class="flex items-center justify-between p-4 border border-gray-100 hover:border-gold-500 transition">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900">{{ $consultation->customer->name }}</div>
                                    <div class="text-sm text-gray-600">{{ $consultation->product->name }}</div>
                                    <div class="text-xs text-gray-500 mt-1">{{ $consultation->created_at ? $consultation->created_at->diffForHumans() : 'Unknown date' }}</div>
                                </div>
                                <div class="text-right mr-4">
                                    @php
                                        $avgScore = ($consultation->euclidean_score + $consultation->weighted_euclidean_score + $consultation->random_forest_score) / 3 * 100;
                                    @endphp
                                    <div class="text-2xl font-bold text-gray-900">{{ round($avgScore, 1) }}<span class="text-sm text-gray-500">%</span></div>
                                    <div class="text-xs text-gray-500">Match</div>
                                </div>
                                <a href="{{ route('consultations.show', $consultation->id) }}" class="px-4 py-2 bg-black text-white text-sm hover:bg-gray-900 transition">
                                    View
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12 text-gray-500">
                        No consultations yet. Start your first consultation!
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
