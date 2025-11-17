<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationCycleCategoryDetailResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycleCategoryDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function json_encode;

class EvaluationCycleCategoryDetailService extends BaseService
{
  protected EvaluationPersonCycleDetailService $evaluationPersonCycleDetailService;
  protected EvaluationCycleService $evaluationCycleService;

  public function __construct(
    EvaluationPersonCycleDetailService $evaluationPersonCycleDetailService,
    EvaluationCycleService             $evaluationCycleService
  )
  {
    $this->evaluationPersonCycleDetailService = $evaluationPersonCycleDetailService;
    $this->evaluationCycleService = $evaluationCycleService;
  }

  public function list(Request $request, int $cycleId)
  {
    $cycle = $this->evaluationCycleService->find($cycleId);
    return $this->getFilteredResults(
      EvaluationCycleCategoryDetail::where('cycle_id', $cycle->id)->whereNull('deleted_at'),
      $request,
      [],
      [],
      EvaluationCycleCategoryDetailResource::class,
    );
  }

  public function store($data)
  {
    $evaluationCycle = EvaluationCycleCategoryDetail::create($data);
    return new EvaluationCycleCategoryDetailResource(EvaluationCycleCategoryDetail::find($evaluationCycle->id));
  }

  public function storeMany($cycleId, $data)
  {
    $newCategories = collect($data['categories'] ?? [])->unique()->values();

    $cycle = $this->evaluationCycleService->find($cycleId);

    $listAllInValidatedCategories = HierarchicalCategory::whereAllPersonsHaveJefe
    ($cycle->typeEvaluation == 0)
      ->filter(fn($category) => !$category->pass)
      ->pluck('id');

    $newCategoryIds = $newCategories->diff($listAllInValidatedCategories);

    $existing = EvaluationCycleCategoryDetail::where('cycle_id', $cycleId)->get();
    $existingCycleCategoryIds = $existing->pluck('hierarchical_category_id');
    $toInsert = $newCategoryIds->diff($existingCycleCategoryIds);
    $toDelete = $existingCycleCategoryIds->diff($newCategoryIds);

    foreach ($toInsert as $categoryId) {
//      create or restore
      EvaluationCycleCategoryDetail::create([
        'cycle_id' => $cycleId,
        'hierarchical_category_id' => $categoryId,
      ]);
    }

    DB::transaction(function () use ($cycleId, $toDelete) {
      if (empty($toDelete)) return;

      // 1) Borra (soft-delete) los detalles de categorías del ciclo que salieron
      EvaluationCycleCategoryDetail::where('cycle_id', $cycleId)
        ->whereIn('hierarchical_category_id', $toDelete)
        ->delete();

      // 2) Borra (soft-delete) los detalles de persona del ciclo ligados a esas categorías
      EvaluationPersonCycleDetail::where('cycle_id', $cycleId)
        ->whereIn('category_id', $toDelete)
        ->orderBy('id')
        ->chunkById(200, function ($chunk) {
          foreach ($chunk as $detail) {
            // Mantienes tu lógica centralizada en el service
            $this->evaluationPersonCycleDetailService->destroy($detail->id);
          }
        });
    });

    $final = EvaluationCycleCategoryDetail::where('cycle_id', $cycleId)->get();

    foreach ($toInsert as $category) {
      $this->evaluationPersonCycleDetailService->storeByCategoryAndCycle(
        $cycleId,
        $category
      );
    }

    $this->evaluationPersonCycleDetailService->revalidateAllPersonsInCycle($cycleId);

    return EvaluationCycleCategoryDetailResource::collection($final);
  }

  public function find($id)
  {
    $evaluationCycle = EvaluationCycleCategoryDetail::where('id', $id)->first();
    if (!$evaluationCycle) {
      throw new Exception('Detalle de Ciclo Categoria no encontrado');
    }
    return $evaluationCycle;
  }

  public function show($id)
  {
    return new EvaluationCycleCategoryDetailResource($this->find($id));
  }

  public function update($data)
  {
    $evaluationCycle = $this->find($data['id']);
    $evaluationCycle->update($data);
    return new EvaluationCycleCategoryDetailResource($evaluationCycle);
  }

  public function destroy($id)
  {
    $evaluationCycle = $this->find($id);
    $evaluationCycle->delete();
    return response()->json(['message' => 'Detalle de Ciclo Categoria eliminado correctamente']);
  }
}
