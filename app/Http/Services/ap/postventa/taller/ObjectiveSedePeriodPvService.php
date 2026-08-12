<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ObjectiveSedePeriodPvResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\taller\ObjectiveSedePeriodPv;
use App\Models\ap\postventa\taller\ConceptObjectiveMasterPv;
use App\Models\ap\postventa\taller\ConceptObjectivePeriodPv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ObjectiveSedePeriodPvService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    $query = ObjectiveSedePeriodPv::query()
      ->with([
        'sede',
        'conceptObjectives.advisors.worker',
        'conceptObjectives.area',
        'conceptObjectives.typePlannings'
      ]);

    return $this->getFilteredResults(
      $query,
      $request,
      ObjectiveSedePeriodPv::filters,
      ObjectiveSedePeriodPv::sorts,
      ObjectiveSedePeriodPvResource::class
    );
  }

  public function find($id)
  {
    $objectiveSede = ObjectiveSedePeriodPv::with('sede')->where('id', $id)->first();
    if (!$objectiveSede) {
      throw new Exception('Objetivo sede período no encontrado');
    }
    return $objectiveSede;
  }

  public function store(mixed $data)
  {
    try {
      DB::beginTransaction();

      $data['amount'] = 0; // Inicializar el monto en 0
      $objectiveSede = ObjectiveSedePeriodPv::create($data);

      // Crear automáticamente los ConceptObjectivePeriodPv desde la tabla maestra
      $conceptsMaster = ConceptObjectiveMasterPv::with('typePlannings')->where('status', true)->get();

      foreach ($conceptsMaster as $conceptMaster) {
        $conceptPeriod = ConceptObjectivePeriodPv::create([
          'objective_sede_period_pv_id' => $objectiveSede->id,
          'area_id' => $conceptMaster->area_id,
          'description' => $conceptMaster->description,
          'is_vehicular_crossing' => $conceptMaster->is_vehicular_crossing,
          'status' => $conceptMaster->status,
          'sub_amount' => 0,
          'order' => $conceptMaster->order,
        ]);

        // Copiar las relaciones de type_plannings
        if ($conceptMaster->typePlannings->isNotEmpty()) {
          $typePlanningIds = $conceptMaster->typePlannings->pluck('id')->toArray();
          $conceptPeriod->typePlannings()->sync($typePlanningIds);
        }
      }

      DB::commit();
      return new ObjectiveSedePeriodPvResource($objectiveSede->load('sede'));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show($id)
  {
    return new ObjectiveSedePeriodPvResource($this->find($id));
  }

  public function update(mixed $data)
  {
    try {
      DB::beginTransaction();

      $objectiveSede = $this->find($data['id']);
      $objectiveSede->update($data);

      DB::commit();
      return new ObjectiveSedePeriodPvResource($objectiveSede->load('sede'));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy($id)
  {
    $objectiveSede = $this->find($id);
    DB::transaction(function () use ($objectiveSede) {
      // Delete related concept objectives and their relations
      foreach ($objectiveSede->conceptObjectives as $conceptObjective) {
        $conceptObjective->typePlannings()->detach();
        $conceptObjective->advisors()->delete();
        $conceptObjective->delete();
      }
      $objectiveSede->delete();
    });
    return response()->json(['message' => 'Objetivo sede período eliminado correctamente.']);
  }
}
