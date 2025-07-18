<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\HierarchicalCategoryResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class HierarchicalCategoryService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            HierarchicalCategory::class,
            $request,
            HierarchicalCategory::filters,
            HierarchicalCategory::sorts,
            HierarchicalCategoryResource::class,
        );
    }

    public function find($id)
    {
        $evaluationCompetence = HierarchicalCategory::where('id', $id)->first();
        if (!$evaluationCompetence) {
            throw new Exception('Categoría Jerárquica no encontrada');
        }
        return $evaluationCompetence;
    }

    public function store(array $data)
    {
        $hierarchicalCategory = HierarchicalCategory::create($data);
        return new HierarchicalCategoryResource($hierarchicalCategory);
    }

    public function show($id)
    {
        return new HierarchicalCategoryResource($this->find($id));
    }

    public function update($data)
    {
        $evaluationCompetence = $this->find($data['id']);
        $evaluationCompetence->update($data);
        return new HierarchicalCategoryResource($evaluationCompetence);
    }

    public function destroy($id)
    {
        $evaluationCompetence = $this->find($id);
        DB::transaction(function () use ($evaluationCompetence) {
            $evaluationCompetence->delete();
        });
        return response()->json(['message' => 'Categoría Jerárquica eliminada correctamente']);
    }
}
