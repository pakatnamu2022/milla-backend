<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\DeleteEvaluationCategoryObjectiveDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationCategoryObjectiveDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationCategoryObjectiveDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateEvaluationCategoryObjectiveDetailRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetailService;

class EvaluationCategoryObjectiveDetailController extends Controller
{
  protected EvaluationCategoryObjectiveDetailService $service;

  public function __construct(EvaluationCategoryObjectiveDetailService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of the resource.
   * @param IndexEvaluationCategoryObjectiveDetailRequest $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function index(IndexEvaluationCategoryObjectiveDetailRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display the workers associated with the given evaluation category objective detail.
   * @param int $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function workers(int $id)
  {
    try {
      return $this->service->workers($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Store a newly created resource in storage.
   * @param StoreEvaluationCategoryObjectiveDetailRequest $request
   * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
   */
  public function store(StoreEvaluationCategoryObjectiveDetailRequest $request)
  {
    try {
      return $this->service->store($request->validated());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display the specified resource.
   * @param int $id
   * @return void
   */
  public function show(int $id)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   * @param UpdateEvaluationCategoryObjectiveDetailRequest $request
   * @param int $id
   * @return \App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetailResource|\Illuminate\Http\JsonResponse
   */
  public function update(UpdateEvaluationCategoryObjectiveDetailRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->service->update($data);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Regenerate the objectives for a specific person and category.
   * @param int $category
   * @param int $person
   * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
   */
  public function regeneratePersonObjectives(int $category, int $person)
  {
    try {
      return $this->service->regeneratePersonObjectives($category, $person);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Recalculate the homogeneous weights for a specific person and category.
   * @param int $category
   * @param int $person
   * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
   */
  public function recalculateHomogeneousWeights(int $category, int $person)
  {
    try {
      return $this->service->recalculateHomogeneousWeights($category, $person);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Remove the specified resource from storage.
   * @param DeleteEvaluationCategoryObjectiveDetailRequest $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function destroy(DeleteEvaluationCategoryObjectiveDetailRequest $request)
  {
    try {
      return $this->success($this->service->destroy($request));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
