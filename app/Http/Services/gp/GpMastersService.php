<?php

namespace App\Http\Services\gp;

use App\Http\Resources\gp\GpMastersResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\GpMasters;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GpMastersService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      GpMasters::class,
      $request,
      GpMasters::filters,
      GpMasters::sorts,
      GpMastersResource::class,
    );
  }

  public function find($id)
  {
    $GpMasters = GpMasters::where('id', $id)->first();
    if (!$GpMasters) {
      throw new Exception('Concepto de tabla maestra no encontrado');
    }
    return $GpMasters;
  }

  public function store(Mixed $data)
  {
    $GpMasters = GpMasters::create($data);

    // Limpiar el cache de tipos cuando se crea un registro
    Cache::forget('gp_masters_types');

    return new GpMastersResource($GpMasters);
  }

  public function show($id)
  {
    return new GpMastersResource($this->find($id));
  }

  public function update(Mixed $data)
  {
    $GpMasters = $this->find($data['id']);

    $GpMasters->update($data);

    // Limpiar el cache de tipos cuando se actualiza un registro
    Cache::forget('gp_masters_types');

    return new GpMastersResource($GpMasters);
  }

  public function destroy($id)
  {
    $GpMasters = $this->find($id);
    DB::transaction(function () use ($GpMasters) {
      $GpMasters->delete();
    });

    // Limpiar el cache de tipos cuando se elimina un registro
    Cache::forget('gp_masters_types');

    return response()->json(['message' => 'Concepto de tabla maestra eliminado correctamente']);
  }

  /**
   * Obtener todos los tipos registrados en Master General
   * Cacheado por 24 horas (1440 minutos)
   */
  public function getTypes()
  {
    return Cache::remember('gp_masters_types', 1440, function () {
      $types = GpMasters::select('type')
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