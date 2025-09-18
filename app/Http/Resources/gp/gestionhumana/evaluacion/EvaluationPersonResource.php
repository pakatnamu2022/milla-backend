<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationPersonResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'person' => $this->person->nombre_completo,
      'personCycleDetail' => new EvaluationPersonCycleDetailResource($this->personCycleDetail),
      'evaluation' => $this->evaluation->name,
      'result' => round($this->result, 2),
      'compliance' => round($this->compliance, 2),
      'qualification' => round($this->qualification, 2),
      'comment' => $this->comment,
      'wasEvaluated' => $this->wasEvaluated,
      'index_range_result' => $this->person->position->hierarchicalCategory->hasObjectives ? $this->calculateIndexRangeResult($this->qualification, $this->evaluation->objectiveParameter) : $this->evaluation->objectiveParameter->details->count() - 1,
      'label_range' => $this->person->position->hierarchicalCategory->hasObjectives ? $this->calculateLabelRangeResult($this->qualification, $this->evaluation->objectiveParameter) : $this->evaluation->objectiveParameter->details->last()->label,

    ];
  }

  private function calculateIndexRangeResult($percentage, $parameter)
  {
    $orderedDetailsByTo = $parameter->details->sortBy('to');
    foreach ($orderedDetailsByTo as $index => $detail) {
      if ($percentage < $detail->to) {
        return $index;
      }
    }
    return $orderedDetailsByTo->count() - 1; // Si es mayor que todos, retorna el último índice
  }

  private function calculateLabelRangeResult($percentage, $parameter)
  {
    $orderedDetailsByTo = $parameter->details->sortBy('to');
    foreach ($orderedDetailsByTo as $detail) {
      if ($percentage < $detail->to) {
        return $detail->label;
      }
    }
    return $orderedDetailsByTo->last()->label; // Si es mayor que todos, retorna la última etiqueta
  }
}

