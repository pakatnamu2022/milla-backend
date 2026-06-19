<?php

namespace App\Http\Requests\gp\tics\pm;

use Illuminate\Foundation\Http\FormRequest;

class StoreScrumTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id'  => 'required|integer|exists:scrum_projects,id',
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string',
            'priority'    => 'nullable|in:alta,media,baja',
        ];
    }
}
