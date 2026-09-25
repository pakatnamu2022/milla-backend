<?php

namespace App\Http\Services\ap;

use App\Http\Resources\ap\ApMastersResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\ApMasters;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApMastersService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    // Los listados completos (all=true) son catálogos que casi no cambian y se piden miles de
    // veces por hora: se cachean y se invalidan al crear/editar/eliminar (ver ApMasters::booted)
    if ($request->query('all') === 'true') {
      $params = $request->query();
      ksort($params);
      $version = Cache::get(ApMasters::LIST_CACHE_VERSION_KEY, '0');
      $key = 'ap_masters:list:' . $version . ':' . md5(json_encode($params));

      $data = Cache::remember($key, now()->addHour(), fn() => $this->getFilteredList($request)->getData(true));
      return response()->json($data);
    }

    return $this->getFilteredList($request);
  }

  private function getFilteredList(Request $request)
  {
    return $this->getFilteredResults(
      ApMasters::class,
      $request,
      ApMasters::filters,
      ApMasters::sorts,
      ApMastersResource::class,
    );
  }

  public function find($id)
  {
    $ApCommercialMasters = ApMasters::where('id', $id)->first();
    if (!$ApCommercialMasters) {
      throw new Exception('Concepto de tabla maestra no encontrado');
    }
    return $ApCommercialMasters;
  }

  public function store(Mixed $data)
  {
    $ApCommercialMasters = ApMasters::create($data);

    return new ApMastersResource($ApCommercialMasters);
  }

  public function show($id)
  {
    return new ApMastersResource($this->find($id));
  }

  public function update(Mixed $data)
  {
    $ApCommercialMasters = $this->find($data['id']);
    
    $ApCommercialMasters->update($data);

    return new ApMastersResource($ApCommercialMasters);
  }

  public function destroy($id)
  {
    $ApCommercialMasters = $this->find($id);
    DB::transaction(function () use ($ApCommercialMasters) {
      $ApCommercialMasters->delete();
    });

    return response()->json(['message' => 'Concepto de tabla maestra eliminado correctamente']);
  }

  /**
   * Obtener todos los tipos registrados en Master Comercial
   * Cacheado por 24 horas (1440 minutos)
   */
  public function getTypes()
  {
    return Cache::remember('commercial_masters_types', 1440, function () {
      $types = ApMasters::select('type')
        ->distinct()
        ->whereNotNull('type')
        ->orderBy('type')
        ->pluck('type');

      return response()->json([
        'data' => $types,
        'count' => $types->count(),
        'cached_at' => now()->toDateTimeString(),
      ]);
    });
  }
}
