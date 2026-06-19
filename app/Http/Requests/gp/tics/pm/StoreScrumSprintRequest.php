<?php

namespace App\Http\Requests\gp\tics\pm;

use Illuminate\Foundation\Http\FormRequest;

class StoreScrumSprintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => 'required|integer|exists:scrum_projects,id',
            'name'       => 'required|string|max:100',
            'goal'       => 'nullable|string|max:500',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'status'     => 'nullable|in:planeado,activo,cerrado',
        ];
    }
}
