<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;

class StorePerDiemCategoryRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:gh_per_diem_category,name',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name of the per diem category is required.',
            'name.string' => 'The name must be a valid string.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'name.unique' => 'The name has already been taken.',
            'description.string' => 'The description must be a valid string.',
        ];
    }
}
