<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Academic\Models\Term;
use App\Http\Requests\Admin\StoreTermRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TermController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $terms = Term::latest()->get();
        return view('admin.terms.index', compact('terms'));
    }

    /**
     * Store a newly created term in database.
     */
    public function store(StoreTermRequest $request): RedirectResponse
    {
        // Global TenantScope automatically handles the institution_id assignment.
        // We only insert the validated data.
        Term::create($request->validated());

        return back()->with('success', 'Term created successfully.');
    }

    /**
     * Set a specific term as the active ongoing session.
     */
    public function markAsActive(Term $term): RedirectResponse
    {
        // TenantScope prevents an admin from modifying another institution's term blindly.
        // Because if the ID doesn't belong to them, Model Binding fails 404 implicitly.

        // Deactivate all others, activate this one (Simple transaction)
        \DB::transaction(function () use ($term) {
            Term::query()->update(['is_active' => false]);
            $term->update(['is_active' => true]);
        });

        return back()->with('success', "{$term->name} is now the active academic term.");
    }
}