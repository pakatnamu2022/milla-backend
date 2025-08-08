<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class UpdateEvaluationPersonCycleDetailRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'goal' => 'required|numeric|min:0|max:100',
            'weight' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
