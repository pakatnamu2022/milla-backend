<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ConceptObjectivePeriodPvResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\taller\ConceptObjectivePeriodPv;
use App\Models\ap\postventa\taller\ObjectiveAdvisorsPeriodPv;
use App\Models\ap\postventa\taller\ObjectiveSedePeriodPv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ConceptObjectivePeriodPvService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ConceptObjectivePeriodPv::class,
      $request,
      ConceptObjectivePeriodPv::filters,
      ConceptObjectivePeriodPv::sorts,
      ConceptObjectivePeriodPvResource::class,
    );
  }

  public function find($id)
  {
    $conceptObjective = ConceptObjectivePeriodPv::with(['typePlannings', 'advisors.worker', 'objectiveSedePeriod.sede', 'area'])
      ->where('id', $id)
      ->first();
    if (!$conceptObjective) {
      throw new Exception('Concepto objetivo período no encontrado');
    }
    return $conceptObjective;
  }

  public function store(mixed $data)
  {
    try {
      DB::beginTransaction();

      $typePlanningIds = $data['type_planning_ids'] ?? [];
      $advisors = $data['advisors'] ?? [];

      unset($data['type_planning_ids']);
      unset($data['advisors']);

      $conceptObjective = ConceptObjectivePeriodPv::create($data);

      // Sync type plannings
      if (!empty($typePlanningIds)) {
        $conceptObjective->typePlannings()->sync($typePlanningIds);
      }

      // Create advisors
      if (!empty($advisors)) {
        foreach ($advisors as $advisor) {
          $conceptObjective->advisors()->create([
            'worker_id' => $advisor['worker_id'],
            'amount' => $advisor['amount'],
          ]);
        }
        // Update sub_amount from advisors sum
        $this->updateSubAmount($conceptObjective);
      }
      // If no advisors sent, sub_amount comes from $data and is already set

      // Update objective sede period amount
      $this->updateObjectiveSedePeriodAmount($conceptObjective->objective_sede_period_pv_id);

      DB::commit();
      return new ConceptObjectivePeriodPvResource(
        $conceptObjective->load(['typePlannings', 'advisors.worker', 'objectiveSedePeriod.sede', 'area'])
      );
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show($id)
  {
    return new ConceptObjectivePeriodPvResource($this->find($id));
  }

  public function update(mixed $data)
  {
    try {
      DB::beginTransaction();

      $conceptObjective = $this->find($data['id']);

      $typePlanningIds = $data['type_planning_ids'] ?? [];
      $advisors = $data['advisors'] ?? [];

      unset($data['type_planning_ids']);
      unset($data['advisors']);

      $conceptObjective->update($data);

      // Sync type plannings
      $conceptObjective->typePlannings()->sync($typePlanningIds);

      // Sync advisors
      if (!empty($advisors)) {
        $this->syncAdvisors($conceptObjective, $advisors);
        // Update sub_amount from advisors sum
        $this->updateSubAmount($conceptObjective);
      }
      // If no advisors sent, sub_amount comes from $data and is already updated

      // Update objective sede period amount
      $this->updateObjectiveSedePeriodAmount($conceptObjective->objective_sede_period_pv_id);

      DB::commit();
      return new ConceptObjectivePeriodPvResource(
        $conceptObjective->load(['typePlannings', 'advisors.worker', 'objectiveSedePeriod.sede', 'area'])
      );
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy($id)
  {
    $conceptObjective = $this->find($id);
    $objectiveSedePeriodId = $conceptObjective->objective_sede_period_pv_id;

    DB::transaction(function () use ($conceptObjective, $objectiveSedePeriodId) {
      $conceptObjective->typePlannings()->detach();
      $conceptObjective->advisors()->delete();
      $conceptObjective->delete();

      // Update objective sede period amount after deletion
      $this->updateObjectiveSedePeriodAmount($objectiveSedePeriodId);
    });

    return response()->json(['message' => 'Concepto objetivo período eliminado correctamente.']);
  }

  /**
   * Sync advisors for the concept objective
   */
  private function syncAdvisors(ConceptObjectivePeriodPv $conceptObjective, array $advisors)
  {
    $existingAdvisorIds = [];

    foreach ($advisors as $advisorData) {
      if (isset($advisorData['id'])) {
        // Update existing advisor
        $advisor = ObjectiveAdvisorsPeriodPv::find($advisorData['id']);
        if ($advisor && $advisor->concept_objective_period_pv_id == $conceptObjective->id) {
          $advisor->update([
            'worker_id' => $advisorData['worker_id'],
            'amount' => $advisorData['amount'],
          ]);
          $existingAdvisorIds[] = $advisor->id;
        }
      } else {
        // Create new advisor
        $newAdvisor = $conceptObjective->advisors()->create([
          'worker_id' => $advisorData['worker_id'],
          'amount' => $advisorData['amount'],
        ]);
        $existingAdvisorIds[] = $newAdvisor->id;
      }
    }

    // Delete advisors that are not in the request
    $conceptObjective->advisors()
      ->whereNotIn('id', $existingAdvisorIds)
      ->delete();
  }

  /**
   * Update sub_amount for the concept objective
   * Sum of all advisor amounts
   */
  private function updateSubAmount(ConceptObjectivePeriodPv $conceptObjective)
  {
    $subAmount = $conceptObjective->advisors()->sum('amount');
    $conceptObjective->update(['sub_amount' => $subAmount]);
  }

  /**
   * Update amount for the objective sede period
   * Sum of all sub_amounts from active concept objectives
   */
  private function updateObjectiveSedePeriodAmount($objectiveSedePeriodId)
  {
    $totalAmount = ConceptObjectivePeriodPv::where('objective_sede_period_pv_id', $objectiveSedePeriodId)
      ->where('status', true)
      ->sum('sub_amount');

    $objectiveSedePeriod = ObjectiveSedePeriodPv::find($objectiveSedePeriodId);
    if ($objectiveSedePeriod) {
      $objectiveSedePeriod->update(['amount' => $totalAmount]);
    }
  }
}
