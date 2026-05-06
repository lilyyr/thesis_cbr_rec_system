<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CaseModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    /**
     * Display list of clients
     */
    public function index()
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $query = User::where('role', 'client');

        // If agent, only show their own clients
        if ($currentUser->isAgent()) {
            $query->where('created_by', Auth::id());
        }

        $clients = $query->latest()->paginate(15);

        return view('clients.index', compact('clients'));
    }

    /**
     * Show create client form
     */
    public function create()
    {
        return view('clients.create');
    }

    /**
     * Store new client
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $client = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'client',
            'active' => true,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('clients.index')
            ->with('success', 'Client created successfully!');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $client = User::where('role', 'client')->findOrFail($id);

        // Check permission
        if ($currentUser->isAgent() && $client->created_by !== Auth::id()) {
            abort(403);
        }

        return view('clients.edit', compact('client'));
    }

    /**
     * Update client
     */
    public function update(Request $request, $id)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $client = User::where('role', 'client')->findOrFail($id);

        // Check permission
        if ($currentUser->isAgent() && $client->created_by !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $client->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $client->name = $validated['name'];
        $client->email = $validated['email'];

        if (!empty($validated['password'])) {
            $client->password = Hash::make($validated['password']);
        }

        $client->save();

        return redirect()->route('clients.index')
            ->with('success', 'Client updated successfully!');
    }

    /**
     * Delete client
     */
    public function destroy($id)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $client = User::where('role', 'client')->findOrFail($id);

        // Check permission
        if ($currentUser->isAgent() && $client->created_by !== Auth::id()) {
            abort(403);
        }

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client deleted successfully!');
    }

    /**
     * Show client's consultations (for client role)
     */
    public function myConsultations()
    {
        // Get consultations where the customer email matches the logged-in client email
        // OR where the client was explicitly linked
        $consultations = CaseModel::with(['customer', 'product', 'agent'])
            ->whereHas('customer', function($query) {
                $query->where('email', Auth::user()->email);
            })
            ->latest()
            ->paginate(10);

        return view('client.consultations', compact('consultations'));
    }

    /**
     * Show specific consultation (for client role)
     */
    public function showConsultation($id)
    {
        $consultation = CaseModel::with(['customer', 'product', 'agent'])->findOrFail($id);

        // Check if this consultation belongs to this client
        if ($consultation->customer->email !== Auth::user()->email) {
            abort(403);
        }

        return view('client.show', compact('consultation'));
    }
}
