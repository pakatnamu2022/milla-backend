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
    if (
      isset($data['type']) &&
      $data['type'] === 'TIPO_DOCUMENTO' &&
      (!isset($data['code']) || !is_numeric($data['code']))
    ) {
      throw new Exception('El campo num. digitos debe tener formato de número entero.');
    }

    $ApCommercialMasters = ApMasters::create($data);

    // Limpiar el cache de tipos cuando se crea un registro
    Cache::forget('commercial_masters_types');

    return new ApMastersResource($ApCommercialMasters);
  }

  public function show($id)
  {
    return new ApMastersResource($this->find($id));
  }

  public function update(Mixed $data)
  {
    $ApCommercialMasters = $this->find($data['id']);
    if (
      isset($data['type']) &&
      $data['type'] === 'TIPO_DOCUMENTO' &&
      (!isset($data['code']) || !is_numeric($data['code']))
    ) {
      throw new Exception('El campo num. digitos debe tener formato de número entero.');
    }
    $ApCommercialMasters->update($data);

    // Limpiar el cache de tipos cuando se actualiza un registro
    Cache::forget('commercial_masters_types');

    return new ApMastersResource($ApCommercialMasters);
  }

  public function destroy($id)
  {
    $ApCommercialMasters = $this->find($id);
    DB::transaction(function () use ($ApCommercialMasters) {
      $ApCommercialMasters->delete();
    });

    // Limpiar el cache de tipos cuando se elimina un registro
    Cache::forget('commercial_masters_types');

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
