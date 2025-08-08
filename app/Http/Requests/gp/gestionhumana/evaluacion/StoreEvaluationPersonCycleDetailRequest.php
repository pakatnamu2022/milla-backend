<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class StoreEvaluationPersonCycleDetailRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'cycle_id' => 'required|integer|exists_soft:gh_evaluation_cycle,id',
            'category_id' => 'required|integer|exists_soft:gh_hierarchical_category,id',
        ];
    }
}
