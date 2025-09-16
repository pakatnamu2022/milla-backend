<?php

namespace App\Http\Services\ap\maestroGeneral;

use App\Http\Resources\ap\maestroGeneral\AssignSalesSeriesResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class AssignSalesSeriesService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      AssignSalesSeries::class,
      $request,
      AssignSalesSeries::filters,
      AssignSalesSeries::sorts,
      AssignSalesSeriesResource::class,
    );
  }

  public function find($id)
  {
    $AssignSalesSeries = AssignSalesSeries::where('id', $id)->first();
    if (!$AssignSalesSeries) {
      throw new Exception('Serie de venta no encontrado');
    }
    return $AssignSalesSeries;
  }

  public function store(Mixed $data)
  {
    $AssignSalesSeries = AssignSalesSeries::create($data);
    return new AssignSalesSeriesResource($AssignSalesSeries);
  }

  public function show($id)
  {
    return new AssignSalesSeriesResource($this->find($id));
  }

  public function update(Mixed $data)
  {
    $AssignSalesSeries = $this->find($data['id']);
    $AssignSalesSeries->update($data);
    return new AssignSalesSeriesResource($AssignSalesSeries);
  }

  public function destroy($id)
  {
    $AssignSalesSeries = $this->find($id);
    DB::transaction(function () use ($AssignSalesSeries) {
      $AssignSalesSeries->delete();
    });
    return response()->json(['message' => 'Serie de venta eliminado correctamente']);
  }
}
