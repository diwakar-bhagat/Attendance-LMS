<?php

namespace Tests\Feature\Security;

use App\Domain\Identity\Models\User;
use App\Domain\Academic\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_faculty_cannot_mark_attendance_for_unassigned_section()
    {
        // 1 Setup Faculty A assigned to Section A
        $facultyA = User::factory()->create(['role_id' => 2]); // Assuming 2=faculty
        $sectionA = Section::factory()->create();
        $sectionA->assignedFaculty()->attach($facultyA->id);

        // 2 Setup Faculty B assigned to Section B
        $facultyB = User::factory()->create(['role_id' => 2]);
        $sectionB = Section::factory()->create();
        $sectionB->assignedFaculty()->attach($facultyB->id);

        // Faculty A tries to access Section B's grading page
        $response = $this->actingAs($facultyA)->post("/faculty/sections/{$sectionB->id}/meetings", [
            'meeting_date' => now()->format('Y-m-d'),
        ]);

        // Assert 403 Forbidden
        $response->assertStatus(403);

        // Faculty A accesses Section A's grading page
        $successResponse = $this->actingAs($facultyA)->post("/faculty/sections/{$sectionA->id}/meetings", [
            'meeting_date' => now()->format('Y-m-d'),
        ]);

        // Assert redirect (success)
        $successResponse->assertStatus(302);
    }
}