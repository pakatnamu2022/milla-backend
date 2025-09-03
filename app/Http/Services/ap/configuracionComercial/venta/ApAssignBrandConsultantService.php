<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApAssignBrandConsultantResource;
use App\Http\Services\BaseService;
use Illuminate\Http\Request;
use Exception;
use App\Models\ap\configuracionComercial\venta\ApAssignBrandConsultant;

class ApAssignBrandConsultantService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApAssignBrandConsultant::with(['asesor', 'marca', 'sede']),
      $request,
      ApAssignBrandConsultant::filters,
      ApAssignBrandConsultant::sorts,
      ApAssignBrandConsultantResource::class
    );
  }

  public function showGrouped(Request $request)
  {
    $data = $request->all();
    $rows = ApAssignBrandConsultant::with([
      'asesor:id,nombre_completo',
      'sede:id,abreviatura',
      'marca:id,nombre'
    ])
      ->where('anio', $data['anio'])
      ->where('month', $data['month'])
      ->where('sede_id', $data['sede_id'])
      ->where('marca_id', $data['marca_id'])
      ->get();

    if ($rows->isEmpty()) {
      throw new Exception('No se encontraron asignaciones para los parámetros enviados.');
    }

    $first = $rows->first();

    return [
      'anio' => (int)$data['anio'],
      'month' => (int)$data['month'],
      'sede' => $first->sede->abreviatura,
      'marca' => $first->marca->nombre,
      'asesores' => $rows->map(function ($r) {
        return [
          'id' => $r->asesor->id,
          'name' => $r->asesor->nombre_completo,
          'objetivo' => (int)$r->objetivo_venta,
        ];
      })->values()->toArray(),
    ];
  }

  public function store(array $data)
  {
    ApAssignBrandConsultant::where('anio', $data['anio'])
      ->where('month', $data['month'])
      ->where('sede_id', $data['sede_id'])
      ->where('marca_id', $data['marca_id'])
      ->delete();

    $insertData = [];
    foreach ($data['asesores'] as $asesor) {
      $insertData[] = [
        'anio' => $data['anio'],
        'month' => $data['month'],
        'sede_id' => $data['sede_id'],
        'marca_id' => $data['marca_id'],
        'asesor_id' => $asesor['asesor_id'],
        'objetivo_venta' => $asesor['objetivo'],
        'status' => true,
        'created_at' => now(),
        'updated_at' => now(),
      ];
    }

    if (!empty($insertData)) {
      ApAssignBrandConsultant::insert($insertData);
    }

    return ApAssignBrandConsultant::with(['asesor', 'marca', 'sede'])
      ->where('anio', $data['anio'])
      ->where('month', $data['month'])
      ->where('sede_id', $data['sede_id'])
      ->where('marca_id', $data['marca_id'])
      ->get();
  }

  public function update(array $data)
  {
    $existing = ApAssignBrandConsultant::withTrashed() // 👈 incluye soft–deleted
    ->where('anio', $data['anio'])
      ->where('month', $data['month'])
      ->where('sede_id', $data['sede_id'])
      ->where('marca_id', $data['marca_id'])
      ->get()
      ->keyBy('asesor_id');

    $newAsesores = collect($data['asesores'])->keyBy('asesor_id');

    foreach ($newAsesores as $asesorId => $asesorData) {
      if ($existing->has($asesorId)) {
        $record = $existing[$asesorId];
        $record->objetivo_venta = $asesorData['objetivo'];
        $record->deleted_at = null; // 👈 reactivar
        $record->save();
      } else {
        ApAssignBrandConsultant::create([
          'anio' => $data['anio'],
          'month' => $data['month'],
          'sede_id' => $data['sede_id'],
          'marca_id' => $data['marca_id'],
          'asesor_id' => $asesorId,
          'objetivo_venta' => $asesorData['objetivo'],
          'status' => true,
        ]);
      }
    }

    // Eliminar asesores que ya no vienen
    $toDelete = $existing->keys()->diff($newAsesores->keys());
    if ($toDelete->isNotEmpty()) {
      ApAssignBrandConsultant::where('anio', $data['anio'])
        ->where('month', $data['month'])
        ->where('sede_id', $data['sede_id'])
        ->where('marca_id', $data['marca_id'])
        ->whereIn('asesor_id', $toDelete)
        ->delete(); // 👈 soft delete
    }

    return ApAssignBrandConsultant::with(['asesor', 'marca', 'sede'])
      ->where('anio', $data['anio'])
      ->where('month', $data['month'])
      ->where('sede_id', $data['sede_id'])
      ->where('marca_id', $data['marca_id'])
      ->get();
  }

}
