<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationCategoryObjectiveDetailRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetailService;
use Illuminate\Http\Request;

class EvaluationCategoryObjectiveDetailController extends Controller
{
  protected EvaluationCategoryObjectiveDetailService $service;

  public function __construct(EvaluationCategoryObjectiveDetailService $service)
  {
    $this->service = $service;
  }

  public function index(IndexEvaluationCategoryObjectiveDetailRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(Request $request)
  {
    //
  }

  public function show(int $id)
  {
    //
  }

  public function update(Request $request, int $id)
  {
    //
  }

  public function destroy(int $id)
  {
    //
  }
}
