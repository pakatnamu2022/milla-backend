<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\IndexRequestBudgetRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\StoreRequestBudgetRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\UpdateRequestBudgetRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\RequestBudgetResource;
use App\Http\Services\gp\gestionhumana\viaticos\RequestBudgetService;
use Throwable;

class RequestBudgetController extends Controller
{
  protected RequestBudgetService $service;

  public function __construct(RequestBudgetService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of request budgets
   */
  public function index(IndexRequestBudgetRequest $request)
  {
    try {
      return $this->service->index($request);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Store a newly created request budget
   */
  public function store(StoreRequestBudgetRequest $request)
  {
    try {
      $budget = $this->service->store($request->validated());
      return $this->success([
        'data' => new RequestBudgetResource($budget),
        'message' => 'Presupuesto creado exitosamente'
      ]);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display the specified request budget
   */
  public function show(int $id)
  {
    try {
      $budget = $this->service->show($id);
      if (!$budget) {
        return $this->error('Presupuesto no encontrado');
      }
      return $this->success(new RequestBudgetResource($budget));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Update the specified request budget
   */
  public function update(UpdateRequestBudgetRequest $request, int $id)
  {
    try {
      $budget = $this->service->update($id, $request->validated());
      return $this->success([
        'data' => new RequestBudgetResource($budget),
        'message' => 'Presupuesto actualizado exitosamente'
      ]);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Remove the specified request budget
   */
  public function destroy(int $id)
  {
    try {
      $this->service->destroy($id);
      return $this->success(['message' => 'Presupuesto eliminado exitosamente']);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
