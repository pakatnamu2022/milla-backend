<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreEvaluationMetricRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('gh_metrica_objetivos', 'nombre')->where('status_deleted', 0),
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
