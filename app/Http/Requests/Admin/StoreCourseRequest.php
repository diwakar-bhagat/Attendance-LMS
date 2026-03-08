<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     * Code should be unique within the specific institution.
     */
    public function rules(): array
    {
        $institutionId = auth()->user()->institution_id;

        return [
            // Ensure unique course code per institution, not globally
            'code' => ['required', 'string', 'max:25', 'unique:courses,code,NULL,id,institution_id,' . $institutionId],
            'title' => ['required', 'string', 'max:255'],
            'credits' => ['nullable', 'integer', 'min:0', 'max:15'],
        ];
    }
}