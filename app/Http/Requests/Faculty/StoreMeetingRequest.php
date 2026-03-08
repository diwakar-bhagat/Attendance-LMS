<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeetingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Must be faculty creating a meeting for a section they teach.
        return auth()->user()->hasRole('faculty')
            && auth()->user()->assignedSections()->where('section_id', $this->route('section')->id)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     * Prevents meetings in the future, or completely broken dates.
     */
    public function rules(): array
    {
        return [
            'meeting_date' => ['required', 'date', 'before_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
        ];
    }
}