<?php

namespace App\Http\Requests\gp\tics\pm;

use App\Models\gp\tics\pm\ScrumItem;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
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
            'predecessor_id'  => 'nullable|integer|exists:scrum_items,id',
            'type'            => 'required|in:tarea,historia,funcion,solicitud,error',
            'title'           => 'required|string|max:200',
            'description'     => 'nullable|string',
            'status'          => 'nullable|in:backlog,por_hacer,en_progreso,en_revision,hecho',
            'priority'        => 'nullable|in:alta,media,baja',
            'assigned_to'     => 'nullable|integer|exists:users,id',
            'story_points'    => 'nullable|integer|in:1,2,3,5,8,13,21',
            'estimated_hours' => 'nullable|numeric|min:0|max:999',
            'actual_hours'    => 'nullable|numeric|min:0|max:999',
            'start_date'      => 'nullable|date',
            'due_date'        => 'nullable|date|after_or_equal:start_date',
            'tag_ids'         => 'nullable|array',
            'tag_ids.*'       => 'integer|exists:scrum_tags,id',
        ];
    }

    /**
     * Misma regla que en UpdateScrumItemRequest: predecesora solo entre items
     * del mismo tipo (tarea con tarea, historia con historia) y, entre
     * tareas, de la misma historia padre (mismo parent_id).
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $predecessorId = $this->input('predecessor_id');
            if (!$predecessorId) {
                return;
            }

            $type = $this->input('type');
            $predecessor = ScrumItem::find($predecessorId);

            if ($type && $predecessor && $type !== $predecessor->type) {
                $validator->errors()->add(
                    'predecessor_id',
                    'La predecesora debe ser del mismo tipo de item (tarea con tarea, historia con historia).',
                );
                return;
            }

            if ($type === 'tarea' && $predecessor) {
                $parentId = $this->input('parent_id');
                if ($parentId != $predecessor->parent_id) {
                    $validator->errors()->add(
                        'predecessor_id',
                        'La predecesora debe ser una tarea de la misma historia.',
                    );
                }
            }
        });
    }
}
