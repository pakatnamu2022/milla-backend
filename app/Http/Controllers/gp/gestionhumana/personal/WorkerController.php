<?php

namespace App\Http\Controllers\gp\gestionhumana\personal;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexConsultantRequest;
use App\Http\Requests\gp\gestionhumana\personal\IndexWorkerRequest;
use App\Http\Services\gp\gestionhumana\personal\WorkerService;
use Illuminate\Http\Request;

class WorkerController extends Controller
{
  protected WorkerService $service;

  public function __construct(WorkerService $service)
  {
    $this->service = $service;
  }

  public function index(IndexWorkerRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function myConsultants(IndexConsultantRequest $request)
  {
    try {
      return $this->service->myConsultants($request);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function show(string $id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Revalidate workers data.
   * @return \Illuminate\Http\JsonResponse
   */
  public function revalidate()
  {
    try {
      return $this->success($this->service->revalidate());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function getWorkersWithoutCategoriesAndObjectives()
  {
    try {
      return $this->service->getWorkersWithoutCategoriesAndObjectives();
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function getWorkersWithoutObjectives()
  {
    try {
      return $this->service->getWorkersWithoutObjectives();
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function getWorkersWithoutCategories()
  {
    try {
      return $this->service->getWorkersWithoutCategories();
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function getWorkersWithoutCompetences()
  {
    try {
      return $this->service->getWorkersWithoutCompetences();
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function assignObjectivesToWorkers()
  {
    try {
      return $this->service->assignObjectivesToWorkers();
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
