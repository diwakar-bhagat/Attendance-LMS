<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceBatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 1. Must be the faculty who created the meeting, or an admin
        $meeting = $this->route('meeting');
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return true;
        }

        return current_user_can_edit_meeting($user, $meeting);
    // Example implementation below (Can be moved to MeetingPolicy)
    }

    protected function current_user_can_edit_meeting($user, $meeting)
    {
        return $user->hasRole('faculty') && $meeting->faculty_id === $user->id;
    }

    /**
     * Get the validation rules that apply to the request.
     * Prevents forging student IDs. Every ID transmitted MUST be officially enrolled in the meeting's section.
     */
    public function rules(): array
    {
        // Using the route binding for the current class_meeting
        $sectionId = $this->route('meeting')->section_id;

        return [
            // Expecting an array: ['attendance' => ['student_id' => 'status', 'another_id' => 'status']]
            'attendance' => ['required', 'array'],

            // The student ID must actually exist in the enrollments table FOR THIS SECTION
            // This is a SECURITY-CRITICAL validation. Without this, I could pass student_id = "Drop Tables" 
            // or pass a student logically impossible for me to grade.
            'attendance.*' => ['in:present,absent,excused,late'],

            // Validating the keys (student_ids) of the array
            'attendance_keys' => ['array'],
            'attendance_keys.*' => [
                'exists:users,id',
                function ($attribute, $value, $fail) use ($sectionId) {
            $isEnrolled = \DB::table('enrollments')
                    ->where('section_id', $sectionId)
                    ->where('student_id', $value)
                    ->exists();
            if (!$isEnrolled) {
                $fail("Student ID {$value} is not enrolled in this section.");
            }
        }
            ],
        ];
    }

    /**
     * Prepare data for validation (extract keys)
     */
    protected function prepareForValidation()
    {
        if ($this->has('attendance') && is_array($this->attendance)) {
            $this->merge([
                'attendance_keys' => array_keys($this->attendance)
            ]);
        }
    }
}