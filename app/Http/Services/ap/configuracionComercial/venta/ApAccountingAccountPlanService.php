<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApAccountingAccountPlanResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\ap\configuracionComercial\venta\ApAccountingAccountPlan;

class ApAccountingAccountPlanService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApAccountingAccountPlan::class,
      $request,
      ApAccountingAccountPlan::filters,
      ApAccountingAccountPlan::sorts,
      ApAccountingAccountPlanResource::class,
    );
  }

  public function find($id)
  {
    $accountingAccountPlan = ApAccountingAccountPlan::where('id', $id)->first();
    if (!$accountingAccountPlan) {
      throw new Exception('Plan de cuenta contable no encontrado');
    }
    return $accountingAccountPlan;
  }

  public function store(mixed $data)
  {
    $accountingAccountPlan = ApAccountingAccountPlan::create($data);
    return new ApAccountingAccountPlanResource($accountingAccountPlan);
  }

  public function show($id)
  {
    return new ApAccountingAccountPlanResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $accountingAccountPlan = $this->find($data['id']);
    $accountingAccountPlan->update($data);
    return new ApAccountingAccountPlanResource($accountingAccountPlan);
  }

  public function destroy($id)
  {
    $accountingAccountPlan = $this->find($id);
    DB::transaction(function () use ($accountingAccountPlan) {
      $accountingAccountPlan->delete();
    });
    return response()->json(['message' => 'Plan de cuenta contable eliminado correctamente']);
  }
}
