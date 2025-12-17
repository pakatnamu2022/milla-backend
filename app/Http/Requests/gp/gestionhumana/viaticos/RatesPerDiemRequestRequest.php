<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class RatesPerDiemRequestRequest extends StoreRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'district_id' => ['required', 'integer', 'exists:gs_district,id'],
            'category_id' => ['required', 'integer', 'exists:gh_per_diem_category,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'district_id.required' => 'El distrito es requerido.',
            'district_id.integer' => 'El distrito debe ser un número entero.',
            'district_id.exists' => 'El distrito seleccionado no existe.',
            'category_id.required' => 'La categoría es requerida.',
            'category_id.integer' => 'La categoría debe ser un número entero.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'district_id' => 'Distrito',
            'category_id' => 'Categoría',
        ];
    }
}
