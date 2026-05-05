<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class StoreManyWorkerToCycleRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'worker_ids'   => 'required|array|min:1',
            'worker_ids.*' => 'required|integer|exists:rrhh_persona,id',
        ];
    }
}
