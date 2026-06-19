<?php

namespace App\Http\Requests\gp\tics\pm;

use Illuminate\Foundation\Http\FormRequest;

class IndexScrumProjectRequest extends FormRequest
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
            'status'   => 'nullable|in:activo,archivado',
            'search'   => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort'     => 'nullable|string',
            'order'    => 'nullable|in:asc,desc',
        ];
    }
}
