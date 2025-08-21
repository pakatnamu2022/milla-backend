<?php

namespace App\Http\Services\gp\gestionhumana\personal;

use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\Person;
use Illuminate\Http\Request;

class WorkerService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Worker::class,
      $request,
      Worker::filters,
      Person::sorts,
      WorkerResource::class,
    );
  }
}
