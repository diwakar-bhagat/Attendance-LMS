<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Domain\Academic\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display a listing of courses the student is currently enrolled in
     * and calculate their real-time percentage (Fallback mode if Jobs aren't setup yet).
     */
    public function index(Request $request)
    {
        $student = $request->user();

        // Load enrolled sections and eager load the term (to ensure we fetch active ones)
        // and course (for the title)
        $sections = $student->enrolledSections()
            ->with(['term', 'course', 'classMeetings'])
            ->whereHas('term', function ($q) {
            // Focus on the present. E.g "Spring 2024"
            $q->where('is_active', true);
        })->get();

        // Calculate simple on-the-fly attendance
        // In a true massive scale SaaS, this is pushed to a Nightly Aggregate Job
        $attendanceData = [];

        foreach ($sections as $section) {
            $totalMeetings = $section->classMeetings->count();

            // Pluck the IDs to query attendance_records quickly
            $meetingIds = $section->classMeetings->pluck('id');

            // Find rows where student_id = me AND class_meeting_id in ($meetingIds) AND status = 'present' or 'late'
            $presentCount = DB::table('attendance_records')
                ->where('student_id', $student->id)
                ->whereIn('class_meeting_id', $meetingIds)
                ->whereIn('status', ['present', 'late', 'excused'])
                ->count();

            $percentage = $totalMeetings > 0 ? round(($presentCount / $totalMeetings) * 100) : 100;

            $attendanceData[$section->id] = [
                'total_meetings' => $totalMeetings,
                'attended' => $presentCount,
                'percentage' => $percentage,
                // Simple logical flagging
                'is_defaulter' => $percentage < 75 && $totalMeetings > 5
            ];
        }

        return view('student.dashboard', compact('sections', 'attendanceData'));
    }

    /**
     * Drill down view to see the exact days a student missed.
     */
    public function showSection(Section $section)
    {
        // Policy: Make sure the student is actually in the course they are clicking!
        $this->authorize('view', $section);

        $studentId = auth()->id();

        // Fetch all meetings with my specific record for that day
        $meetings = $section->classMeetings()
            ->with(['attendanceRecords' => function ($query) use ($studentId) {
            $query->where('student_id', $studentId);
        }])
            ->orderBy('meeting_date', 'desc')
            ->paginate(30);

        return view('student.section.show', compact('section', 'meetings'));
    }
}