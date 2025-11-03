<?php

namespace App\Http\Requests\gp\maestroGeneral;

use App\Http\Requests\IndexRequest;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Validation\Rule;

class IndexSunatConceptsRequest extends IndexRequest
{
  public function rules(): array
  {
    $types = SunatConcepts::all()->pluck('type')->unique()->toArray();
    return [
      "search" => [
        'nullable',
        Rule::in($types)
      ],
      "status" => [
        'nullable',
        'in:0,1'
      ],
    ];
  }
}
