<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApWorkOrderAssignOperatorResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\taller\ApWorkOrderAssignOperator;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApWorkOrderAssignOperatorService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApWorkOrderAssignOperator::class,
      $request,
      ApWorkOrderAssignOperator::filters,
      ApWorkOrderAssignOperator::sorts,
      ApWorkOrderAssignOperatorResource::class
    );
  }

  public function find($id)
  {
    $assignOperator = ApWorkOrderAssignOperator::with([
      'workOrder',
      'operator',
      'registeredBy'
    ])->where('id', $id)->first();

    if (!$assignOperator) {
      throw new Exception('Asignación de operador no encontrada');
    }

    return $assignOperator;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      // Validar que no exista una asignación activa para esta orden de trabajo
      $existingAssignment = ApWorkOrderAssignOperator::where('work_order_id', $data['work_order_id'])
        ->where('group_number', $data['group_number'])
        ->whereNull('deleted_at')
        ->first();

      if ($existingAssignment) {
        throw new Exception('Ya existe un operario asignado a este trabajo y grupo.');
      }

      // Set registered_by
      if (auth()->check()) {
        $data['registered_by'] = auth()->user()->id;
      }

      // Create assignment
      $assignOperator = ApWorkOrderAssignOperator::create($data);

      return new ApWorkOrderAssignOperatorResource($assignOperator->load([
        'workOrder',
        'operator',
        'registeredBy'
      ]));
    });
  }

  public function show($id)
  {
    return new ApWorkOrderAssignOperatorResource($this->find($id));
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $assignOperator = $this->find($data['id']);

      // Update assignment
      $assignOperator->update($data);

      // Reload relations
      $assignOperator->load([
        'workOrder',
        'operator',
        'registeredBy'
      ]);

      return new ApWorkOrderAssignOperatorResource($assignOperator);
    });
  }

  public function destroy($id)
  {
    $assignOperator = $this->find($id);

    DB::transaction(function () use ($assignOperator) {
      // Delete assignment
      $assignOperator->delete();
    });

    return response()->json(['message' => 'Asignación de operador eliminada correctamente']);
  }
}
