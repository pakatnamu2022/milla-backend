<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\HierarchicalCategoryDetailResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategoryDetail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HierarchicalCategoryDetailService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            HierarchicalCategoryDetail::class,
            $request,
            HierarchicalCategoryDetail::filters,
            HierarchicalCategoryDetail::sorts,
            HierarchicalCategoryDetailResource::class,
        );
    }

    public function find($id)
    {
        $hierarchicalCategoryDetail = HierarchicalCategoryDetail::where('id', $id)->first();
        if (!$hierarchicalCategoryDetail) {
            throw new Exception('Detalle de Categoría Jerárquica no encontrada');
        }
        return $hierarchicalCategoryDetail;
    }

    public function store(array $data)
    {
        $hierarchicalCategoryDetail = HierarchicalCategoryDetail::create($data);
        return new HierarchicalCategoryDetailResource($hierarchicalCategoryDetail);
    }

    public function storeMany(int $categoryId, array $positions)
    {
        $newPositionIds = collect($positions)->pluck('position_id')->unique()->values();

        // Obtener los registros actuales
        $existing = HierarchicalCategoryDetail::where('hierarchical_category_id', $categoryId)->get();
        $existingPositionIds = $existing->pluck('position_id');

        // Posiciones que hay que insertar
        $toInsert = $newPositionIds->diff($existingPositionIds);
        // Posiciones que hay que eliminar
        $toDelete = $existingPositionIds->diff($newPositionIds);

        // Insertar nuevas
        foreach ($toInsert as $positionId) {
            HierarchicalCategoryDetail::create([
                'hierarchical_category_id' => $categoryId,
                'position_id' => $positionId,
            ]);
        }

        // Eliminar las que ya no vienen
        HierarchicalCategoryDetail::where('hierarchical_category_id', $categoryId)
            ->whereIn('position_id', $toDelete)
            ->delete();

        // Devolver todos los registros actuales actualizados
        $final = HierarchicalCategoryDetail::where('hierarchical_category_id', $categoryId)->get();

        return HierarchicalCategoryDetailResource::collection($final);
    }


    public function show($id)
    {
        return new HierarchicalCategoryDetailResource($this->find($id));
    }

    public function update($data)
    {
        $hierarchicalCategoryDetail = $this->find($data['id']);
        $hierarchicalCategoryDetail->update($data);
        return new HierarchicalCategoryDetailResource($hierarchicalCategoryDetail);
    }

    public function destroy($id)
    {
        $hierarchicalCategoryDetail = $this->find($id);
        DB::transaction(function () use ($hierarchicalCategoryDetail) {
            $hierarchicalCategoryDetail->delete();
        });
        return response()->json(['message' => 'Detalle de Categoría Jerárquica eliminada correctamente']);
    }
}
