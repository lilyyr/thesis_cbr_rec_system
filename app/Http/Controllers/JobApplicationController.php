<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class JobApplicationController extends Controller
{
    public function index()
    {
        return view('job-application.index');
    }

    public function adminIndex(Request $request)
    {
        $query = JobApplication::query();

        // If the user submitted a search term, filter the results
        if ($request->filled('search')) {
            $searchTerm = $request->search;

            $query->where(function ($q) use ($searchTerm) {
                $q->where('full_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%')
                    ->orWhere('phone', 'like', '%' . $searchTerm . '%');
            });
        }

        // Order by newest first and paginate the results (e.g., 10 per page)
        $applications = $query->latest()->paginate(10);

        // Keep the search term in the pagination links
        $applications->appends($request->all());

        return view('job-application.admin-index', compact('applications'));
    }
    public function adminShow($id)
    {
        $application = JobApplication::findOrFail($id);

        return view('job-application.show', compact('application'));
    }

    public function downloadResume($id)
    {
        $application = JobApplication::findOrFail($id);

        if (!$application->resume_path || !Storage::disk('local')->exists($application->resume_path)) {
            abort(404, 'Resume not found.');
        }

        return Storage::disk('local')->download(
            $application->resume_path,
            $application->resume_original_name
        );
    }

    // Update the status and admin notes
    public function adminUpdate(Request $request, $id)
    {
        $application = JobApplication::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,reviewed,accepted,rejected',
            'admin_notes' => 'nullable|string'
        ]);

        $application->update($validated);

        return redirect()->back()->with('success', 'Application updated successfully');
    }

    public function adminDelete(Request $request, $id)
    {
        $application = JobApplication::findOrFail($id);
        if ($application->resume_path) {
            // Storage::delete() won't crash if the file is already missing, 
            // but checking path existence is good practice.
            Storage::disk('local')->delete($application->resume_path);
        }

        $application->delete();
        return redirect()->back()->with('success', 'Application deleted successfully');
    }

    public function store(Request $request)
    {
        // 1. Validate the incoming request
        $validated = $request->validate([
            'full_name'            => 'required|string|max:255',
            'email'                => 'required|email|max:255',
            'phone'                => 'required|string|max:20',
            'date_of_birth'        => 'required|date',
            'address'              => 'required|string',
            'education_level'      => 'required|in:high_school,diploma,bachelors,masters',
            'institution'          => 'nullable|string|max:255',
            'work_experience'      => 'nullable|string',
            'insurance_experience' => 'nullable|string',
            'motivation'           => 'required|string',
            'resume'               => 'nullable|file|mimes:pdf|max:2048', // Matches your form restrictions
        ]);

        // 2. Handle the file upload
        if ($request->hasFile('resume')) {
            // Store the file in 'storage/app/public/resumes' and get the generated path
            $path = $request->file('resume')->store('resumes', 'local');

            // Save both the generated path and the original filename for easy searching
            $validated['resume_path'] = $path;
            $validated['resume_original_name'] = $request->file('resume')->getClientOriginalName();
        }

        // Unset the 'resume' key from the array so it doesn't clash with the database columns
        unset($validated['resume']);

        // 3. Save to the database
        JobApplication::create($validated);

        // 4. Redirect with the success message your blade template is looking for
        return back()->with('application_success', 'Thank you! Your application has been submitted successfully.');
    }
}
