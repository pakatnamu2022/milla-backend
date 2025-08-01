<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class StoreHierarchicalCategoryDetailRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'positions' => 'required|array',
            'positions.*.id' => 'sometimes|integer|exists:gh_hierarchical_category_detail,id',
            'positions.*.position_id' => 'required|exists:rrhh_cargo,id',
        ];
    }
}
