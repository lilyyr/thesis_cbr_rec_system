@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Hero Section -->
    <div class="text-center py-20">
        <div class="inline-block mb-6">
            <div class="w-20 h-20 gold-gradient rounded-2xl flex items-center justify-center gold-border-glow">
                <span class="text-black font-bold text-4xl">C</span>
            </div>
        </div>
        <h1 class="text-5xl font-bold text-gray-900 mb-4">Insurance CBR System</h1>
        <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
            AI-Powered Insurance Recommendation Engine
        </p>

        @auth
            <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->user()->isAgent() ? route('consultations.index') : route('client.consultations')) }}"
               class="inline-block px-8 py-4 bg-black text-white hover:bg-gray-900 transition text-lg font-semibold">
                Go to Dashboard
            </a>
        @else
            <a href="{{ route('login') }}" class="inline-block px-8 py-4 gold-gradient text-black hover:opacity-90 transition text-lg font-semibold">
                Get Started
            </a>
        @endauth
    </div>

    <!-- Features -->
    <div class="grid md:grid-cols-3 gap-8 mt-20 mb-20">
        <div class="text-center p-8 border border-gray-200 hover-gold transition">
            <div class="w-16 h-16 bg-black rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-gold-500 text-2xl">🤖</span>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">AI-Powered</h3>
            <p class="text-gray-600">Advanced algorithms analyze 150+ cases to find perfect matches</p>
        </div>

        <div class="text-center p-8 border border-gray-200 hover-gold transition">
            <div class="w-16 h-16 bg-black rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-gold-500 text-2xl">⚡</span>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Lightning Fast</h3>
            <p class="text-gray-600">Get recommendations in under 100ms using optimized ML</p>
        </div>

        <div class="text-center p-8 border border-gray-200 hover-gold transition">
            <div class="w-16 h-16 bg-black rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-gold-500 text-2xl">🎯</span>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Highly Accurate</h3>
            <p class="text-gray-600">3 algorithms combined for maximum precision</p>
        </div>
    </div>

    <!-- Stats -->
    @auth
        @if(auth()->user()->isAdmin() || auth()->user()->isAgent())
            <div class="bg-black text-white p-12 rounded-lg mt-20">
                <h2 class="text-3xl font-bold mb-8 text-center">System Statistics</h2>
                <div class="grid md:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-gold-500 mb-2">{{ \App\Models\CaseModel::count() }}</div>
                        <div class="text-gray-400 text-sm uppercase tracking-wide">Total Cases</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-gold-500 mb-2">{{ \App\Models\Product::count() }}</div>
                        <div class="text-gray-400 text-sm uppercase tracking-wide">Products</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-gold-500 mb-2">{{ \App\Models\User::where('role', 'agent')->count() }}</div>
                        <div class="text-gray-400 text-sm uppercase tracking-wide">Agents</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-gold-500 mb-2">{{ \App\Models\Customer::count() }}</div>
                        <div class="text-gray-400 text-sm uppercase tracking-wide">Customers</div>
                    </div>
                </div>
            </div>
        @endif
    @endauth
</div>
@endsection
