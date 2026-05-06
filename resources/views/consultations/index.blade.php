@extends('layouts.app')

@section('title', 'Consultations')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Consultations</h1>
            <p class="text-gray-600">View and manage customer consultations</p>
        </div>
        <a href="{{ route('consultations.create') }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition font-semibold">
            + New Consultation
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Consultations</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $consultations->total() }}</p>
                </div>
                <div class="text-4xl">📊</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">This Month</p>
                    <p class="text-3xl font-bold text-green-600">
                        {{ \App\Models\CaseModel::whereMonth('created_at', now()->month)
                            ->when(auth()->user()->isAgent(), function($q) {
                                return $q->where('agent_id', auth()->id());
                            })->count() }}
                    </p>
                </div>
                <div class="text-4xl">📅</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Avg Match Score</p>
                    <p class="text-3xl font-bold text-purple-600">
                        {{ number_format(\App\Models\CaseModel::when(auth()->user()->isAgent(), function($q) {
                                return $q->where('agent_id', auth()->id());
                            })->avg(\DB::raw('(euclidean_score + weighted_euclidean_score + random_forest_score) / 3')) * 100, 1) }}%
                    </p>
                </div>
                <div class="text-4xl">⭐</div>
            </div>
        </div>
    </div>

    <!-- Consultations Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Match Score</th>
                    @if(auth()->user()->isAdmin())
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agent</th>
                    @endif
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($consultations as $consultation)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-gray-900">{{ $consultation->customer->name }}</div>
                            <div class="text-sm text-gray-500">{{ $consultation->customer->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-gray-900">{{ $consultation->product->name }}</div>
                            <div class="text-sm text-gray-500">Rp {{ number_format($consultation->product->base_premium, 0, ',', '.') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $avgScore = ($consultation->euclidean_score + $consultation->weighted_euclidean_score + $consultation->random_forest_score) / 3 * 100;
                            @endphp
                            <div class="flex items-center">
                                <span class="text-2xl font-bold {{ $avgScore >= 80 ? 'text-green-600' : ($avgScore >= 60 ? 'text-blue-600' : 'text-orange-600') }}">
                                    {{ round($avgScore, 1) }}%
                                </span>
                            </div>
                        </td>
                        @if(auth()->user()->isAdmin())
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $consultation->agent->name ?? 'N/A' }}</div>
                            </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $consultation->created_at->format('M d, Y') }}
                            <div class="text-xs text-gray-400">{{ $consultation->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('consultations.show', $consultation->id) }}" class="text-blue-600 hover:text-blue-900">
                                View Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isAdmin() ? '6' : '5' }}" class="px-6 py-12 text-center text-gray-500">
                            No consultations found. Start your first consultation!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($consultations->hasPages())
        <div class="mt-6">
            {{ $consultations->links() }}
        </div>
    @endif
</div>
@endsection
