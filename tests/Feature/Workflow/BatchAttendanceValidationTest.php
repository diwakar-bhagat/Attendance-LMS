<?php

namespace Tests\Feature\Workflow;

use App\Domain\Identity\Models\User;
use App\Domain\Academic\Models\Section;
use App\Domain\Attendance\Models\ClassMeeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchAttendanceValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_service_blocks_unenrolled_student_ids()
    {
        // 1 Faculty, 1 Section
        $faculty = User::factory()->create(['role_id' => 2]); // Faculty
        $section = Section::factory()->create();
        $section->assignedFaculty()->attach($faculty->id);

        // 2 real enrolled students
        $studentA = User::factory()->create(['role_id' => 3]); // Student
        $studentB = User::factory()->create(['role_id' => 3]);
        $section->enrolledStudents()->attach([$studentA->id, $studentB->id]);

        // 1 fake student NOT enrolled in this section
        $imposterStudent = User::factory()->create(['role_id' => 3]);

        // Create a meeting
        $meeting = ClassMeeting::factory()->create([
            'section_id' => $section->id,
            'faculty_id' => $faculty->id,
        ]);

        // Malicious bulk array: Includes a student who is NOT in the class
        $payload = [
            'attendance' => [
                $studentA->id => 'present',
                $studentB->id => 'absent',
                $imposterStudent->id => 'present' // Should trigger the custom IN rule
            ]
        ];

        // Faculty member attempts to push the malicious payload
        $response = $this->actingAs($faculty)
            ->post("/faculty/meetings/{$meeting->id}/attendance", $payload);

        // Assert 422 Unprocessable Entity - Valdiation Failed
        $response->assertSessionHasErrors(['attendance_keys.2']);

        // Assert nothing saved to DB
        $this->assertDatabaseMissing('attendance_records', [
            'class_meeting_id' => $meeting->id
        ]);
    }
}