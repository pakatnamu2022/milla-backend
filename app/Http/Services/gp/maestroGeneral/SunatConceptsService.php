<?php

namespace App\Http\Services\gp\maestroGeneral;

use App\Http\Resources\gp\maestroGeneral\SunatConceptsResource;
use App\Http\Services\BaseService;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Http\Request;

class SunatConceptsService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      SunatConcepts::class,
      $request,
      SunatConcepts::filters,
      SunatConcepts::sorts,
      SunatConceptsResource::class,
    );
  }
}
