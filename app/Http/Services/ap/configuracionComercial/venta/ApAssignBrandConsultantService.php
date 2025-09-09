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
      ApAssignBrandConsultant::with(['worker', 'brand', 'sede']),
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
      'worker:id,nombre_completo',
      'sede:id,abreviatura',
      'brand:id,name'
    ])
      ->where('year', $data['year'])
      ->where('month', $data['month'])
      //->where('company_branch_id', $data['company_branch_id'])
      ->where('sede_id', $data['sede_id'])
      ->where('brand_id', $data['brand_id'])
      ->get();

    if ($rows->isEmpty()) {
      throw new Exception('No se encontraron asignaciones para los parámetros enviados.');
    }

    $first = $rows->first();

    return [
      'year' => (int)$data['year'],
      'month' => (int)$data['month'],
      //'company_branch' => $first->companyBranch->abbreviation,
      'sede' => $first->sede->abreviatura,
      'brand' => $first->brand->nombre,
      'workers' => $rows->map(function ($r) {
        return [
          'id' => $r->worker->id,
          'name' => $r->worker->nombre_completo,
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
      ->where('year', $data['year'])
      ->where('month', $data['month'])
      ->where('worker_id', $data['worker_id'])
      ->where('brand_id', $data['brand_id'])
      //->where('company_branch_id', $data['company_branch_id'])
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

    $exists = ApAssignBrandConsultant::where('year', $data['year'])
      ->where('month', $data['month'])
      ->where('worker_id', $data['worker_id'])
      ->where('brand_id', $data['brand_id'])
      //->where('company_branch_id', $data['company_branch_id'])
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
