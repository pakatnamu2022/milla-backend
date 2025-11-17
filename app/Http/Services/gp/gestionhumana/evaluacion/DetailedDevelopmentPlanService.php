<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\DetailedDevelopmentPlanResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\DetailedDevelopmentPlan;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function response;

class DetailedDevelopmentPlanService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            DetailedDevelopmentPlan::class,
            $request,
            DetailedDevelopmentPlan::filters,
            DetailedDevelopmentPlan::sorts,
            DetailedDevelopmentPlanResource::class,
        );
    }

    public function find($id)
    {
        $detailedDevelopmentPlan = DetailedDevelopmentPlan::where('id', $id)->first();
        if (!$detailedDevelopmentPlan) {
            throw new Exception('Plan de desarrollo detallado no encontrado');
        }
        return $detailedDevelopmentPlan;
    }

    public function store(array $data)
    {
        $detailedDevelopmentPlan = DetailedDevelopmentPlan::create($data);
        return new DetailedDevelopmentPlanResource($detailedDevelopmentPlan);
    }

    public function show($id)
    {
        return new DetailedDevelopmentPlanResource($this->find($id));
    }

    public function update($data)
    {
        $detailedDevelopmentPlan = $this->find($data['id']);
        $detailedDevelopmentPlan->update($data);
        return new DetailedDevelopmentPlanResource($detailedDevelopmentPlan);
    }

    public function destroy($id)
    {
        $detailedDevelopmentPlan = $this->find($id);
        DB::transaction(function () use ($detailedDevelopmentPlan) {
            $detailedDevelopmentPlan->delete();
        });
        return response()->json(['message' => 'Plan de desarrollo detallado eliminado correctamente']);
    }
}