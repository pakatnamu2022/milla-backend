<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdatePerDiemCategoryRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'nullable',
                'string',
                'max:255',
              Rule::unique('gh_per_diem_category', 'name')
                ->whereNull('deleted_at')
                ->ignore($this->route('perDiemCategory')),
            ],
            'description' => 'nullable|string',
            'active' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'name.unique' => 'The name has already been taken.',
            'description.string' => 'The description must be a string.',
            'active.required' => 'The active field is required.',
            'active.boolean' => 'The active field must be true or false.',
        ];
    }
}
