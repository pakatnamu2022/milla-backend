<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class ClosePerDiemPolicyRequest extends StoreRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'end_date' => ['required', 'date', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.required' => 'La fecha de fin es requerida.',
            'end_date.date' => 'La fecha de fin debe ser una fecha válida.',
            'end_date.date_format' => 'La fecha de fin debe tener el formato AAAA-MM-DD.',
        ];
    }

    public function attributes(): array
    {
        return [
            'end_date' => 'Fecha de fin',
        ];
    }
}
