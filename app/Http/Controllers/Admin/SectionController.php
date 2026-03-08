<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Academic\Models\Section;
use App\Domain\Academic\Models\Course;
use App\Domain\Academic\Models\Term;
use App\Http\Requests\Admin\StoreSectionRequest;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     * Loads the relationships to prevent N+1 rendering queries.
     */
    public function index()
    {
        $sections = Section::with(['course', 'term'])->latest()->paginate(25);
        $courses = Course::all();
        $terms = Term::all(); // Assuming admin wants to see all options to create new

        return view('admin.sections.index', compact('sections', 'courses', 'terms'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSectionRequest $request)
    {
        // Eloquent will auto-attach the required foreign keys implicitly if bounded, 
        // but here term_id and course_id are in the validated request.
        Section::create($request->validated());

        return back()->with('success', 'Section/Batch created successfully.');
    }

    /**
     * Show a specific section and its enrollments.
     */
    public function show(Section $section)
    {
        $section->load(['enrolledStudents', 'assignedFaculty']);

        // This is where Admins can add/remove students. The UI would contain a multi-select here.
        return view('admin.sections.show', compact('section'));
    }

    /**
     * Batch enroll students into a section via pivot table.
     */
    public function enroll(Request $request, Section $section)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id',
        ]);

        // .syncWithoutDetaching prevents accidental removal if an admin just adds 1 missing student
        $section->enrolledStudents()->syncWithoutDetaching($request->input('student_ids'));

        return back()->with('success', 'Students enrolled successfully.');
    }
}