<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationModelResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\evaluacion\EvaluationModel;
use Exception;
use Illuminate\Http\Request;

class EvaluationModelService extends BaseService implements BaseServiceInterface
{
  /**
   * List all evaluation models with filtering, sorting (no pagination)
   */
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      EvaluationModel::class,
      $request,
      EvaluationModel::filters,
      EvaluationModel::sorts,
      EvaluationModelResource::class,
    );
  }

  /**
   * Find a specific evaluation model by ID
   * @throws Exception
   */
  public function find($id)
  {
    $evaluationModel = EvaluationModel::find($id);
    if (!$evaluationModel) {
      throw new Exception('Modelo de evaluación no encontrado');
    }
    return $evaluationModel;
  }

  /**
   * Show a specific evaluation model
   */
  public function show($id)
  {
    return new EvaluationModelResource($this->find($id));
  }

  /**
   * Store a new evaluation model
   * @throws Exception
   */
  public function store($data)
  {
    try {
      // Validate that weights sum to 100
      $totalWeight = ($data['leadership_weight'] ?? 0) +
                     ($data['self_weight'] ?? 0) +
                     ($data['par_weight'] ?? 0) +
                     ($data['report_weight'] ?? 0);

      if ($totalWeight != 100) {
        throw new Exception('La suma de los pesos debe ser igual a 100');
      }

      $evaluationModel = EvaluationModel::create($data);
      return new EvaluationModelResource($evaluationModel);
    } catch (\Exception $e) {
      throw new Exception('Error al crear el modelo de evaluación: ' . $e->getMessage());
    }
  }

  /**
   * Update an existing evaluation model
   * @throws Exception
   */
  public function update($data)
  {
    try {
      $evaluationModel = $this->find($data['id']);

      // Validate that weights sum to 100
      $totalWeight = ($data['leadership_weight'] ?? $evaluationModel->leadership_weight) +
                     ($data['self_weight'] ?? $evaluationModel->self_weight) +
                     ($data['par_weight'] ?? $evaluationModel->par_weight) +
                     ($data['report_weight'] ?? $evaluationModel->report_weight);

      if ($totalWeight != 100) {
        throw new Exception('La suma de los pesos debe ser igual a 100');
      }

      $evaluationModel->update($data);
      return new EvaluationModelResource($evaluationModel);
    } catch (\Exception $e) {
      throw new Exception('Error al actualizar el modelo de evaluación: ' . $e->getMessage());
    }
  }

  /**
   * Delete an evaluation model
   * @throws Exception
   */
  public function destroy($id)
  {
    try {
      $evaluationModel = $this->find($id);
      $evaluationModel->delete();
      return response()->json(['message' => 'Modelo de evaluación eliminado correctamente']);
    } catch (\Exception $e) {
      throw new Exception('Error al eliminar el modelo de evaluación: ' . $e->getMessage());
    }
  }
}