<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\ProvinceResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\Province;
use Illuminate\Http\Request;

class ProvinceService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Province::class,
      $request,
      Province::filters,
      Province::sorts,
      ProvinceResource::class,
    );
  }
}
