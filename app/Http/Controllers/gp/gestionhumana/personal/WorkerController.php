<?php

namespace App\Http\Controllers\gp\gestionhumana\personal;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\personal\IndexWorkerRequest;
use App\Http\Requests\gp\gestionhumana\personal\StoreWorkerRequest;
use App\Http\Requests\gp\gestionhumana\personal\UpdateWorkerRequest;
use App\Http\Services\gp\gestionhumana\personal\WorkerService;

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
