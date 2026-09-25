<?php

namespace App\Http\Requests\gp\tics\pm;

use App\Models\gp\tics\pm\ScrumItem;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
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
            'predecessor_id'  => 'nullable|integer|exists:scrum_items,id',
            'type'            => 'sometimes|in:tarea,historia,funcion,solicitud,error',
            'title'           => 'sometimes|string|max:200',
            'description'     => 'nullable|string',
            'status'          => 'sometimes|in:backlog,por_hacer,en_progreso,en_revision,hecho',
            'priority'        => 'sometimes|in:alta,media,baja',
            'assigned_to'     => 'nullable|integer|exists:users,id',
            'story_points'    => 'nullable|integer|in:1,2,3,5,8,13,21',
            'estimated_hours' => 'nullable|numeric|min:0|max:999',
            'actual_hours'    => 'nullable|numeric|min:0|max:999',
            'order'           => 'nullable|integer|min:0',
            'start_date'      => 'nullable|date',
            'due_date'        => 'nullable|date|after_or_equal:start_date',
            'tag_ids'         => 'nullable|array',
            'tag_ids.*'       => 'integer|exists:scrum_tags,id',
        ];
    }

    /**
     * La predecesora solo tiene sentido entre items del mismo tipo (tarea con
     * tarea, historia con historia): mezclar niveles no encaja con cómo se
     * calculan las cadenas de fechas ni con las flechas del Gantt. Además,
     * entre tareas, deben ser del mismo dominio (misma historia padre): una
     * tarea de otra historia no es una dependencia real de esta.
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $predecessorId = $this->input('predecessor_id');
            if (!$predecessorId) {
                return;
            }

            $itemId = (int) $this->route('id');
            if ($predecessorId == $itemId) {
                $validator->errors()->add('predecessor_id', 'Un item no puede ser su propia predecesora.');
                return;
            }

            $item = ScrumItem::find($itemId);
            $type = $this->input('type') ?? $item?->type;
            $predecessor = ScrumItem::find($predecessorId);

            if ($type && $predecessor && $type !== $predecessor->type) {
                $validator->errors()->add(
                    'predecessor_id',
                    'La predecesora debe ser del mismo tipo de item (tarea con tarea, historia con historia).',
                );
                return;
            }

            if ($type === 'tarea' && $predecessor) {
                $parentId = $this->input('parent_id') ?? $item?->parent_id;
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
