<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\IndexPerDiemRequestRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\StorePerDiemRequestRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\UpdatePerDiemRequestRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\MarkPaidPerDiemRequestRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\StartSettlementPerDiemRequestRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\CompleteSettlementPerDiemRequestRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemRequestResource;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemRequestCollection;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemRateResource;
use App\Http\Services\gp\gestionhumana\viaticos\PerDiemRequestService;

class PerDiemRequestController extends Controller
{
  protected PerDiemRequestService $service;

  public function __construct(PerDiemRequestService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of per diem requests
   */
  public function index(IndexPerDiemRequestRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Store a newly created per diem request
   */
  public function store(StorePerDiemRequestRequest $request)
  {
    try {
      $data = $request->validated();
      $perDiemRequest = $this->service->store($data);

      return response()->json([
        'success' => true,
        'data' => $perDiemRequest,
        'message' => 'Solicitud de viático creada exitosamente'
      ], 201);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Display the specified per diem request
   */
  public function show(int $id)
  {
    try {
      $request = $this->service->show($id);

      return response()->json([
        'success' => true,
        'data' => $request
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 404);
    }
  }

  /**
   * Update the specified per diem request
   */
  public function update(UpdatePerDiemRequestRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      $perDiemRequest = $this->service->update($data);

      return response()->json([
        'success' => true,
        'data' => $perDiemRequest,
        'message' => 'Solicitud de viático actualizada exitosamente'
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Remove the specified per diem request
   */
  public function destroy(int $id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Get overdue settlements
   */
  public function overdue()
  {
    try {
      $requests = $this->service->getOverdueSettlements();

      return response()->json([
        'success' => true,
        'data' => new PerDiemRequestCollection($requests)
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Get rates for destination and category
   */
  public function rates()
  {
    try {
      $districtId = request('district_id');
      $categoryId = request('category_id');

      if (!$districtId || !$categoryId) {
        return response()->json([
          'success' => false,
          'message' => 'Se requieren los parámetros district_id y category_id'
        ], 400);
      }

      $rates = $this->service->getRatesForDestination($districtId, $categoryId);

      return response()->json([
        'success' => true,
        'data' => PerDiemRateResource::collection($rates)
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Submit request for approval
   */
  public function submit(int $id)
  {
    try {
      $request = $this->service->submit($id);

      return response()->json([
        'success' => true,
        'data' => new PerDiemRequestResource($request),
        'message' => 'Solicitud enviada para aprobación exitosamente'
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Mark request as paid
   */
  public function markAsPaid(MarkPaidPerDiemRequestRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $perDiemRequest = $this->service->markAsPaid($id, $data);

      return response()->json([
        'success' => true,
        'data' => new PerDiemRequestResource($perDiemRequest),
        'message' => 'Solicitud marcada como pagada exitosamente'
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Start settlement process
   */
  public function startSettlement(StartSettlementPerDiemRequestRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $perDiemRequest = $this->service->startSettlement($id, $data);

      return response()->json([
        'success' => true,
        'data' => new PerDiemRequestResource($perDiemRequest),
        'message' => 'Liquidación iniciada exitosamente'
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Complete settlement
   */
  public function completeSettlement(CompleteSettlementPerDiemRequestRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $perDiemRequest = $this->service->completeSettlement($id, $data);

      return response()->json([
        'success' => true,
        'data' => new PerDiemRequestResource($perDiemRequest),
        'message' => 'Liquidación completada exitosamente'
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }
}
