<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationCycleCategoryDetailResource;
use App\Http\Resources\gp\gestionhumana\evaluacion\HierarchicalCategoryDetailResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycleCategoryDetail;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategoryDetail;
use Illuminate\Http\Request;

class EvaluationCycleCategoryDetailService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            EvaluationCycleCategoryDetail::class,
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

    public function storeMany($cycleId, $categories)
    {
        $newCategoryIds = collect($categories['categories'] ?? [])->unique()->values();

        $existing = EvaluationCycleCategoryDetail::where('cycle_id', $cycleId)->get();
        $existingCycleCategoryIds = $existing->pluck('hierarchical_category_id');

        $toInsert = $newCategoryIds->diff($existingCycleCategoryIds);
        $toDelete = $existingCycleCategoryIds->diff($newCategoryIds);

        foreach ($toInsert as $categoryId) {
            EvaluationCycleCategoryDetail::create([
                'cycle_id' => $cycleId,
                'hierarchical_category_id' => $categoryId,
            ]);
        }

        EvaluationCycleCategoryDetail::where('cycle_id', $cycleId)
            ->whereIn('hierarchical_category_id', $toDelete)
            ->delete();

        $final = EvaluationCycleCategoryDetail::where('cycle_id', $cycleId)->get();

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
