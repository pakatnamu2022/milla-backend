<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ConceptObjectiveMasterPvResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\taller\ConceptObjectiveMasterPv;
use App\Models\ap\postventa\taller\TypePlanningConceptObjectiveMasterPv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ConceptObjectiveMasterPvService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ConceptObjectiveMasterPv::class,
      $request,
      ConceptObjectiveMasterPv::filters,
      ConceptObjectiveMasterPv::sorts,
      ConceptObjectiveMasterPvResource::class,
    );
  }

  public function find($id)
  {
    $conceptObjective = ConceptObjectiveMasterPv::with('typePlannings')->where('id', $id)->first();
    if (!$conceptObjective) {
      throw new Exception('Concepto objetivo no encontrado');
    }
    return $conceptObjective;
  }

  public function store(mixed $data)
  {
    try {
      DB::beginTransaction();

      $typePlanningIds = $data['type_planning_ids'] ?? [];
      unset($data['type_planning_ids']);

      $conceptObjective = ConceptObjectiveMasterPv::create($data);

      if (!empty($typePlanningIds)) {
        $conceptObjective->typePlannings()->sync($typePlanningIds);
      }

      DB::commit();
      return new ConceptObjectiveMasterPvResource($conceptObjective->load('typePlannings'));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show($id)
  {
    return new ConceptObjectiveMasterPvResource($this->find($id));
  }

  public function update(mixed $data)
  {
    try {
      DB::beginTransaction();

      $conceptObjective = $this->find($data['id']);

      $typePlanningIds = $data['type_planning_ids'] ?? [];
      unset($data['type_planning_ids']);

      $conceptObjective->update($data);

      $conceptObjective->typePlannings()->sync($typePlanningIds);

      DB::commit();
      return new ConceptObjectiveMasterPvResource($conceptObjective->load('typePlannings'));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy($id)
  {
    $conceptObjective = $this->find($id);
    DB::transaction(function () use ($conceptObjective) {
      $conceptObjective->typePlannings()->detach();
      $conceptObjective->delete();
    });
    return response()->json(['message' => 'Concepto objetivo eliminado correctamente.']);
  }

  /**
   * Update or create concept objective with type plannings
   */
  public function updateOrCreate(mixed $data)
  {
    try {
      DB::beginTransaction();

      $typePlanningIds = $data['type_planning_ids'] ?? [];
      $conceptId = $data['id'] ?? null;

      unset($data['type_planning_ids']);
      unset($data['id']);

      $conceptObjective = ConceptObjectiveMasterPv::updateOrCreate(
        ['id' => $conceptId],
        $data
      );

      $conceptObjective->typePlannings()->sync($typePlanningIds);

      DB::commit();
      return new ConceptObjectiveMasterPvResource($conceptObjective->load('typePlannings'));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
