<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\NewSedeResource;
use App\Http\Resources\gp\gestionsistema\SedeResource;
use App\Http\Resources\gp\tics\EquipmentResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\NewSede;
use App\Models\gp\gestionsistema\Sede;
use Exception;
use Illuminate\Http\Request;

class SedeService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Sede::where('status_deleted', 1)->whereNotNull('empresa_id')->orderBy('empresa_id', 'asc'),
      $request,
      Sede::filters,
      Sede::sorts,
      SedeResource::class,
    );
  }
}
