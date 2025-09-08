<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\DistrictResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\District;
use Illuminate\Http\Request;

class DistrictService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      District::class,
      $request,
      District::filters,
      District::sorts,
      DistrictResource::class,
    );
  }
}
