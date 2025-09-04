<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApAssignBrandConsultantResource;
use App\Http\Services\BaseService;
use Illuminate\Http\Request;
use Exception;
use App\Models\ap\configuracionComercial\venta\ApAssignBrandConsultant;
use Illuminate\Support\Facades\DB;

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

  public function find($id)
  {
    $ApAssignBrandConsultantMasters = ApAssignBrandConsultant::where('id', $id)->first();
    if (!$ApAssignBrandConsultantMasters) {
      throw new Exception('Asignación de marca no encontrado');
    }
    return $ApAssignBrandConsultantMasters;
  }

  public function store(array $data)
  {
    $existing = ApAssignBrandConsultant::withTrashed()
      ->where('anio', $data['anio'])
      ->where('month', $data['month'])
      ->where('asesor_id', $data['asesor_id'])
      ->where('marca_id', $data['marca_id'])
      ->where('sede_id', $data['sede_id'])
      ->first();

    if ($existing) {
      if ($existing->trashed()) {
        $existing->restore();
        $existing->update($data);
      } else {
        throw new Exception('Ya existe una asignación activa para el asesor, marca y sede en el período indicado.');
      }
      $ApAssignBrandConsultantMasters = $existing;
    } else {
      $ApAssignBrandConsultantMasters = ApAssignBrandConsultant::create($data);
    }

    return new ApAssignBrandConsultantResource($ApAssignBrandConsultantMasters);
  }

  public function update($data)
  {
    $ApAssignBrandConsultant = $this->find($data['id']);

    if (count($data) === 2) {
      $ApAssignBrandConsultant->update($data);
      return new ApAssignBrandConsultantResource($ApAssignBrandConsultant);
    }

    $exists = ApAssignBrandConsultant::where('anio', $data['anio'])
      ->where('month', $data['month'])
      ->where('asesor_id', $data['asesor_id'])
      ->where('marca_id', $data['marca_id'])
      ->where('sede_id', $data['sede_id'])
      ->where('id', '!=', $data['id'])
      ->exists();
    if ($exists) {
      throw new Exception('Ya existe una asignación para el asesor, marca y sede en el período indicado.');
    }
    $ApAssignBrandConsultant->update($data);
    return new ApAssignBrandConsultantResource($ApAssignBrandConsultant);
  }

  public function destroy($id)
  {
    $ApAssignBrandConsultant = $this->find($id);
    DB::transaction(function () use ($ApAssignBrandConsultant) {
      $ApAssignBrandConsultant->delete();
    });
    return response()->json(['message' => 'Asignación de asistente eliminado correctamente']);
  }
}
