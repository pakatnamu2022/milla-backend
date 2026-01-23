<?php

namespace App\Http\Requests\gp\gestionhumana;

use App\Http\Requests\IndexRequest;

class IndexAccountantDistrictAssignmentRequest extends IndexRequest
{
    public function rules(): array
    {
        return [
            // Filters are handled automatically by the Filterable trait
        ];
    }
}
