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
   * Store a new evaluation par evaluator
   * @throws Exception
   */
  public function store($data)
  {
    try {
      $parEvaluator = EvaluationParEvaluator::create($data);
      return new EvaluationParEvaluatorResource($parEvaluator);
    } catch (\Exception $e) {
      throw new Exception('Error al crear el par evaluador: ' . $e->getMessage());
    }
  }

  /**
   * Update an existing evaluation par evaluator
   * @throws Exception
   */
  public function update($data)
  {
    try {
      $parEvaluator = $this->find($data['id']);
      $parEvaluator->update($data);
      return new EvaluationParEvaluatorResource($parEvaluator);
    } catch (\Exception $e) {
      throw new Exception('Error al actualizar el par evaluador: ' . $e->getMessage());
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
}
