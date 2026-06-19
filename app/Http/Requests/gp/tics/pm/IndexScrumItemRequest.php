<?php

namespace App\Http\Requests\gp\tics\pm;

use Illuminate\Foundation\Http\FormRequest;

class IndexScrumItemRequest extends FormRequest
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
            'project_id'  => 'sometimes|integer|exists:scrum_projects,id',
            'sprint_id'   => 'nullable|integer|exists:scrum_sprints,id',
            'parent_id'   => 'nullable|integer|exists:scrum_items,id',
            'type'        => 'nullable|in:tarea,historia,funcion,solicitud,error',
            'status'      => 'nullable|in:backlog,por_hacer,en_progreso,en_revision,hecho',
            'priority'    => 'nullable|in:alta,media,baja',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'search'      => 'nullable|string|max:100',
            'per_page'    => 'nullable|integer|min:1|max:100',
        ];
    }
}
