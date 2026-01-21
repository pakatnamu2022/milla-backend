<?php

namespace App\Http\Requests\gp\gestionhumana;

use App\Http\Requests\StoreRequest;

class StoreAccountantDistrictAssignmentRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'worker_id' => ['required', 'integer', 'exists:rrhh_persona,id'],
            'district_id' => ['required', 'integer', 'exists:district,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'worker_id.required' => 'El trabajador es requerido.',
            'worker_id.integer' => 'El ID del trabajador debe ser un número entero.',
            'worker_id.exists' => 'El trabajador seleccionado no existe.',
            'district_id.required' => 'El distrito es requerido.',
            'district_id.integer' => 'El ID del distrito debe ser un número entero.',
            'district_id.exists' => 'El distrito seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'worker_id' => 'Trabajador',
            'district_id' => 'Distrito',
        ];
    }
}
