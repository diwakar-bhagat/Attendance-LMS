<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Academic\Models\Course;
use App\Http\Requests\Admin\StoreCourseRequest;

class CourseController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index()
    {
        $courses = Course::latest()->paginate(25);

        return view('admin.courses.index', compact('courses'));
    }

    /**
     * Store a newly created course.
     */
    public function store(StoreCourseRequest $request)
    {
        Course::create($request->validated());

        return back()->with('success', 'Course created successfully.');
    }

    /**
     * Soft Delete / Deactivate a course.
     */
    public function destroy(Course $course)
    {
        // Safe to soft delete, prevents losing historical transcript/attendance data
        // because of cascading hard deletes.
        $course->delete();

        return back()->with('success', 'Course deactivated successfully.');
    }
}