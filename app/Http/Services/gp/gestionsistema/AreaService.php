<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\AreaResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\Area;
use Illuminate\Http\Request;

class AreaService extends BaseService
{
  protected $model;
  protected $resource;

  public function __construct()
  {
    $this->model = Area::class;
    $this->resource = AreaResource::class;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      $this->model,
      $request,
      $this->model::filters,
      $this->model::sorts,
      $this->resource,
    );
  }

}
