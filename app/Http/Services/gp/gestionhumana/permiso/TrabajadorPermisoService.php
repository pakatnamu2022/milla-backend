<?php

namespace App\Http\Services\gp\gestionhumana\permiso;

use App\Http\Resources\gp\gestionhumana\permiso\TrabajadorPermisoResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\permiso\TrabajadorPermiso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrabajadorPermisoService extends BaseService
{
  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      TrabajadorPermiso::query()->with('empleado')->where('status_deleted', 1),
      $request,
      TrabajadorPermiso::filters,
      TrabajadorPermiso::sorts,
      TrabajadorPermisoResource::class,
    );
  }

  public function show(int $id): TrabajadorPermisoResource
  {
    $record = TrabajadorPermiso::with('empleado')->findOrFail($id);

    return new TrabajadorPermisoResource($record);
  }

  public function store(Request $request): TrabajadorPermisoResource
  {
    $data = $request->validated();
    $data['write_id'] = auth()->id();

    $record = TrabajadorPermiso::create($data);
    $record->load('empleado');

    return new TrabajadorPermisoResource($record);
  }

  public function update(Request $request, int $id): TrabajadorPermisoResource
  {
    $record = TrabajadorPermiso::findOrFail($id);
    $data   = $request->validated();
    $data['write_id'] = auth()->id();

    $record->update($data);
    $record->load('empleado');

    return new TrabajadorPermisoResource($record);
  }

  public function destroy(int $id): array
  {
    TrabajadorPermiso::findOrFail($id)->update(['status_deleted' => 0]);

    return ['message' => 'Permiso eliminado correctamente.'];
  }
}
