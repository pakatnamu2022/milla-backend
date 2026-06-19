<?php

namespace App\Http\Requests\gp\tics\pm;

use Illuminate\Foundation\Http\FormRequest;

class StoreScrumItemRequest extends FormRequest
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
            'project_id'      => 'required|integer|exists:scrum_projects,id',
            'sprint_id'       => 'nullable|integer|exists:scrum_sprints,id',
            'parent_id'       => 'nullable|integer|exists:scrum_items,id',
            'type'            => 'required|in:tarea,historia,funcion,solicitud,error',
            'title'           => 'required|string|max:200',
            'description'     => 'nullable|string',
            'status'          => 'nullable|in:backlog,por_hacer,en_progreso,en_revision,hecho',
            'priority'        => 'nullable|in:alta,media,baja',
            'assigned_to'     => 'nullable|integer|exists:users,id',
            'story_points'    => 'nullable|integer|min:0|max:100',
            'estimated_hours' => 'nullable|numeric|min:0|max:999',
            'actual_hours'    => 'nullable|numeric|min:0|max:999',
            'due_date'        => 'nullable|date',
            'tag_ids'         => 'nullable|array',
            'tag_ids.*'       => 'integer|exists:scrum_tags,id',
        ];
    }
}
