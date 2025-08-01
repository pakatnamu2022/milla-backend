<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class StoreEvaluationObjectiveRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'metric_id' => 'required|exists:gh_evaluation_metric,id',
        ];
    }
}
