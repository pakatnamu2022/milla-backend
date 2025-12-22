<?php

namespace App\Http\Services\gp\gestionhumana\viaticos;

use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemRequestResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionhumana\viaticos\PerDiemRequest;
use App\Models\gp\gestionhumana\viaticos\PerDiemRate;
use App\Models\gp\gestionhumana\viaticos\PerDiemPolicy;
use App\Models\gp\gestionhumana\viaticos\PerDiemApproval;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;

class PerDiemRequestService extends BaseService implements BaseServiceInterface
{
  /**
   * Get all per diem requests with filters and pagination
   */
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PerDiemRequest::class,
      $request,
      PerDiemRequest::filters,
      PerDiemRequest::sorts,
      PerDiemRequestResource::class,
    );
  }

  /**
   * Find a per diem request by ID (internal method)
   */
  public function find($id)
  {
    $perDiemRequest = PerDiemRequest::where('id', $id)->first();
    if (!$perDiemRequest) {
      throw new Exception('Solicitud de viático no encontrada');
    }
    return $perDiemRequest;
  }

  /**
   * Show a per diem request by ID
   */
  public function show($id)
  {
    return new PerDiemRequestResource($this->find($id));
  }

  /**
   * Create a new per diem request
   */
  public function store(mixed $data)
  {
    try {
      DB::beginTransaction();

      // Get current policy
      $currentPolicy = PerDiemPolicy::where('is_current', true)->first();
      if (!$currentPolicy) {
        throw new Exception('No hay una política de viáticos activa');
      }

      // Set employee_id from authenticated user
      if (auth()->check()) {
        $data['employee_id'] = auth()->user()->person->id;
      }

      // Get employee's position (cargo) to obtain per_diem_category_id
      $employee = Worker::find($data['employee_id']);
      if (!$employee || !$employee->position) {
        throw new Exception('El empleado no tiene un cargo asignado');
      }

      if (!$employee->position->per_diem_category_id) {
        throw new Exception('El cargo del empleado no tiene una categoría de viático asignada');
      }

      // Generate unique code
      $perDiemRequest = new PerDiemRequest();
      $code = $perDiemRequest->generateCode();

      // Calculate days count
      $startDate = Carbon::parse($data['start_date']);
      $endDate = Carbon::parse($data['end_date']);
      $daysCount = $startDate->diffInDays($endDate) + 1;

      // Prepare request data
      $requestData = [
        'code' => $code,
        'per_diem_policy_id' => $currentPolicy->id,
        'employee_id' => $data['employee_id'],
        'company_id' => $data['company_id'],
        'company_service_id' => $data['company_service_id'],
        'district_id' => $data['district_id'],
        'per_diem_category_id' => $employee->position->per_diem_category_id,
        'start_date' => $data['start_date'],
        'end_date' => $data['end_date'],
        'days_count' => $daysCount,
        'purpose' => $data['purpose'],
        'status' => 'pending',
        'notes' => $data['notes'] ?? null,
        'cash_amount' => 0,
        'transfer_amount' => 0,
        'total_budget' => 0,
        'paid' => false,
        'settled' => false,
        'total_spent' => 0,
        'balance_to_return' => 0,
        'final_result' => "0",
      ];

      // Create the request
      $request = PerDiemRequest::create($requestData);

      DB::commit();
      return new PerDiemRequestResource($request->fresh(['employee', 'company', 'companyService', 'district', 'policy', 'category']));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Update a per diem request
   */
  public function update(mixed $data)
  {
    try {
      DB::beginTransaction();

      $request = $this->find($data['id']);

      // Only allow updates if status is pending or rejected
      if (!in_array($request->status, ['pending', 'rejected'])) {
        throw new Exception('Solo se pueden actualizar solicitudes en estado pendiente o rechazadas');
      }

      // Calculate days count if dates are updated
      if (isset($data['start_date']) || isset($data['end_date'])) {
        $startDate = Carbon::parse($data['start_date'] ?? $request->start_date);
        $endDate = Carbon::parse($data['end_date'] ?? $request->end_date);
        $data['days_count'] = $startDate->diffInDays($endDate) + 1;
      }

      // Update the request
      $request->update($data);

      DB::commit();
      return new PerDiemRequestResource($request->fresh(['employee', 'company', 'companyService', 'district', 'policy', 'category']));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Delete a per diem request
   */
  public function destroy($id)
  {
    $request = $this->find($id);

    // Only allow deletion if status is pending
    if ($request->status !== 'pending') {
      throw new Exception('Solo se pueden eliminar solicitudes en estado pendiente');
    }

    DB::transaction(function () use ($request) {
      // Delete related records
      $request->approvals()->delete();

      // Delete the request
      $request->delete();
    });

    return response()->json(['message' => 'Solicitud de viático eliminada correctamente']);
  }

  /**
   * Get overdue settlement requests
   */
  public function getOverdueSettlements()
  {
    return PerDiemRequest::with([
      'employee',
      'company',
      'category',
      'policy'
    ])
      ->overdue()
      ->orderBy('end_date', 'asc')
      ->get();
  }

  /**
   * Get pending approval requests for the authenticated user (as approver)
   */
  public function getPendingApprovals()
  {
    $approverId = auth()->user()->person->id;

    // Get all per diem requests where the user has a pending approval
    $requests = PerDiemRequest::where('status', PerDiemApproval::PENDING)
      ->with([
        'employee',
        'company',
        'companyService',
        'district',
        'policy',
      ])
      ->orderBy('created_at', 'desc')
      ->get();

    return PerDiemRequestResource::collection($requests);
  }

  /**
   * Get rates for a specific destination and category
   */
  public function getRatesForDestination(int $districtId, int $categoryId): Collection
  {
    return PerDiemRate::getCurrentRatesByDistrict($districtId, $categoryId);
  }

  /**
   * Submit request for approval
   */
  public function submit(int $id): PerDiemRequest
  {
    try {
      DB::beginTransaction();

      $request = $this->find($id);

      // Validate status
      if (!in_array($request->status, ['pending', 'rejected'])) {
        throw new Exception('Solo se pueden enviar solicitudes en estado pendiente o rechazadas');
      }

      // Create approval record for the employee's manager
      if ($request->employee->manager_id) {
        // Check if approval already exists
        $existingApproval = PerDiemApproval::where('per_diem_request_id', $request->id)
          ->where('approver_id', $request->employee->manager_id)
          ->first();

        if (!$existingApproval) {
          PerDiemApproval::create([
            'per_diem_request_id' => $request->id,
            'approver_id' => $request->employee->manager_id,
            'status' => PerDiemApproval::PENDING,
          ]);
        }
      }

      DB::commit();
      return $request->fresh(['employee', 'company', 'companyService', 'district', 'policy', 'category', 'approvals.approver']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Review (approve or reject) a per diem request
   */
  public function review(int $id, array $data): PerDiemApproval
  {
    try {
      DB::beginTransaction();

      $request = $this->find($id);
      $approverId = auth()->user()->person->id ?? $data['approver_id'];

      // Find the approval record for this approver
      $approval = $request->approvals()
        ->where('approver_id', $approverId)
        ->where('status', PerDiemApproval::PENDING)
        ->first();

      if (!$approval) {
        // If no approval exists, create one
        $approval = PerDiemApproval::create([
          'per_diem_request_id' => $request->id,
          'approver_id' => $approverId,
          'status' => $data['status'],
          'comments' => $data['comments'] ?? null,
          'approved_at' => now(),
        ]);
      } else {
        // Update the existing approval
        $approval->update([
          'status' => $data['status'],
          'comments' => $data['comments'] ?? null,
          'approved_at' => now(),
        ]);
      }

      // Update request status based on approval decision
      if ($data['status'] === PerDiemApproval::REJECTED) {
        // If rejected by any approver, immediately deny the request
        $request->update(['status' => 'rejected']);
      } elseif ($data['status'] === PerDiemApproval::APPROVED) {
        // Check if all approvals are approved
        $allApproved = $request->approvals()
            ->where('status', '!=', PerDiemApproval::APPROVED)
            ->count() === 0;

        if ($allApproved) {
          // All approvers approved, update request status to approved
          $request->update(['status' => 'approved']);
        }
        // If not all approved yet, keep status as pending
      }

      DB::commit();
      return $approval->fresh(['approver', 'request']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Mark request as paid
   */
  public function markAsPaid(int $id, array $data): PerDiemRequest
  {
    try {
      DB::beginTransaction();

      $request = $this->find($id);

      // Validate status
      if ($request->status !== 'approved') {
        throw new Exception('Solo se pueden marcar como pagadas las solicitudes aprobadas');
      }

      // Update payment information
      $request->update([
        'paid' => true,
        'payment_date' => $data['payment_date'] ?? now(),
        'payment_method' => $data['payment_method'] ?? null,
        'cash_amount' => $data['cash_amount'] ?? 0,
        'transfer_amount' => $data['transfer_amount'] ?? 0,
      ]);

      DB::commit();
      return $request->fresh(['employee', 'company', 'companyService', 'district', 'policy', 'category']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Start settlement process
   */
  public function startSettlement(int $id, array $data): PerDiemRequest
  {
    try {
      DB::beginTransaction();

      $request = $this->find($id);

      // Validate status - must be approved and paid
      if ($request->status !== 'approved' || !$request->paid) {
        throw new Exception('Solo se puede iniciar liquidación de solicitudes aprobadas y pagadas');
      }

      // Don't change status, just mark settlement as started
      // Status remains 'approved'

      DB::commit();
      return $request->fresh(['employee', 'company', 'companyService', 'district', 'policy', 'category']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Complete settlement
   */
  public function completeSettlement(int $id, array $data): PerDiemRequest
  {
    try {
      DB::beginTransaction();

      $request = $this->find($id);

      // Validate status - must be approved
      if ($request->status !== 'approved') {
        throw new Exception('Solo se puede completar la liquidación de solicitudes aprobadas');
      }

      // Calculate balance to return
      $totalSpent = $data['total_spent'] ?? $request->expenses()->sum('amount');
      $balanceToReturn = $request->total_budget - $totalSpent;

      // Update settlement information
      $request->update([
        'settled' => true,
        'settlement_date' => $data['settlement_date'] ?? now(),
        'total_spent' => $totalSpent,
        'balance_to_return' => $balanceToReturn > 0 ? $balanceToReturn : 0,
      ]);

      DB::commit();
      return $request->fresh(['employee', 'company', 'companyService', 'district', 'policy', 'category', 'expenses']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Generate settlement report PDF
   */
  public function generateSettlementPDF($id)
  {
    $perDiemRequest = $this->find($id);

    // Load all necessary relationships
    $perDiemRequest->load([
      'employee.boss.position',
      'employee.position.area',
      'company',
      'district',
      'category',
      'policy',
      'approvals.approver.position',
      'expenses.expenseType',
    ]);

    $dataResource = new PerDiemRequestResource($perDiemRequest);
    $dataArray = $dataResource->resolve();

    // Agrupar gastos por tipo
    $expensesWithReceipts = collect($dataArray['expenses'] ?? [])->filter(function ($expense) {
      return $expense['receipt_type'] !== 'no_receipt';
    });

    $expensesWithoutReceipts = collect($dataArray['expenses'] ?? [])->filter(function ($expense) {
      return $expense['receipt_type'] === 'no_receipt';
    });

    // Calcular totales
    $totalWithReceipts = $expensesWithReceipts->sum('company_amount');
    $totalWithoutReceipts = $expensesWithoutReceipts->sum('company_amount');
    $totalGeneral = $totalWithReceipts + $totalWithoutReceipts;
    $saldo = ($dataArray['total_budget'] ?? 0) - $totalGeneral;
    
    $pdf = PDF::loadView('reports.gp.gestionhumana.viaticos.settlement', [
      'request' => $dataArray,
      'expensesWithReceipts' => $expensesWithReceipts,
      'expensesWithoutReceipts' => $expensesWithoutReceipts,
      'totalWithReceipts' => $totalWithReceipts,
      'totalWithoutReceipts' => $totalWithoutReceipts,
      'totalGeneral' => $totalGeneral,
      'saldo' => $saldo,
    ]);

    $pdf->setOptions([
      'defaultFont' => 'Arial',
      'isHtml5ParserEnabled' => true,
      'isRemoteEnabled' => false,
      'dpi' => 96,
    ]);

    $pdf->setPaper('A4', 'portrait');

    return $pdf;
  }
}
