<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationPersonCycleDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationPersonDetailRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationPersonDetailService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonDetail;
use Illuminate\Http\Request;

class EvaluationPersonDetailController extends Controller
{
  protected EvaluationPersonDetailService $service;

  public function __construct(EvaluationPersonDetailService $service)
  {
    $this->service = $service;
  }

  /**
   * Muestra todos los detalles de personas para evaluación
   */
  public function index(IndexEvaluationPersonCycleDetailRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreEvaluationPersonDetailRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display the specified resource.
   */
  public function show(int $id)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, int $id)
  {
    //
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(int $id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
