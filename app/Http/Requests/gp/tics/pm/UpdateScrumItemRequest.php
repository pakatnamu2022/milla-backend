<?php

namespace App\Http\Requests\gp\tics\pm;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScrumItemRequest extends FormRequest
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
            'sprint_id'       => 'nullable|integer|exists:scrum_sprints,id',
            'parent_id'       => 'nullable|integer|exists:scrum_items,id',
            'type'            => 'sometimes|in:tarea,historia,funcion,solicitud,error',
            'title'           => 'sometimes|string|max:200',
            'description'     => 'nullable|string',
            'status'          => 'sometimes|in:backlog,por_hacer,en_progreso,en_revision,hecho',
            'priority'        => 'sometimes|in:alta,media,baja',
            'assigned_to'     => 'nullable|integer|exists:users,id',
            'story_points'    => 'nullable|integer|min:0|max:100',
            'estimated_hours' => 'nullable|numeric|min:0|max:999',
            'actual_hours'    => 'nullable|numeric|min:0|max:999',
            'order'           => 'nullable|integer|min:0',
            'due_date'        => 'nullable|date',
            'tag_ids'         => 'nullable|array',
            'tag_ids.*'       => 'integer|exists:scrum_tags,id',
        ];
    }
}
