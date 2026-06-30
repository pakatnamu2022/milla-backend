<?php

namespace App\Http\Services\gp\gestionhumana\ausentismo;

use App\Http\Resources\gp\gestionhumana\ausentismo\AusentismoLaboralResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\ausentismo\AusentismoLaboral;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AusentismoLaboralService extends BaseService
{
  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      AusentismoLaboral::query()->with(['empleado', 'tipoDescanso'])->where('status_deleted', 1),
      $request,
      AusentismoLaboral::filters,
      AusentismoLaboral::sorts,
      AusentismoLaboralResource::class,
    );
  }

  public function show(int $id): JsonResponse
  {
    $record = AusentismoLaboral::with(['empleado', 'tipoDescanso'])->findOrFail($id);

    return response()->json(new AusentismoLaboralResource($record));
  }

  public function store(Request $request): JsonResponse
  {
    $data = $request->validated();
    $data['mes']  = strtoupper(Carbon::parse($data['fecha_inicial'])->locale('es')->monthName);
    $data['anio'] = (int) Carbon::parse($data['fecha_inicial'])->year;

    $record = AusentismoLaboral::create($data);
    $record->load(['empleado', 'tipoDescanso']);

    return response()->json(new AusentismoLaboralResource($record), 201);
  }

  public function update(Request $request, int $id): JsonResponse
  {
    $record = AusentismoLaboral::findOrFail($id);
    $data   = $request->validated();

    if (isset($data['fecha_inicial'])) {
      $data['mes']  = strtoupper(Carbon::parse($data['fecha_inicial'])->locale('es')->monthName);
      $data['anio'] = (int) Carbon::parse($data['fecha_inicial'])->year;
    }

    $record->update($data);
    $record->load(['empleado', 'tipoDescanso']);

    return response()->json(new AusentismoLaboralResource($record));
  }

  public function destroy(int $id): JsonResponse
  {
    $record = AusentismoLaboral::findOrFail($id);
    $record->update(['status_deleted' => 0]);

    return response()->json(['message' => 'Registro eliminado correctamente.']);
  }
}