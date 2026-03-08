<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Domain\Reporting\Services\AttendanceReportService;
use App\Domain\Identity\Models\User;
use App\Domain\Academic\Models\Section;
use App\Domain\Attendance\Models\ClassMeeting;
use App\Domain\Attendance\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_defaulters_calculates_correct_cutoff()
    {
        $service = new AttendanceReportService();
        $section = Section::factory()->create();

        // Needs at least 5 meetings for defaulter logic to trigger
        for ($i = 0; $i < 6; $i++) {
            ClassMeeting::factory()->create(['section_id' => $section->id]);
        }

        $meetings = $section->classMeetings;

        // Student A: 100% attendance (All 6 present)
        $studentA = User::factory()->create();
        $section->enrolledStudents()->attach($studentA->id);
        foreach ($meetings as $meeting) {
            AttendanceRecord::factory()->create([
                'class_meeting_id' => $meeting->id,
                'student_id' => $studentA->id,
                'status' => 'present'
            ]);
        }

        // Student B: 50% attendance (3 present, 3 absent)
        $studentB = User::factory()->create();
        $section->enrolledStudents()->attach($studentB->id);
        foreach ($meetings as $key => $meeting) {
            AttendanceRecord::factory()->create([
                'class_meeting_id' => $meeting->id,
                'student_id' => $studentB->id,
                'status' => $key < 3 ? 'present' : 'absent'
            ]);
        }

        // Fetch defaulters under 75%
        $defaulters = $service->getDefaulters($section->id, 75);

        // Assert 1 defaulter found
        $this->assertEquals(1, $defaulters->count());

        // Assert it is specifically Student B
        $this->assertEquals($studentB->id, $defaulters->first()->id);

        // Assert percentage output is precisely 50
        $this->assertEquals(50, $defaulters->first()->percentage);
    }
}