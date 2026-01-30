<?php

namespace App\Http\Services\gp\tics;

use App\Http\Resources\gp\tics\TelephonePlanResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\tics\TelephonePlan;
use Exception;
use Illuminate\Http\Request;

class TelephonePlanService extends BaseService implements BaseServiceInterface
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            TelephonePlan::query(),
            $request,
            TelephonePlan::filters,
            TelephonePlan::sorts,
            TelephonePlanResource::class,
        );
    }

    public function store($data)
    {
        $telephonePlan = TelephonePlan::create($data);
        return new TelephonePlanResource(TelephonePlan::find($telephonePlan->id));
    }

    public function find($id)
    {
        $telephonePlan = TelephonePlan::where('id', $id)->first();
        if (!$telephonePlan) {
            throw new Exception('Plan telefónico no encontrado');
        }
        return $telephonePlan;
    }

    public function show($id)
    {
        return new TelephonePlanResource($this->find($id));
    }

    public function update($data)
    {
        $telephonePlan = $this->find($data['id']);
        $telephonePlan->update($data);
        return new TelephonePlanResource($telephonePlan);
    }

    public function destroy($id)
    {
        $telephonePlan = $this->find($id);
        $telephonePlan->delete();
        return response()->json(['message' => 'Plan telefónico eliminado correctamente']);
    }
}