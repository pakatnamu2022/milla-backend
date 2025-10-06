<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexOpportunityRequest;
use App\Http\Requests\ap\comercial\StoreOpportunityRequest;
use App\Http\Requests\ap\comercial\UpdateOpportunityRequest;
use App\Http\Services\ap\comercial\OpportunityService;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
  protected OpportunityService $service;

  public function __construct(OpportunityService $service)
  {
    $this->service = $service;
  }

  public function index(IndexOpportunityRequest $request)
  {
    return $this->service->list($request);
  }

  public function store(StoreOpportunityRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateOpportunityRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Crear oportunidad desde un cliente específico
   * POST /api/ap/commercial/businessPartners/{clientId}/opportunities
   */
  public function storeFromClient(StoreOpportunityRequest $request, $clientId)
  {
    try {
      return $this->success($this->service->storeFromClient($clientId, $request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtener acciones de una oportunidad
   * GET /api/ap/commercial/opportunities/{opportunityId}/actions
   */
  public function getActions($opportunityId)
  {
    try {
      return $this->success($this->service->getOpportunityActions($opportunityId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtener mis oportunidades (con permisos de jefe)
   * GET /api/ap/commercial/opportunities/my-opportunities
   */
  public function myOpportunities(Request $request)
  {
    try {
      // Obtener el worker_id del request (debe venir del usuario autenticado)
      $workerId = $request->input('worker_id');

      if (!$workerId) {
        return $this->error('El worker_id es requerido');
      }

      return $this->success($this->service->getMyOpportunities($request, $workerId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtener agenda del asesor (acciones agrupadas por fecha)
   * GET /api/ap/commercial/opportunities/my-agenda
   */
  public function myAgenda(Request $request)
  {
    try {
      // Obtener el worker_id del request (debe venir del usuario autenticado)
      $workerId = $request->input('worker_id');

      if (!$workerId) {
        return $this->error('El worker_id es requerido');
      }

      return $this->success($this->service->getMyAgenda($request, $workerId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
