<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CaseModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function index()
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $query = User::where('role', 'client');

        if ($currentUser->isAgent()) {
            $query->where('created_by', Auth::id());
        }

        $clients = $query->latest()->paginate(15);

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'role'       => 'client',
            'active'     => true,
            'created_by' => Auth::id(),
            // policy_holder_id is null at registration;
            // it gets set automatically when the client's first
            // consultation is submitted (see RecommendationController)
        ]);

        return redirect()->route('clients.index')
            ->with('success', 'Client created successfully!');
    }

    public function edit($id)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        $client      = User::where('role', 'client')->findOrFail($id);

        if ($currentUser->isAgent() && $client->created_by !== Auth::id()) {
            abort(403);
        }

        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, $id)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        $client      = User::where('role', 'client')->findOrFail($id);

        if ($currentUser->isAgent() && $client->created_by !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $client->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $client->name  = $validated['name'];
        $client->email = $validated['email'];

        if (!empty($validated['password'])) {
            $client->password = Hash::make($validated['password']);
        }

        $client->save();

        return redirect()->route('clients.index')
            ->with('success', 'Client updated successfully!');
    }

    public function destroy($id)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        $client      = User::where('role', 'client')->findOrFail($id);

        if ($currentUser->isAgent() && $client->created_by !== Auth::id()) {
            abort(403);
        }

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client deleted successfully!');
    }

    // ── Client-facing pages ───────────────────────────────────────────────────

    /**
     * Show all consultations that belong to the logged-in client.
     *
     * How the lookup works:
     *   users.policy_holder_id  →  policy_holders.id
     *                                      ↕
     *                           cases.policy_holder_id
     *
     * We simply match cases.policy_holder_id to the user's own
     * policy_holder_id — no email matching needed.
     */
    public function myConsultations()
    {
        /** @var User $user */
        $user = Auth::user();

        // Client has never been the policy holder on any consultation yet
        if (!$user->policy_holder_id) {
            return view('client.consultations', [
                'consultations' => CaseModel::whereNull('id')->paginate(10), // empty paginator
                'hasHolder'     => false,
            ]);
        }

        $consultations = CaseModel::with(['customer', 'product', 'agent', 'policyHolder'])
            ->where('policy_holder_id', $user->policy_holder_id)
            ->latest()
            ->paginate(10);

        return view('client.consultations', [
            'consultations' => $consultations,
            'hasHolder'     => true,
        ]);
    }

    /**
     * Show one consultation — only if it belongs to the logged-in client.
     */
    public function showConsultation($id)
    {
        /** @var User $user */
        $user = Auth::user();

        $consultation = CaseModel::with([
            'customer',
            'product',
            'agent',
            'policyHolder',
        ])->findOrFail($id);

        // Guard: client must have a linked holder, and it must match the case
        if (!$user->policy_holder_id
            || (int) $consultation->policy_holder_id !== (int) $user->policy_holder_id) {
            abort(403, 'You do not have permission to view this consultation.');
        }

        return view('client.show', compact('consultation'));
    }
}
