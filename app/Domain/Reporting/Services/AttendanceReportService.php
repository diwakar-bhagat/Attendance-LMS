<?php

namespace App\Domain\Reporting\Services;

use App\Domain\Academic\Models\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AttendanceReportService
{
    /**
     * Calculate the attendance percentages for all students in a given section.
     * This query is optimized to use the indexed attendance_records table.
     * 
     * @param int $sectionId
     * @return Collection
     */
    public function generateSectionRosterReport(int $sectionId): Collection
    {
        // 1. Get total number of meetings for this section
        $totalMeetings = DB::table('class_meetings')
            ->where('section_id', $sectionId)
            ->count();

        // If no meetings have happened, everyone is at 100% technically
        if ($totalMeetings === 0) {
            $students = DB::table('enrollments')
                ->join('users', 'enrollments.student_id', '=', 'users.id')
                ->where('enrollments.section_id', $sectionId)
                ->select('users.id', 'users.name', 'users.email')
                ->get()
                ->map(function ($student) {
                $student->total_meetings = 0;
                $student->attended = 0;
                $student->percentage = 100;
                return $student;
            });

            return $students;
        }

        // 2. Fetch the aggregate count of present/excused/late days for all enrolled students
        // Using raw queries for maximum performance on large datasets
        $aggregates = DB::table('users')
            ->join('enrollments', 'users.id', '=', 'enrollments.student_id')
            ->leftJoin('attendance_records', function ($join) use ($sectionId) {
            $join->on('users.id', '=', 'attendance_records.student_id')
                ->whereIn('attendance_records.status', ['present', 'excused', 'late'])
                ->whereIn('attendance_records.class_meeting_id', function ($query) use ($sectionId) {
                $query->select('id')
                    ->from('class_meetings')
                    ->where('section_id', $sectionId);
            }
            );
        })
            ->where('enrollments.section_id', $sectionId)
            ->select(
            'users.id',
            'users.name',
            'users.email',
            DB::raw('COUNT(attendance_records.id) as attended_count')
        )
            ->groupBy('users.id', 'users.name', 'users.email')
            ->get();

        // 3. Map the raw counts into percentages
        return $aggregates->map(function ($row) use ($totalMeetings) {
            $percentage = round(($row->attended_count / $totalMeetings) * 100);

            $row->total_meetings = $totalMeetings;
            $row->percentage = $percentage;

            return $row;
        });
    }

    /**
     * Get a list of students who fall below a specific cutoff percentage.
     */
    public function getDefaulters(int $sectionId, int $cutoffPercent = 75): Collection
    {
        $roster = $this->generateSectionRosterReport($sectionId);

        // Filter the collection to only those severely lacking attendance
        return $roster->filter(function ($student) use ($cutoffPercent) {
            // Give them a grace period of at least 5 meetings before warning them
            return $student->total_meetings >= 5 && $student->percentage < $cutoffPercent;
        });
    }
}