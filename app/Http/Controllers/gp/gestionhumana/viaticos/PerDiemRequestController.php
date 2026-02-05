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
use App\Http\Requests\gp\gestionhumana\viaticos\ApproveSettlementPerDiemRequestRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\RejectSettlementPerDiemRequestRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\CancelPerDiemRequestRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\ResendPerDiemRequestEmailsRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemRateResource;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemRequestResource;
use App\Http\Services\gp\gestionhumana\viaticos\PerDiemRequestService;
use Exception;
use Illuminate\Http\Request;

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
   * Supports filtering by approval_status: 'pending', 'approved', 'all'
   */
  public function pendingApprovals(IndexPerDiemRequestRequest $request)
  {
    try {
      return $this->service->getPendingApprovals($request);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Display pending settlements for the logged-in user
   * Shows settlements that need approval from the user (as boss or module approver)
   */
  public function pendingSettlements()
  {
    try {
      return $this->service->getPendingSettlements();
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
      return $this->success($this->service->store($request->validated()));
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
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
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
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
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
   * Cancel a per diem request
   */
  public function cancel(CancelPerDiemRequestRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $perDiemRequest = $this->service->cancel($id, $data);

      return $this->success([
        'data' => new PerDiemRequestResource($perDiemRequest),
        'message' => 'Solicitud cancelada exitosamente'
      ]);
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
   * Approve settlement
   */
  public function approveSettlement(ApproveSettlementPerDiemRequestRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $perDiemRequest = $this->service->approveSettlement($id, $data);

      return $this->success([
        'data' => new PerDiemRequestResource($perDiemRequest),
        'message' => 'Liquidación aprobada exitosamente'
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Reject settlement
   */
  public function rejectSettlement(RejectSettlementPerDiemRequestRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $perDiemRequest = $this->service->rejectSettlement($id, $data);

      return $this->success([
        'data' => new PerDiemRequestResource($perDiemRequest),
        'message' => 'Liquidación rechazada exitosamente'
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
   * Generate expenseTotal report PDF
   */
  public function expenseTotalPDF($id)
  {
    try {
      $pdf = $this->service->generateExpenseTotalPDF($id);
      $filename = "liquidacion-gastos-{$id}.pdf";
      return $pdf->download($filename);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Generate expense detail report PDF (only employee expenses)
   */
  public function expenseDetailPDF($id)
  {
    try {
      $pdf = $this->service->generateExpenseDetailPDF($id);
      $filename = "detalle-gastos-personal-{$id}.pdf";
      return $pdf->download($filename);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Confirm a per diem request
   * Transitions from 'approved' to 'in_progress' and regenerates budgets
   */
  public function confirm(int $id)
  {
    try {
      $perDiemRequest = $this->service->confirm($id);

      return $this->success([
        'data' => $perDiemRequest,
        'message' => 'Solicitud confirmada exitosamente. Los presupuestos han sido recalculados.'
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Confirm a per diem request
   * Transitions from 'approved' to 'in_progress' and regenerates budgets
   */
  public function confirmProgress(int $id)
  {
    try {
      $perDiemRequest = $this->service->confirmProgress($id);

      return $this->success([
        'data' => $perDiemRequest,
        'message' => 'Solicitud marcada como en progreso exitosamente.'
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get available budgets for a per diem request
   */
  public function availableBudgets(int $id)
  {
    try {
      $budgets = $this->service->getAvailableBudgets($id);

      return $this->success($budgets);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get available expense types for a per diem request
   */
  public function availableExpenseTypes(int $id)
  {
    try {
      $expenseTypes = $this->service->getAvailableExpenseTypes($id);

      return $this->success($expenseTypes);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Upload deposit voucher for a per diem request
   */
  public function agregarDeposito(int $id, Request $request)
  {
    try {
      // Validate that a file was uploaded
      if (!$request->hasFile('voucher')) {
        return $this->error('No se ha proporcionado ningún archivo de voucher');
      }

      $voucherFile = $request->file('voucher');

      // Validate file type (images and PDFs)
      $request->validate([
        'voucher' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240' // Max 10MB
      ]);

      $perDiemRequest = $this->service->agregarDeposito($id, $voucherFile);

      return $this->success([
        'data' => $perDiemRequest,
        'message' => 'Voucher de depósito agregado exitosamente'
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Generate mobility payroll for a per diem request and return PDF directly
   * Creates or updates the payroll and returns the PDF for download
   */
  public function generateMobilityPayrollPDF(int $id)
  {
    try {
      $pdf = $this->service->generateMobilityPayrollPDF($id);
      $filename = "planilla-movilidad-{$id}.pdf";
      return $pdf->download($filename);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Generate expenseTotal report PDF with evidence/receipts
   */
  public function expenseTotalWithEvidencePDF($id)
  {
    try {
      $pdf = $this->service->generateExpenseTotalWithEvidencePDF($id);
      $filename = "liquidacion-gastos-evidencias-{$id}.pdf";
      return $pdf->download($filename);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Reset approvals for a per diem request
   * Creates missing boss approvals and resets approved/rejected ones to pending
   */
  public function resetApprovals(int $id)
  {
    try {
      $perDiemRequest = $this->service->resetApprovals($id);

      return $this->success([
        'data' => new PerDiemRequestResource($perDiemRequest),
        'message' => 'Aprobaciones restablecidas exitosamente'
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Resend emails for a per diem request
   * Allows filtering recipients with boolean parameters
   */
  public function resendEmails(ResendPerDiemRequestEmailsRequest $request, int $id)
  {
    try {
      $result = $this->service->resendEmails($id, $request->validated());

      return $this->success([
        'data' => $result,
        'message' => $result['message']
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
