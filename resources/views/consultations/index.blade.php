@extends('layouts.app')

@section('title', 'Consultations')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-4xl font-bold text-gray-900">Consultations</h1>
        <a href="{{ route('consultations.create') }}"
           class="bg-gradient-to-r from-yellow-600 to-yellow-500 text-white px-6 py-3 rounded hover:from-yellow-700 hover:to-yellow-600">
            New Consultation
        </a>
    </div>

    <!-- Loading state -->
    <div id="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-yellow-600"></div>
        <p class="mt-4 text-gray-600">Loading consultations...</p>
    </div>

    <!-- Consultations list -->
    <div id="consultations-list" class="hidden space-y-4"></div>

    <!-- Empty state -->
    <div id="empty-state" class="hidden text-center py-12 bg-white border border-gray-200">
        <p class="text-gray-600 text-lg">No consultations found.</p>
        <a href="{{ route('consultations.create') }}" class="text-yellow-600 hover:text-yellow-700 mt-2 inline-block">
            Create your first consultation
        </a>
    </div>

    <!-- Pagination -->
    <div id="pagination" class="hidden mt-6"></div>
</div>

<script>
// Get API token from meta tag (we'll add this to layout)
const apiToken = document.querySelector('meta[name="api-token"]').content;

// Fetch consultations from API
async function loadConsultations(page = 1) {
    try {
        const response = await fetch(`/api/recommendations?page=${page}`, {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${apiToken}`
            }
        });

        if (!response.ok) {
            throw new Error('Failed to load consultations');
        }

        const result = await response.json();

        // Hide loading
        document.getElementById('loading').classList.add('hidden');

        if (result.data.length === 0) {
            document.getElementById('empty-state').classList.remove('hidden');
            return;
        }

        // Display consultations
        displayConsultations(result.data);
        displayPagination(result.pagination);

    } catch (error) {
        console.error('Error:', error);
        document.getElementById('loading').innerHTML = `
            <div class="text-red-600">
                <p class="text-lg font-semibold">Error loading consultations</p>
                <p class="text-sm">${error.message}</p>
            </div>
        `;
    }
}

function displayConsultations(consultations) {
    const container = document.getElementById('consultations-list');
    container.classList.remove('hidden');

    container.innerHTML = consultations.map(consultation => `
        <div class="bg-white border border-gray-200 p-6 hover:border-yellow-600 transition">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">${consultation.customer.name}</h3>
                    <p class="text-gray-600">Age: ${consultation.customer.age} | BMI: ${consultation.bmi}</p>
                    <p class="text-sm text-gray-500 mt-2">
                        Recommended: <span class="font-semibold text-yellow-600">${consultation.product.name}</span>
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">
                        ${new Date(consultation.created_at).toLocaleDateString()}
                    </div>
                    <div class="mt-2">
                        <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded text-sm font-semibold">
                            ${Math.round((consultation.euclidean_score + consultation.weighted_euclidean_score + consultation.random_forest_score) / 3 * 100)}% Match
                        </span>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <a href="/consultations/${consultation.id}"
                   class="text-yellow-600 hover:text-yellow-700 font-semibold">
                    View Details →
                </a>
                <a href="/consultations/${consultation.id}/process"
                   class="text-gray-600 hover:text-gray-700">
                    View Process
                </a>
            </div>
        </div>
    `).join('');
}

function displayPagination(pagination) {
    if (pagination.last_page <= 1) return;

    const container = document.getElementById('pagination');
    container.classList.remove('hidden');

    let pages = '';
    for (let i = 1; i <= pagination.last_page; i++) {
        const active = i === pagination.current_page;
        pages += `
            <button onclick="loadConsultations(${i})"
                    class="px-4 py-2 ${active ? 'bg-yellow-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'} border border-gray-300">
                ${i}
            </button>
        `;
    }

    container.innerHTML = `<div class="flex gap-1 justify-center">${pages}</div>`;
}

// Load on page load
loadConsultations();
</script>
@endsection
