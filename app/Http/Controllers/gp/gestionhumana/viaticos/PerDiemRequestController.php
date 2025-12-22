<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\IndexPerDiemRatesRequestRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\IndexPerDiemRequestRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\StorePerDiemRequestRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\UpdatePerDiemRequestRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\ReviewPerDiemRequestRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\MarkPaidPerDiemRequestRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\StartSettlementPerDiemRequestRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\CompleteSettlementPerDiemRequestRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemRateResource;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemRequestResource;
use App\Http\Services\gp\gestionhumana\viaticos\PerDiemRequestService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

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
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Display per diem requests for the logged-in user
   */
  public function myRequests(IndexPerDiemRequestRequest $request)
  {
    try {
      $partnerId = auth()->user()->partner_id;

      // Merge the employee_id filter with existing request filters
      $request->merge(['employee_id' => $partnerId]);

      return $this->service->list($request);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Display pending approval requests for the logged-in user (as approver/manager)
   */
  public function pendingApprovals()
  {
    try {
      return $this->service->getPendingApprovals();
    } catch (Exception $e) {
      return $this->error($e->getMessage());
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
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Display the specified per diem request
   */
  public function show(int $id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
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
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Remove the specified per diem request
   */
  public function destroy(int $id)
  {
    try {
      return $this->service->destroy($id);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get overdue settlements
   */
  public function overdue()
  {
    try {
      return $this->success(PerDiemRequestResource::collection($this->service->getOverdueSettlements()));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get rates for destination and category
   */
  public function rates(IndexPerDiemRatesRequestRequest $request)
  {
    try {
      $districtId = $request->query('district_id');
      $categoryId = $request->query('category_id');

      return $this->success(PerDiemRateResource::collection($this->service->getRatesForDestination($districtId, $categoryId)));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Submit request for approval
   */
  public function submit(int $id)
  {
    try {
      $request = $this->service->submit($id);

      return $this->success([
        'data' => new PerDiemRequestResource($request),
        'message' => 'Solicitud enviada para aprobación exitosamente'
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
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

      return $this->success([
        'data' => new PerDiemRequestResource($perDiemRequest),
        'message' => 'Solicitud marcada como pagada exitosamente'
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
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

      return $this->success([
        'data' => new PerDiemRequestResource($perDiemRequest),
        'message' => 'Proceso de liquidación iniciado exitosamente'
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
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

      return $this->success([
        'data' => new PerDiemRequestResource($perDiemRequest),
        'message' => 'Liquidación completada exitosamente'
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Review (approve or reject) per diem request
   */
  public function review(ReviewPerDiemRequestRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $approval = $this->service->review($id, $data);

      $message = $data['status'] === 'approved'
        ? 'Solicitud aprobada exitosamente'
        : 'Solicitud rechazada exitosamente';

      return $this->success([
        'data' => $approval,
        'message' => $message
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Generate settlement report PDF
   */
  public function settlementPDF($id)
  {
    try {
      $pdf = $this->service->generateSettlementPDF($id);
      $filename = "liquidacion-gastos-{$id}.pdf";
      return $pdf->download($filename);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
