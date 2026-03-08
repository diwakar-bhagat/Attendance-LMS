<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Domain\Academic\Models\Section;
use App\Domain\Attendance\Models\ClassMeeting;
use App\Http\Requests\Faculty\StoreMeetingRequest;
use App\Http\Requests\Faculty\StoreAttendanceBatchRequest;
use App\Domain\Attendance\Services\AttendanceRecordingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MeetingController extends Controller
{
    /**
     * Display a listing of sections assigned to the faculty to select from.
     */
    public function dashboard()
    {
        $faculty = Auth::user();

        // Faculty ONLY see what belongs to them this term.
        $assignedSections = $faculty->assignedSections()
            ->with(['term', 'course'])
            ->whereHas('term', function ($q) {
            // Ensure the term is the active one! Faculty shouldn't easily see 3 years ago unless intended
            $q->where('is_active', true);
        })
            ->get();

        return view('faculty.dashboard', compact('assignedSections'));
    }

    /**
     * Display the roster for a specific section and a form to Create a meeting.
     */
    public function showSection(Section $section)
    {
        $this->authorize('view', $section);

        $section->load(['enrolledStudents', 'classMeetings' => function ($q) {
            $q->orderBy('meeting_date', 'desc')->limit(10);
        }]);

        return view('faculty.section.show', compact('section'));
    }

    /**
     * Create a new Meeting for the section.
     */
    public function storeMeeting(StoreMeetingRequest $request, Section $section)
    {
        $this->authorize('manageAttendance', $section);

        $meeting = $section->classMeetings()->create([
            'faculty_id' => auth()->id(),
            'meeting_date' => $request->meeting_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return redirect()->route('faculty.meetings.attendance.edit', $meeting)
            ->with('success', 'Meeting created. Please mark the roster.');
    }

    /**
     * Display the form to mark attendance for a specific meeting instance.
     */
    public function editAttendance(ClassMeeting $meeting)
    {
        $this->authorize('manageAttendance', $meeting->section);

        // Load the students enrolled in the section, and any existing attendance for this specific meeting
        $section = $meeting->section()->with(['enrolledStudents'])->first();

        $attendanceRecords = $meeting->attendanceRecords()
            ->get()
            ->keyBy('student_id');

        return view('faculty.attendance.edit', compact('meeting', 'section', 'attendanceRecords'));
    }

    /**
     * Handle the POST request containing the array of attendance dropdowns/checkboxes.
     */
    public function updateAttendance(StoreAttendanceBatchRequest $request, ClassMeeting $meeting, AttendanceRecordingService $service)
    {
        $this->authorize('manageAttendance', $meeting->section);

        try {
            // The service handles DB transactions and upserts implicitly.
            $service->recordBatch($meeting, $request->input('attendance', []));

        }
        catch (\Exception $e) {
            // Log this securely
            \Log::error("Bulk Upsert Failed", ['meeting' => $meeting->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'A database error occurred saving the batch. Please try again.');
        }

        return redirect()->route('faculty.sections.show', $meeting->section_id)
            ->with('success', 'Attendance marked successfully for ' . $meeting->meeting_date->format('M d, Y'));
    }
}