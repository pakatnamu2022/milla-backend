<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationParEvaluatorResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\evaluacion\EvaluationParEvaluator;
use Exception;
use Illuminate\Http\Request;

class EvaluationParEvaluatorService extends BaseService implements BaseServiceInterface
{
  /**
   * List all evaluation par evaluators with filtering, sorting (no pagination)
   */
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      EvaluationParEvaluator::class,
      $request,
      EvaluationParEvaluator::filters,
      EvaluationParEvaluator::sorts,
      EvaluationParEvaluatorResource::class,
    );
  }

  /**
   * Find a specific evaluation par evaluator by ID
   * @throws Exception
   */
  public function find($id)
  {
    $parEvaluator = EvaluationParEvaluator::with(['worker', 'mate'])->find($id);
    if (!$parEvaluator) {
      throw new Exception('Par evaluador no encontrado');
    }
    return $parEvaluator;
  }

  /**
   * Show a specific evaluation par evaluator
   */
  public function show($id)
  {
    return new EvaluationParEvaluatorResource($this->find($id));
  }

  /**
   * Store new evaluation par evaluators (multiple mates for one worker)
   * @throws Exception
   */
  public function store($data)
  {
    try {
      $workerId = $data['worker_id'];
      $mateIds = $data['mate_ids'];
      $createdRecords = [];

      foreach ($mateIds as $mateId) {
        // Check if this combination already exists (including soft deleted)
        $existing = EvaluationParEvaluator::withTrashed()
          ->where('worker_id', $workerId)
          ->where('mate_id', $mateId)
          ->first();

        if ($existing) {
          if ($existing->trashed()) {
            // Restore if soft deleted
            $existing->restore();
            $createdRecords[] = $existing;
          } else {
            // Already exists and is active, skip or throw error
            throw new Exception("El par evaluador con mate_id {$mateId} ya existe para este trabajador");
          }
        } else {
          // Create new record
          $parEvaluator = EvaluationParEvaluator::create([
            'worker_id' => $workerId,
            'mate_id' => $mateId,
          ]);
          $createdRecords[] = $parEvaluator;
        }
      }

      return EvaluationParEvaluatorResource::collection($createdRecords);
    } catch (\Exception $e) {
      throw new Exception('Error al crear los pares evaluadores: ' . $e->getMessage());
    }
  }

  /**
   * Update (sync) evaluation par evaluators for a worker
   * @throws Exception
   */
  public function update($data)
  {
    try {
      $workerId = $data['id']; // This is the worker_id passed as route parameter
      $mateIds = $data['mate_ids'];

      // Get all existing records for this worker (including soft deleted)
      $existingRecords = EvaluationParEvaluator::withTrashed()
        ->where('worker_id', $workerId)
        ->get();

      $syncedRecords = [];

      // Process each mate_id in the request
      foreach ($mateIds as $mateId) {
        $existing = $existingRecords->firstWhere('mate_id', $mateId);

        if ($existing) {
          if ($existing->trashed()) {
            // Restore if soft deleted
            $existing->restore();
          }
          $syncedRecords[] = $existing->fresh();
        } else {
          // Create new record
          $newRecord = EvaluationParEvaluator::create([
            'worker_id' => $workerId,
            'mate_id' => $mateId,
          ]);
          $syncedRecords[] = $newRecord;
        }
      }

      // Soft delete records that are not in the mate_ids array
      foreach ($existingRecords as $record) {
        if (!in_array($record->mate_id, $mateIds) && !$record->trashed()) {
          $record->delete();
        }
      }

      return EvaluationParEvaluatorResource::collection(collect($syncedRecords));
    } catch (\Exception $e) {
      throw new Exception('Error al actualizar los pares evaluadores: ' . $e->getMessage());
    }
  }

  /**
   * Delete an evaluation par evaluator
   * @throws Exception
   */
  public function destroy($id)
  {
    try {
      $parEvaluator = $this->find($id);
      $parEvaluator->delete();
      return response()->json(['message' => 'Par evaluador eliminado correctamente']);
    } catch (\Exception $e) {
      throw new Exception('Error al eliminar el par evaluador: ' . $e->getMessage());
    }
  }

  /**
   * Get all par evaluators for a specific worker
   * @throws Exception
   */
  public function getByWorker($workerId)
  {
    try {
      $parEvaluators = EvaluationParEvaluator::with(['worker', 'mate'])
        ->where('worker_id', $workerId)
        ->get();

      return EvaluationParEvaluatorResource::collection($parEvaluators);
    } catch (\Exception $e) {
      throw new Exception('Error al obtener los pares evaluadores: ' . $e->getMessage());
    }
  }
}
