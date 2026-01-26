<?php

namespace App\Http\Services\gp\gestionhumana\viaticos;

use App\Http\Resources\gp\gestionhumana\viaticos\ExpenseTypeResource;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemRequestResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\EmailService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Models\gp\gestionhumana\AccountantDistrictAssignment;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionhumana\viaticos\ExpenseType;
use App\Models\gp\gestionhumana\viaticos\HotelReservation;
use App\Models\gp\gestionhumana\viaticos\PerDiemExpense;
use App\Models\gp\gestionhumana\viaticos\PerDiemRequest;
use App\Models\gp\gestionhumana\viaticos\PerDiemRate;
use App\Models\gp\gestionhumana\viaticos\PerDiemPolicy;
use App\Models\gp\gestionhumana\viaticos\PerDiemApproval;
use App\Models\gp\gestionhumana\viaticos\MobilityPayroll;
use App\Models\gp\gestionsistema\DigitalFile;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;

class PerDiemRequestService extends BaseService implements BaseServiceInterface
{
  protected EmailService $emailService;
  protected DigitalFileService $digitalFileService;

  // Configuración de ruta para vouchers de depósito
  private const DEPOSIT_VOUCHER_PATH = '/gp/gestionhumana/viaticos/vouchers/';

  public function __construct(DigitalFileService $digitalFileService, EmailService $emailService)
  {
    $this->digitalFileService = $digitalFileService;
    $this->emailService = $emailService;
  }

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
   * Includes optimized eager loading for budgets with expenses
   */
  public function find($id)
  {
    $perDiemRequest = PerDiemRequest::with([
      'budgets.expenseType',
      'expenses.expenseType' // Eager load expenses for spent calculation
    ])->where('id', $id)->first();
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
        $data['authorizer_id'] = auth()->user()->person->jefe_id;
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
        'sede_service_id' => $data['sede_service_id'],
        'district_id' => $data['district_id'],
        'per_diem_category_id' => $employee->position->per_diem_category_id,
        'start_date' => $data['start_date'],
        'end_date' => $data['end_date'],
        'days_count' => $daysCount,
        'purpose' => $data['purpose'],
        'status' => PerDiemRequest::SETTLEMENT_PENDING,
        'notes' => $data['notes'] ?? null,
        'cash_amount' => 0,
        'transfer_amount' => 0,
        'total_budget' => 0,
        'paid' => false,
        'settled' => false,
        'total_spent' => 0,
        'balance_to_return' => 0,
        'final_result' => "0",
        'with_active' => $data['with_active'] ?? false,
        'with_request' => false,
        'authorizer_id' => $data['authorizer_id'] ?? null,
      ];

      // Create the request
      $request = PerDiemRequest::create($requestData);

      // Get rates for the destination and category from current policy
      $rates = PerDiemRate::getCurrentRatesByDistrict(
        $data['district_id'],
        $employee->position->per_diem_category_id
      );

      // Generate initial budgets (without hotel consideration)
      $totalBudget = $this->generateBudgets($request, $rates);

      // Update total budget
      $request->update(['total_budget' => $totalBudget]);

      // Create approval for employee's boss
      if ($employee->jefe_id) {
        PerDiemApproval::create([
          'per_diem_request_id' => $request->id,
          'approver_id' => $employee->jefe_id,
          'status' => PerDiemApproval::PENDING,
        ]);
      }

      // Send email notifications
      $this->sendPerDiemRequestCreatedEmails($request->fresh(['employee.boss', 'district']));

      DB::commit();
      return new PerDiemRequestResource($request->fresh(['employee', 'company', 'sedeService', 'district', 'policy', 'category', 'budgets.expenseType', 'approvals.approver']));
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
      if (!in_array($request->status, [PerDiemRequest::STATUS_PENDING, PerDiemRequest::STATUS_REJECTED])) {
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
      return new PerDiemRequestResource($request->fresh(['employee', 'company', 'sedeService', 'district', 'policy', 'category']));
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
    if ($request->status !== PerDiemRequest::STATUS_PENDING) {
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
   * Cancel a per diem request
   */
  public function cancel(int $id, array $data): PerDiemRequest
  {
    try {
      DB::beginTransaction();

      $request = $this->find($id);

      // Validate that the request can be cancelled
      if (in_array($request->status, [PerDiemRequest::STATUS_SETTLED, PerDiemRequest::STATUS_CANCELLED])) {
        throw new Exception('No se puede cancelar una solicitud que ya está liquidada o cancelada');
      }

      // Check if there is a hotel reservation associated
      if ($request->hotelReservation()->exists()) {
        throw new Exception('No se puede cancelar la solicitud porque tiene una reserva de hotel asociada');
      }

      // Update request status to cancelled
      $request->update([
        'status' => PerDiemRequest::STATUS_CANCELLED,
        'notes' => isset($data['cancellation_reason'])
          ? ($request->notes ? $request->notes . "\n\nMotivo de cancelación: " . strtoupper($data['cancellation_reason']) : "MOTIVO DE CANCELACIÓN: " . strtoupper($data['cancellation_reason']))
          : $request->notes
      ]);

      // Send cancellation email
      $this->sendPerDiemRequestCancelledEmail(
        $request->fresh(['employee.boss', 'district']),
        $data['cancellation_reason'] ?? ''
      );

      DB::commit();
      return $request->fresh(['employee', 'company', 'sedeService', 'district', 'policy', 'category']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
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
   * Get approval requests for the authenticated user (as approver)
   * @param Request $request
   * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
   */
  public function getPendingApprovals(Request $request)
  {
    $approverId = auth()->user()->person->id;

    // Merge authorizer_id filter
    $request->merge(['authorizer_id' => $approverId]);

    // Handle approval_status filter: 'pending' (default), 'approved', 'all'
    $approvalStatus = $request->query('approval_status', 'pending');
    if ($approvalStatus !== 'all') {
      // Convert approval_status to status filter
      $statusValue = $approvalStatus === 'pending' ? PerDiemApproval::PENDING : $approvalStatus;
      $request->merge(['status' => $statusValue]);
    }

    // Build query with relationships
    $query = PerDiemRequest::query()->with([
      'employee',
      'company',
      'sedeService',
      'district',
      'policy',
    ]);

    return $this->getFilteredResults(
      $query,
      $request,
      PerDiemRequest::filters,
      PerDiemRequest::sorts,
      PerDiemRequestResource::class,
    );
  }

  /**
   * Get pending settlements for the authenticated user (as authorizer)
   * Returns per diem requests with settlement_status 'submitted'
   * that require the user's approval
   */
  public function getPendingSettlements()
  {
    $userId = auth()->user()->person->id;

    $pendingSettlements = PerDiemRequest::where('settlement_status', PerDiemRequest::SETTLEMENT_SUBMITTED)
      ->where('authorizer_id', $userId)
      ->orderBy('settlement_date', 'desc')
      ->get();

    return PerDiemRequestResource::collection($pendingSettlements);
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
      if (!in_array($request->status, [PerDiemRequest::STATUS_PENDING, PerDiemRequest::STATUS_REJECTED])) {
        throw new Exception('Solo se pueden enviar solicitudes en estado pendiente o rechazadas');
      }

      // Create approval record for the employee's boss
      if ($request->employee->jefe_id) {
        // Check if approval already exists
        $existingApproval = PerDiemApproval::where('per_diem_request_id', $request->id)
          ->where('approver_id', $request->employee->jefe_id)
          ->first();

        if (!$existingApproval) {
          PerDiemApproval::create([
            'per_diem_request_id' => $request->id,
            'approver_id' => $request->employee->jefe_id,
            'status' => PerDiemApproval::PENDING,
          ]);
        }
      }

      DB::commit();
      return $request->fresh(['employee', 'company', 'sedeService', 'district', 'policy', 'category', 'approvals.approver']);
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
        $request->update(['status' => PerDiemRequest::STATUS_REJECTED]);
      } elseif ($data['status'] === PerDiemApproval::APPROVED) {
        // Check if all approvals are approved
        $allApproved = $request->approvals()
            ->where('status', '!=', PerDiemApproval::APPROVED)
            ->count() === 0;

        if ($allApproved) {
          // All approvers approved, update request status to approved
          $request->update(['status' => PerDiemRequest::STATUS_APPROVED]);

          // Send approval email to employee
          $this->sendPerDiemRequestApprovedEmail($request->fresh(['employee', 'district']));
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
      if ($request->status !== PerDiemRequest::STATUS_APPROVED) {
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
      return $request->fresh(['employee', 'company', 'sedeService', 'district', 'policy', 'category']);
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

      // Validate status - must be in progress
      if (!in_array($request->status, [PerDiemRequest::STATUS_IN_PROGRESS])) {
        throw new Exception('Solo se puede iniciar liquidación de solicitudes en progreso');
      }

      // Update settlement status to submitted
      $request->update([
        'status' => PerDiemRequest::STATUS_PENDING_SETTLEMENT,
        'settlement_status' => PerDiemRequest::SETTLEMENT_SUBMITTED,
      ]);

      // Send settlement email to employee
      $this->sendPerDiemRequestSettlementEmail($request->fresh(['employee', 'district']));

      DB::commit();
      return $request->fresh(['employee', 'company', 'sedeService', 'district', 'policy', 'category']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Complete settlement
   * Automatically calculates total spent and balance to return from expenses
   */
  public function completeSettlement(int $id, array $data): PerDiemRequest
  {
    try {
      DB::beginTransaction();

      $request = $this->find($id);

      // Validate status - must be approved or in progress
      if (!in_array($request->status, [PerDiemRequest::STATUS_PENDING_SETTLEMENT])) {
        throw new Exception('Solo se puede completar la liquidación de solicitudes aprobadas o en progreso');
      }

      // Calculate total spent from all non-rejected expenses (company_amount)
      $totalSpent = $request->expenses()
        ->where('rejected', false)
        ->sum('company_amount');

      // Calculate balance to return
      $balanceToReturn = $request->total_budget - $totalSpent;

      // Update settlement information
      $request->update([
        'settled' => true,
        'settlement_status' => PerDiemRequest::SETTLEMENT_COMPLETED,
        'status' => PerDiemRequest::STATUS_SETTLED,
        'settlement_date' => now(),
        'total_spent' => $totalSpent,
        'balance_to_return' => max($balanceToReturn, 0),
      ]);

      // Add comments to notes if provided
      if (!empty($data['comments'])) {
        $currentNotes = $request->notes ?? '';
        $newNotes = $currentNotes ? $currentNotes . "\n\nCOMENTARIOS DE LIQUIDACIÓN: " . strtoupper($data['comments']) : "COMENTARIOS DE LIQUIDACIÓN: " . strtoupper($data['comments']);
        $request->update(['notes' => $newNotes]);
      }

      // Send settlement completed email to employee
      $this->sendPerDiemRequestSettledEmail($request->fresh(['employee', 'district']));

      DB::commit();
      return $request->fresh(['employee', 'company', 'sedeService', 'district', 'policy', 'category', 'expenses']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Approve settlement
   * Can only be approved by the boss at this stage
   */
  public function approveSettlement(int $id, array $data): PerDiemRequest
  {
    try {
      DB::beginTransaction();

      $request = $this->find($id);
      $currentUserId = auth()->user()->person->id ?? null;

      if ($request->settlement_status != PerDiemRequest::SETTLEMENT_SUBMITTED) {
        throw new Exception('Solo se pueden aprobar liquidaciones que han sido enviadas para revisión');
      }

      if ($request->settlement_status === PerDiemRequest::SETTLEMENT_SUBMITTED) {
        if ($currentUserId !== $request->authorizer_id) {
          throw new Exception('La aprobación de la liquidación debe ser realizada por el jefe directo');
        }

        $request->update([
          'settlement_status' => PerDiemRequest::SETTLEMENT_APPROVED,
        ]);

        $approvalNote = "LIQUIDACIÓN APROBADA POR JEFE DIRECTO";
      }

      // If there are comments, add them to notes
      $currentNotes = $request->notes ?? '';
      $newNotes = $currentNotes ? $currentNotes . "\n\n" . $approvalNote : $approvalNote;

      if (!empty($data['comments'])) {
        $newNotes .= " - COMENTARIOS: " . strtoupper($data['comments']);
      }

      $request->update(['notes' => $newNotes]);

      DB::commit();
      return $request->fresh(['employee', 'company', 'sedeService', 'district', 'policy', 'category', 'expenses']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Reject settlement
   * Can be rejected at any approval level (by boss or module)
   */
  public function rejectSettlement(int $id, array $data): PerDiemRequest
  {
    try {
      DB::beginTransaction();

      $request = $this->find($id);

      // Validate that settlement has been submitted or approved by boss
      if ($request->settlement_status != PerDiemRequest::SETTLEMENT_SUBMITTED) {
        throw new Exception('Solo se pueden rechazar liquidaciones que están pendientes de aprobación');
      }

      // Update settlement status to rejected
      $request->update([
        'settlement_status' => PerDiemRequest::SETTLEMENT_REJECTED,
        'settled' => false,
      ]);

      // Add rejection reason to notes
      $currentNotes = $request->notes ?? '';
      $rejectionNote = "MOTIVO DE RECHAZO DE LIQUIDACIÓN: " . strtoupper($data['rejection_reason']);
      $newNotes = $currentNotes ? $currentNotes . "\n\n" . $rejectionNote : $rejectionNote;
      $request->update(['notes' => $newNotes]);

      DB::commit();
      return $request->fresh(['employee', 'company', 'sedeService', 'district', 'policy', 'category', 'expenses']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Confirm a per diem request and recalculate budgets
   *
   * @param int $id Per diem request ID
   * @return PerDiemRequestResource
   * @throws Exception
   */
  public function confirm(int $id): PerDiemRequestResource
  {
    try {
      DB::beginTransaction();

      $request = $this->find($id);

      // Validate status is 'approved'
      if ($request->status !== PerDiemRequest::SETTLEMENT_APPROVED) {
        throw new Exception('Solo se pueden confirmar solicitudes aprobadas');
      }

      // Change status to 'in_progress'
      $request->update(['status' => PerDiemRequest::STATUS_IN_PROGRESS]);

      // Delete existing budgets
      $request->budgets()->delete();

      // Get rates again
      $rates = PerDiemRate::getCurrentRatesByDistrict(
        $request->district_id,
        $request->per_diem_category_id
      );

      // Load hotel reservation if exists
      $hotelReservation = $request->hotelReservation()->with('hotelAgreement')->first();

      // Regenerate budgets with hotel consideration
      $totalBudget = $this->generateBudgets($request, $rates);

      // Update total budget
      $request->update(['total_budget' => $totalBudget]);

      // Send in-progress email ONLY if no hotel reservation exists
      if (!$request->hotelReservation()->exists()) {
        $this->sendPerDiemInProgressEmail($request->fresh(['employee', 'district']));
      }

      DB::commit();

      return new PerDiemRequestResource(
        $request->fresh([
          'employee',
          'company',
          'sedeService',
          'district',
          'policy',
          'category',
          'budgets.expenseType',
          'hotelReservation.hotelAgreement'
        ])
      );
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function confirmProgress(int $id): PerDiemRequestResource
  {
    $request = $this->find($id);
    $request->update(['status' => PerDiemRequest::STATUS_IN_PROGRESS]);

    return new PerDiemRequestResource($request->fresh([
      'employee',
      'company',
      'sedeService',
      'district',
      'policy',
      'category',
      'budgets.expenseType',
      'hotelReservation.hotelAgreement'
    ]));
  }


  /**
   * Get available expense types for a per diem request
   * Returns expense types that have budgets assigned to the request
   * Also includes TRANSPORTATION if less than 2 transportation expenses exist
   *
   * @param int $id Per diem request ID
   * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
   * @throws Exception
   */
  public function getAvailableExpenseTypes(int $id)
  {
    $request = $this->find($id);

    // Get all expense types from budgets
    $expenseTypeIds = $request->budgets()->pluck('expense_type_id')->unique()->toArray();

    // Check if TRANSPORTATION should be available
    // Count existing transportation expenses (excluding rejected ones)
    $transportationExpensesCount = PerDiemExpense::where('per_diem_request_id', $id)
      ->where('expense_type_id', ExpenseType::TRANSPORTATION_ID)
      ->where('rejected', false)
      ->count();

    // Add TRANSPORTATION to available types if less than 2 expenses exist
    if ($transportationExpensesCount < 2 && !in_array(ExpenseType::TRANSPORTATION_ID, $expenseTypeIds)) {
      $expenseTypeIds[] = ExpenseType::TRANSPORTATION_ID;
    }

    // Add TOLLS and GASOLINE if with_active is true
    if ($request->with_active) {
      if (!in_array(ExpenseType::TOLLS_ID, $expenseTypeIds)) {
        $expenseTypeIds[] = ExpenseType::TOLLS_ID;
      }
      if (!in_array(ExpenseType::GASOLINE_ID, $expenseTypeIds)) {
        $expenseTypeIds[] = ExpenseType::GASOLINE_ID;
      }
    }

    // Add AIRFARE only for managers (category 1)
    if ($request->per_diem_category_id === 1 && !in_array(ExpenseType::AIRFARE_ID, $expenseTypeIds)) {
      $expenseTypeIds[] = ExpenseType::AIRFARE_ID;
    }

    // Replace parent expense types with their children (leaf nodes only)
    $finalExpenseTypeIds = [];
    foreach ($expenseTypeIds as $expenseTypeId) {
      $expenseType = ExpenseType::with('children')->find($expenseTypeId);

      if ($expenseType) {
        // If the expense type has children, add the children instead of the parent
        if ($expenseType->children->isNotEmpty()) {
          $childrenIds = $expenseType->children->pluck('id')->toArray();
          $finalExpenseTypeIds = array_merge($finalExpenseTypeIds, $childrenIds);
        } else {
          // If no children, add the expense type itself (it's already a leaf node)
          $finalExpenseTypeIds[] = $expenseTypeId;
        }
      }
    }

    // Remove duplicates
    $finalExpenseTypeIds = array_unique($finalExpenseTypeIds);

    // Handle meals based on hotel reservation
    $hasMealTypes = !empty(array_intersect($finalExpenseTypeIds, [
      ExpenseType::BREAKFAST_ID,
      ExpenseType::LUNCH_ID,
      ExpenseType::DINNER_ID
    ]));

    if ($hasMealTypes) {
      $hotelReservation = $request->hotelReservation()->with('hotelAgreement')->first();

      // If hotel exists with agreement, remove meals that are included
      if ($request->days_count > 1 && $hotelReservation?->hotelAgreement) {
        $agreement = $hotelReservation->hotelAgreement;

        if ($agreement->includes_breakfast) {
          $finalExpenseTypeIds = array_diff($finalExpenseTypeIds, [ExpenseType::BREAKFAST_ID]);
        }
        if ($agreement->includes_lunch) {
          $finalExpenseTypeIds = array_diff($finalExpenseTypeIds, [ExpenseType::LUNCH_ID]);
        }
        if ($agreement->includes_dinner) {
          $finalExpenseTypeIds = array_diff($finalExpenseTypeIds, [ExpenseType::DINNER_ID]);
        }
      }
    }

    // Get expense types with parent relation
    $expenseTypes = ExpenseType::whereIn('id', $finalExpenseTypeIds)
      ->with('parent')
      ->orderBy('order')
      ->get();

    return ExpenseTypeResource::collection($expenseTypes);
  }

  /**
   * Get available budgets for a per diem request
   * Shows budget, spent, and available amounts for each expense type
   * Excludes TRANSPORTATION from response
   *
   * @param int $id Per diem request ID
   * @return array
   * @throws Exception
   */
  public function getAvailableBudgets(int $id): array
  {
    $request = $this->find($id);

    // Get all budgets except TRANSPORTATION
    $budgets = $request->budgets()
      ->with('expenseType')
      ->where('expense_type_id', '!=', ExpenseType::TRANSPORTATION_ID)
      ->get();

    $budgetData = [];

    foreach ($budgets as $budget) {
      // Calculate total spent for this expense type (excluding rejected expenses)
      $totalSpent = PerDiemExpense::where('per_diem_request_id', $request->id)
        ->where('expense_type_id', $budget->expense_type_id)
        ->where('rejected', false)
        ->sum('company_amount');

      $available = max(0, $budget->total - $totalSpent);

      $budgetData[] = [
        'expense_type_id' => $budget->expense_type_id,
        'expense_type_name' => $budget->expenseType->name,
        'expense_type_code' => $budget->expenseType->code,
        'daily_amount' => (float)$budget->daily_amount,
        'days' => $budget->days,
        'total_budget' => (float)$budget->total,
        'amount_spent' => (float)$totalSpent,
        'amount_available' => (float)$available,
        'is_over_budget' => $totalSpent > $budget->total,
        'percentage_spent' => $budget->total > 0
          ? round(($totalSpent / $budget->total) * 100, 2)
          : 0,
      ];
    }

    // Calculate overall totals
    $overallTotalBudget = collect($budgetData)->sum('total_budget');
    $overallTotalSpent = collect($budgetData)->sum('amount_spent');
    $overallAvailable = collect($budgetData)->sum('amount_available');

    return [
      'per_diem_request_id' => $request->id,
      'per_diem_request_code' => $request->code,
      'status' => $request->status,
      'budgets' => $budgetData,
      'summary' => [
        'total_budget' => (float)$overallTotalBudget,
        'total_spent' => (float)$overallTotalSpent,
        'total_available' => (float)$overallAvailable,
        'percentage_spent' => $overallTotalBudget > 0
          ? round(($overallTotalSpent / $overallTotalBudget) * 100, 2)
          : 0,
      ]
    ];
  }

  /**
   * Generate expenseTotal report PDF with detailed expense breakdown
   * Shows all expenses grouped by dynamic categories based on expense_type parent
   */
  public function generateExpenseTotalPDF($id)
  {
    $perDiemRequest = $this->find($id);

    // Load all necessary relationships
    $perDiemRequest->load([
      'employee.boss.position',
      'employee.position.area',
      'company',
      'sedeService',
      'district',
      'category',
      'policy',
      'approvals.approver.position',
      'expenses.expenseType',
    ]);

    // Separar gastos por quien los asume (empresa vs colaborador)
    $gastosEmpresa = $perDiemRequest->expenses->filter(function ($expense) {
      return $expense->is_company_expense === true && $expense->validated == 1;
    });

    $gastosColaborador = $perDiemRequest->expenses->filter(function ($expense) {
      return $expense->is_company_expense === false && $expense->validated == 1;
    });

    // ===== GASTOS DE LA EMPRESA =====
    // Agrupar gastos por parent (si tienen parent, agrupar por parent_id, si no por expense_type_id)
    $empresaExpensesByParent = [];
    foreach ($gastosEmpresa as $expense) {
      if (!$expense->expense_type_id) continue;

      $expenseType = $expense->expenseType;
      if (!$expenseType) continue;

      // Si tiene padre, agrupar por el ID del padre
      if ($expenseType->parent_id) {
        $parentId = $expenseType->parent_id;
        if (!isset($empresaExpensesByParent[$parentId])) {
          $empresaExpensesByParent[$parentId] = [];
        }
        $empresaExpensesByParent[$parentId][] = $expense;
      } else {
        // Si no tiene padre, agrupar por su propio ID
        $typeId = $expense->expense_type_id;
        if (!isset($empresaExpensesByParent[$typeId])) {
          $empresaExpensesByParent[$typeId] = [];
        }
        $empresaExpensesByParent[$typeId][] = $expense;
      }
    }

    // Preparar array de categorías con sus gastos y totales para empresa
    $empresaCategories = [];
    foreach ($empresaExpensesByParent as $parentTypeId => $expenses) {
      $expensesCollection = collect($expenses);

      // Obtener el tipo de gasto padre (puede ser el mismo si no tiene padre)
      $parentType = ExpenseType::find($parentTypeId);
      if (!$parentType) continue;

      $typeName = $parentType->name ?? 'Sin categoría';

      // Convertir los gastos a array para la vista
      $expensesArray = $expensesCollection->map(function ($expense) {
        // Concatenar el tipo de gasto con las notas
        $expenseTypeName = $expense->expenseType->name ?? '';
        $notes = $expense->notes ?? '';
        $detalle = $expenseTypeName . ($notes ? ' - ' . $notes : '');

        return [
          'expense_date' => $expense->expense_date,
          'receipt_number' => $expense->receipt_number,
          'business_name' => $expense->business_name . ($expense->ruc ? ' (RUC: ' . $expense->ruc . ')' : ''),
          'detalle' => $detalle,
          'receipt_amount' => $expense->receipt_amount,
          'company_amount' => $expense->company_amount,
          'employee_amount' => $expense->employee_amount,
        ];
      });

      $empresaCategories[] = [
        'type_id' => $parentTypeId,
        'type_name' => $typeName,
        'expenses' => $expensesArray,
        'total_receipt' => $expensesCollection->sum('receipt_amount'),
        'total_company' => $expensesCollection->sum('company_amount'),
        'total_employee' => $expensesCollection->sum('employee_amount'),
      ];
    }

    // ===== GASTOS DEL COLABORADOR =====
    // Agrupar gastos por parent (si tienen parent, agrupar por parent_id, si no por expense_type_id)
    $colaboradorExpensesByParent = [];
    foreach ($gastosColaborador as $expense) {
      if (!$expense->expense_type_id) continue;

      $expenseType = $expense->expenseType;
      if (!$expenseType) continue;

      // Si tiene padre, agrupar por el ID del padre
      if ($expenseType->parent_id) {
        $parentId = $expenseType->parent_id;
        if (!isset($colaboradorExpensesByParent[$parentId])) {
          $colaboradorExpensesByParent[$parentId] = [];
        }
        $colaboradorExpensesByParent[$parentId][] = $expense;
      } else {
        // Si no tiene padre, agrupar por su propio ID
        $typeId = $expense->expense_type_id;
        if (!isset($colaboradorExpensesByParent[$typeId])) {
          $colaboradorExpensesByParent[$typeId] = [];
        }
        $colaboradorExpensesByParent[$typeId][] = $expense;
      }
    }

    // Preparar array de categorías con sus gastos y totales para colaborador
    $colaboradorCategories = [];
    foreach ($colaboradorExpensesByParent as $parentTypeId => $expenses) {
      $expensesCollection = collect($expenses);

      // Obtener el tipo de gasto padre (puede ser el mismo si no tiene padre)
      $parentType = ExpenseType::find($parentTypeId);
      if (!$parentType) continue;

      $typeName = $parentType->name ?? 'Sin categoría';

      // Convertir los gastos a array para la vista
      $expensesArray = $expensesCollection->map(function ($expense) {
        // Concatenar el tipo de gasto con las notas
        $expenseTypeName = $expense->expenseType->name ?? '';
        $notes = $expense->notes ?? '';
        $detalle = $expenseTypeName . ($notes ? ' - ' . $notes : '');

        return [
          'expense_date' => $expense->expense_date,
          'receipt_number' => $expense->receipt_number,
          'business_name' => $expense->business_name . ($expense->ruc ? ' (RUC: ' . $expense->ruc . ')' : ''),
          'detalle' => $detalle,
          'receipt_amount' => $expense->receipt_amount,
          'company_amount' => $expense->company_amount,
          'employee_amount' => $expense->employee_amount,
        ];
      });

      $colaboradorCategories[] = [
        'type_id' => $parentTypeId,
        'type_name' => $typeName,
        'expenses' => $expensesArray,
        'total_receipt' => $expensesCollection->sum('receipt_amount'),
        'total_company' => $expensesCollection->sum('company_amount'),
        'total_employee' => $expensesCollection->sum('employee_amount'),
      ];
    }

    // Totales de gastos de la empresa (3 columnas)
    $totalEmpresaReceipt = $gastosEmpresa->sum('receipt_amount');
    $totalEmpresaCompany = $gastosEmpresa->sum('company_amount');
    $totalEmpresaEmployee = $gastosEmpresa->sum('employee_amount');

    // Totales de gastos del colaborador (3 columnas)
    $totalColaboradorReceipt = $gastosColaborador->sum('receipt_amount');
    $totalColaboradorCompany = $gastosColaborador->sum('company_amount');
    $totalColaboradorEmployee = $gastosColaborador->sum('employee_amount');

    // Totales generales (suma de empresa + colaborador)
    $totalGeneralReceipt = $totalEmpresaReceipt + $totalColaboradorReceipt;
    $totalGeneralCompany = $totalEmpresaCompany + $totalColaboradorCompany;
    $totalGeneralEmployee = $totalEmpresaEmployee + $totalColaboradorEmployee;

    // Preparar datos de la solicitud para la vista
    $dataResource = new PerDiemRequestResource($perDiemRequest);
    $dataArray = $dataResource->resolve();

    // Calcular importes de pie de página
    $importeOtorgado = $dataArray['cash_amount'] ?? 0;
    // El monto a devolver se calcula sobre lo cubierto por la empresa del colaborador
    $montoDevolver = $importeOtorgado - $totalColaboradorCompany;

    $pdf = PDF::loadView('reports.gp.gestionhumana.viaticos.settlement', [
      'request' => $dataArray,
      // Gastos de la empresa (categorías dinámicas)
      'empresaCategories' => $empresaCategories,
      'totalEmpresaReceipt' => $totalEmpresaReceipt,
      'totalEmpresaCompany' => $totalEmpresaCompany,
      'totalEmpresaEmployee' => $totalEmpresaEmployee,
      // Gastos del colaborador (categorías dinámicas)
      'colaboradorCategories' => $colaboradorCategories,
      'totalColaboradorReceipt' => $totalColaboradorReceipt,
      'totalColaboradorCompany' => $totalColaboradorCompany,
      'totalColaboradorEmployee' => $totalColaboradorEmployee,
      // Totales generales
      'totalGeneralReceipt' => $totalGeneralReceipt,
      'totalGeneralCompany' => $totalGeneralCompany,
      'totalGeneralEmployee' => $totalGeneralEmployee,
      'importeOtorgado' => $importeOtorgado,
      'montoDevolver' => $montoDevolver,
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

  /**
   * Generate expenseTotal report PDF with evidence/receipts
   * Same as generateExpenseTotalPDF but includes receipt images/PDFs for each expense
   */
  public function generateExpenseTotalWithEvidencePDF($id)
  {
    $perDiemRequest = $this->find($id);

    if (!$perDiemRequest->settled) {
      throw new Exception('No se puede generar el reporte de gastos con evidencias para una solicitud no ha sido liquidada.');
    }

    // Load all necessary relationships
    $perDiemRequest->load([
      'employee.boss.position',
      'employee.position.area',
      'company',
      'sedeService',
      'district',
      'category',
      'policy',
      'approvals.approver.position',
      'expenses.expenseType',
    ]);

    // Separar gastos por quien los asume (empresa vs colaborador)
    $gastosEmpresa = $perDiemRequest->expenses->filter(function ($expense) {
      return $expense->is_company_expense === true;
    });

    $gastosColaborador = $perDiemRequest->expenses->filter(function ($expense) {
      return $expense->is_company_expense === false;
    });

    // ===== GASTOS DE LA EMPRESA =====
    // Agrupar gastos por parent (si tienen parent, agrupar por parent_id, si no por expense_type_id)
    $empresaExpensesByParent = [];
    foreach ($gastosEmpresa as $expense) {
      if (!$expense->expense_type_id) continue;

      $expenseType = $expense->expenseType;
      if (!$expenseType) continue;

      // Si tiene padre, agrupar por el ID del padre
      if ($expenseType->parent_id) {
        $parentId = $expenseType->parent_id;
        if (!isset($empresaExpensesByParent[$parentId])) {
          $empresaExpensesByParent[$parentId] = [];
        }
        $empresaExpensesByParent[$parentId][] = $expense;
      } else {
        // Si no tiene padre, agrupar por su propio ID
        $typeId = $expense->expense_type_id;
        if (!isset($empresaExpensesByParent[$typeId])) {
          $empresaExpensesByParent[$typeId] = [];
        }
        $empresaExpensesByParent[$typeId][] = $expense;
      }
    }

    // Preparar array de categorías con sus gastos y totales para empresa
    $empresaCategories = [];
    foreach ($empresaExpensesByParent as $parentTypeId => $expenses) {
      $expensesCollection = collect($expenses);

      // Obtener el tipo de gasto padre (puede ser el mismo si no tiene padre)
      $parentType = ExpenseType::find($parentTypeId);
      if (!$parentType) continue;

      $typeName = $parentType->name ?? 'Sin categoría';

      // Convertir los gastos a array para la vista (INCLUIR receipt_path)
      $expensesArray = $expensesCollection->map(function ($expense) {
        // Concatenar el tipo de gasto con las notas
        $expenseTypeName = $expense->expenseType->name ?? '';
        $notes = $expense->notes ?? '';
        $detalle = $expenseTypeName . ($notes ? ' - ' . $notes : '');

        return [
          'expense_date' => $expense->expense_date,
          'receipt_number' => $expense->receipt_number,
          'business_name' => $expense->business_name,
          'detalle' => $detalle,
          'receipt_amount' => $expense->receipt_amount,
          'company_amount' => $expense->company_amount,
          'employee_amount' => $expense->employee_amount,
          'receipt_path' => $expense->receipt_path, // Ruta de la evidencia
        ];
      });

      $empresaCategories[] = [
        'type_id' => $parentTypeId,
        'type_name' => $typeName,
        'expenses' => $expensesArray,
        'total_receipt' => $expensesCollection->sum('receipt_amount'),
        'total_company' => $expensesCollection->sum('company_amount'),
        'total_employee' => $expensesCollection->sum('employee_amount'),
      ];
    }

    // ===== GASTOS DEL COLABORADOR =====
    // Agrupar gastos por parent (si tienen parent, agrupar por parent_id, si no por expense_type_id)
    $colaboradorExpensesByParent = [];
    foreach ($gastosColaborador as $expense) {
      if (!$expense->expense_type_id) continue;

      $expenseType = $expense->expenseType;
      if (!$expenseType) continue;

      // Si tiene padre, agrupar por el ID del padre
      if ($expenseType->parent_id) {
        $parentId = $expenseType->parent_id;
        if (!isset($colaboradorExpensesByParent[$parentId])) {
          $colaboradorExpensesByParent[$parentId] = [];
        }
        $colaboradorExpensesByParent[$parentId][] = $expense;
      } else {
        // Si no tiene padre, agrupar por su propio ID
        $typeId = $expense->expense_type_id;
        if (!isset($colaboradorExpensesByParent[$typeId])) {
          $colaboradorExpensesByParent[$typeId] = [];
        }
        $colaboradorExpensesByParent[$typeId][] = $expense;
      }
    }

    // Preparar array de categorías con sus gastos y totales para colaborador
    $colaboradorCategories = [];
    foreach ($colaboradorExpensesByParent as $parentTypeId => $expenses) {
      $expensesCollection = collect($expenses);

      // Obtener el tipo de gasto padre (puede ser el mismo si no tiene padre)
      $parentType = ExpenseType::find($parentTypeId);
      if (!$parentType) continue;

      $typeName = $parentType->name ?? 'Sin categoría';

      // Convertir los gastos a array para la vista (INCLUIR receipt_path)
      $expensesArray = $expensesCollection->map(function ($expense) {
        // Concatenar el tipo de gasto con las notas
        $expenseTypeName = $expense->expenseType->name ?? '';
        $notes = $expense->notes ?? '';
        $detalle = $expenseTypeName . ($notes ? ' - ' . $notes : '');

        return [
          'expense_date' => $expense->expense_date,
          'receipt_number' => $expense->receipt_number,
          'business_name' => $expense->business_name,
          'detalle' => $detalle,
          'receipt_amount' => $expense->receipt_amount,
          'company_amount' => $expense->company_amount,
          'employee_amount' => $expense->employee_amount,
          'receipt_path' => $expense->receipt_path, // Ruta de la evidencia
        ];
      });

      $colaboradorCategories[] = [
        'type_id' => $parentTypeId,
        'type_name' => $typeName,
        'expenses' => $expensesArray,
        'total_receipt' => $expensesCollection->sum('receipt_amount'),
        'total_company' => $expensesCollection->sum('company_amount'),
        'total_employee' => $expensesCollection->sum('employee_amount'),
      ];
    }

    // Totales de gastos de la empresa (3 columnas)
    $totalEmpresaReceipt = $gastosEmpresa->sum('receipt_amount');
    $totalEmpresaCompany = $gastosEmpresa->sum('company_amount');
    $totalEmpresaEmployee = $gastosEmpresa->sum('employee_amount');

    // Totales de gastos del colaborador (3 columnas)
    $totalColaboradorReceipt = $gastosColaborador->sum('receipt_amount');
    $totalColaboradorCompany = $gastosColaborador->sum('company_amount');
    $totalColaboradorEmployee = $gastosColaborador->sum('employee_amount');

    // Totales generales (suma de empresa + colaborador)
    $totalGeneralReceipt = $totalEmpresaReceipt + $totalColaboradorReceipt;
    $totalGeneralCompany = $totalEmpresaCompany + $totalColaboradorCompany;
    $totalGeneralEmployee = $totalEmpresaEmployee + $totalColaboradorEmployee;

    // Preparar datos de la solicitud para la vista
    $dataResource = new PerDiemRequestResource($perDiemRequest);
    $dataArray = $dataResource->resolve();

    // Calcular importes de pie de página
    $importeOtorgado = $dataArray['cash_amount'] ?? 0;
    // El monto a devolver se calcula sobre lo cubierto por la empresa del colaborador
    $montoDevolver = $importeOtorgado - $totalColaboradorCompany;

    $pdf = PDF::loadView('reports.gp.gestionhumana.viaticos.settlement-with-evidence', [
      'request' => $dataArray,
      // Gastos de la empresa (categorías dinámicas)
      'empresaCategories' => $empresaCategories,
      'totalEmpresaReceipt' => $totalEmpresaReceipt,
      'totalEmpresaCompany' => $totalEmpresaCompany,
      'totalEmpresaEmployee' => $totalEmpresaEmployee,
      // Gastos del colaborador (categorías dinámicas)
      'colaboradorCategories' => $colaboradorCategories,
      'totalColaboradorReceipt' => $totalColaboradorReceipt,
      'totalColaboradorCompany' => $totalColaboradorCompany,
      'totalColaboradorEmployee' => $totalColaboradorEmployee,
      // Totales generales
      'totalGeneralReceipt' => $totalGeneralReceipt,
      'totalGeneralCompany' => $totalGeneralCompany,
      'totalGeneralEmployee' => $totalGeneralEmployee,
      'importeOtorgado' => $importeOtorgado,
      'montoDevolver' => $montoDevolver,
    ]);

    $pdf->setOptions([
      'defaultFont' => 'Arial',
      'isHtml5ParserEnabled' => true,
      'isRemoteEnabled' => true, // IMPORTANTE: Activar para cargar imágenes remotas
      'dpi' => 96,
    ]);

    $pdf->setPaper('A4', 'portrait');

    return $pdf;
  }

  /**
   * Generate expense detail report PDF showing only employee expenses
   * Shows expenses paid by the employee (not company expenses)
   */
  public function generateExpenseDetailPDF($id)
  {
    $perDiemRequest = $this->find($id);

    // Load all necessary relationships
    $perDiemRequest->load([
      'employee.boss.position',
      'employee.position.area',
      'company',
      'sedeService',
      'district',
      'category',
      'policy',
      'approvals.approver.position',
      'expenses.expenseType',
    ]);

    // Obtener solo los gastos del personal (no asumidos por la empresa) directamente del modelo
    $gastosColaborador = $perDiemRequest->expenses->filter(function ($expense) {
      return !$expense->is_company_expense && $expense->validated == 1;
    });

    // Agrupar gastos por parent (si tienen parent, agrupar por parent_id, si no por expense_type_id)
    $expensesByParent = [];

    foreach ($gastosColaborador as $expense) {
      if (!$expense->expense_type_id) continue;

      $expenseType = $expense->expenseType;
      if (!$expenseType) continue;

      // Si tiene padre, agrupar por el ID del padre
      if ($expenseType->parent_id) {
        $parentId = $expenseType->parent_id;
        if (!isset($expensesByParent[$parentId])) {
          $expensesByParent[$parentId] = [];
        }
        $expensesByParent[$parentId][] = $expense;
      } else {
        // Si no tiene padre, agrupar por su propio ID
        $typeId = $expense->expense_type_id;
        if (!isset($expensesByParent[$typeId])) {
          $expensesByParent[$typeId] = [];
        }
        $expensesByParent[$typeId][] = $expense;
      }
    }

    // Preparar array de categorías con sus gastos y totales
    $expenseCategories = [];
    foreach ($expensesByParent as $parentTypeId => $expenses) {
      $expensesCollection = collect($expenses);

      // Obtener el tipo de gasto padre (puede ser el mismo si no tiene padre)
      $parentType = ExpenseType::find($parentTypeId);
      if (!$parentType) continue;

      $typeName = $parentType->name ?? 'Sin categoría';

      // Convertir los gastos a array para la vista
      $expensesArray = $expensesCollection->map(function ($expense) {
        // Concatenar el tipo de gasto con las notas
        $expenseTypeName = $expense->expenseType->name ?? '';
        $notes = $expense->notes ?? '';
        $detalle = $expenseTypeName . ($notes ? ' - ' . $notes : '');

        return [
          'expense_date' => $expense->expense_date,
          'receipt_number' => $expense->receipt_number,
          'business_name' => $expense->business_name . ($expense->ruc ? ' (RUC: ' . $expense->ruc . ')' : ''),
          'detalle' => $detalle,
          'receipt_amount' => $expense->receipt_amount,
          'company_amount' => $expense->company_amount,
          'employee_amount' => $expense->employee_amount,
        ];
      });

      $expenseCategories[] = [
        'type_id' => $parentTypeId,
        'type_name' => $typeName,
        'expenses' => $expensesArray,
        'total_receipt' => $expensesCollection->sum('receipt_amount'),
        'total_company' => $expensesCollection->sum('company_amount'),
        'total_employee' => $expensesCollection->sum('employee_amount'),
      ];
    }

    // Totales generales
    $totalGeneralReceipt = $gastosColaborador->sum('receipt_amount');
    $totalGeneralCompany = $gastosColaborador->sum('company_amount');
    $totalGeneralEmployee = $gastosColaborador->sum('employee_amount');

    // Preparar datos de la solicitud para la vista
    $dataResource = new PerDiemRequestResource($perDiemRequest);
    $dataArray = $dataResource->resolve();

    // Calcular importes de pie de página
    $importeOtorgado = $dataArray['cash_amount'] ?? 0;
    $montoDevolver = $importeOtorgado - $totalGeneralCompany;

    $pdf = PDF::loadView('reports.gp.gestionhumana.viaticos.expense-detail', [
      'request' => $dataArray,
      'expenseCategories' => $expenseCategories,
      // Totales Generales
      'totalGeneralReceipt' => $totalGeneralReceipt,
      'totalGeneralCompany' => $totalGeneralCompany,
      'totalGeneralEmployee' => $totalGeneralEmployee,
      // Importes
      'importeOtorgado' => $importeOtorgado,
      'montoDevolver' => $montoDevolver,
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

  /**
   * Generate budgets for a per diem request
   *
   * @param PerDiemRequest $request The per diem request
   * @param Collection $rates Collection of PerDiemRate objects for the district/category
   * @param HotelReservation|null $hotelReservation Optional hotel reservation to consider meal inclusions
   * @return float Total budget amount
   */
  private function generateBudgets(
    PerDiemRequest $request,
    Collection     $rates,
  ): float
  {
    $totalBudget = 0;
    $daysCount = $request->days_count;
    $withActive = $request->with_active;

    foreach ($rates as $rate) {
      switch ($rate->expense_type_id) {
        case ExpenseType::MEALS_ID:
          $totalBudget += $this->generateMealBudgets($request, $rate, $daysCount);
          break;

        case ExpenseType::ACCOMMODATION_ID:
          // No budget generated - depends on GH
          break;

        case ExpenseType::LOCAL_TRANSPORT_ID:
          if (!$withActive) {
            $totalBudget += $this->generateLocalTransportBudget($request, $rate, $daysCount);
          }
          break;

        case ExpenseType::TRANSPORTATION_ID:
          // No budget generated - depends on agency
          break;
      }
    }

    return $totalBudget;
  }

  /**
   * Generate budgets for meals (breakfast, lunch, dinner)
   * Distributes MEALS amount across three meals: 30%, 40%, 30%
   * Omits meals included in hotel agreement
   *
   * @param PerDiemRequest $request
   * @param PerDiemRate $mealsRate The parent MEALS rate
   * @param int $daysCount
   * @param HotelReservation|null $hotelReservation
   * @return float Total meal budget added
   */
  private function generateMealBudgets(
    PerDiemRequest $request,
    PerDiemRate    $mealsRate,
    int            $daysCount,
  ): float
  {
    $totalMealsDaily = $mealsRate->daily_amount;
    // Create breakfast budget if not included in hotel

    $totalMealBudget = $totalMealsDaily * $daysCount;
    $request->budgets()->create([
      'expense_type_id' => ExpenseType::MEALS_ID,
      'daily_amount' => $totalMealsDaily,
      'days' => $daysCount,
      'total' => $totalMealBudget,
    ]);

    return $totalMealBudget;
  }

  /**
   * Generate budget for accommodation
   * Creates a budget entry with total = 0 for tracking purposes (depends on GH)
   *
   * @param PerDiemRequest $request
   * @return void
   */
  private function generateAccommodationBudget(PerDiemRequest $request): void
  {
    $request->budgets()->create([
      'expense_type_id' => ExpenseType::ACCOMMODATION_ID,
      'daily_amount' => 0,
      'days' => 0,
      'total' => 0,
    ]);
  }

  /**
   * Generate budget for local transport
   *
   * @param PerDiemRequest $request
   * @param PerDiemRate $rate
   * @param int $daysCount
   * @return float Total local transport budget
   */
  private function generateLocalTransportBudget(
    PerDiemRequest $request,
    PerDiemRate    $rate,
    int            $daysCount
  ): float
  {
    $total = $rate->daily_amount * $daysCount;

    $request->budgets()->create([
      'expense_type_id' => ExpenseType::LOCAL_TRANSPORT_ID,
      'daily_amount' => $rate->daily_amount,
      'days' => $daysCount,
      'total' => $total,
    ]);

    return $total;
  }

  /**
   * Generate budget for transportation (pasajes)
   * Creates a budget entry with total = 0 for tracking purposes
   *
   * @param PerDiemRequest $request
   * @return void
   */
  private function generateTransportationBudget(PerDiemRequest $request): void
  {
    $request->budgets()->create([
      'expense_type_id' => ExpenseType::TRANSPORTATION_ID,
      'daily_amount' => 0,
      'days' => 0,
      'total' => 0,
    ]);
  }

  /**
   * Upload deposit voucher for a per diem request
   * This is used when with_request is true to upload proof of deposit
   *
   * @param int $id Per diem request ID
   * @param UploadedFile $voucherFile The voucher file (photo or document)
   * @return PerDiemRequestResource
   * @throws Exception
   */
  public function agregarDeposito(int $id, UploadedFile $voucherFile): PerDiemRequestResource
  {
    try {
      DB::beginTransaction();

      $request = $this->find($id);

      // Delete old voucher if exists
      if ($request->deposit_voucher_url) {
        $oldDigitalFile = DigitalFile::where('url', $request->deposit_voucher_url)->first();

        if ($oldDigitalFile) {
          $this->digitalFileService->destroy($oldDigitalFile->id);
        }
      }

      // Upload new voucher using DigitalFileService
      $path = self::DEPOSIT_VOUCHER_PATH;
      $model = $request->getTable();

      $digitalFile = $this->digitalFileService->store($voucherFile, $path, 'public', $model);

      // Update request with voucher URL
      $request->deposit_voucher_url = $digitalFile->url;
      $request->paid = true;
      $request->save();

      DB::commit();

      return new PerDiemRequestResource(
        $request->fresh([
          'employee',
          'company',
          'sedeService',
          'district',
          'policy',
          'category'
        ])
      );
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Generate mobility payroll for a per diem request
   * Creates a payroll record, links all mobility expenses to it, and returns the PDF directly
   */
  public function generateMobilityPayrollPDF(int $id)
  {
    try {
      DB::beginTransaction();

      // Get the per diem request with necessary relationships
      $perDiemRequest = $this->find($id);
      $perDiemRequest->load(['employee', 'company', 'expenses.expenseType']);

      // Get mobility expenses only (LOCAL TRANSPORT only - not general transportation)
      $mobilityExpenses = $perDiemRequest->expenses->filter(function ($expense) {
        return $expense->expense_type_id === ExpenseType::LOCAL_TRANSPORT_ID;
      });

      // Validate that there are mobility expenses
      if ($mobilityExpenses->isEmpty()) {
        throw new Exception('No hay gastos de movilidad en esta solicitud');
      }

      // Get employee data
      $employee = $perDiemRequest->employee;
      if (!$employee) {
        throw new Exception('No se encontró el empleado asociado a la solicitud');
      }

      // Check if there's already a mobility payroll for this request
      $existingPayrollId = $mobilityExpenses->first()->mobility_payroll_id;

      if ($existingPayrollId) {
        // Update existing payroll: link any new mobility expenses to it
        foreach ($mobilityExpenses as $expense) {
          if (!$expense->mobility_payroll_id) {
            $expense->mobility_payroll_id = $existingPayrollId;
            $expense->save();
          }
        }

        $mobilityPayroll = MobilityPayroll::find($existingPayrollId);
      } else {
        // Extract period from expense dates (format: mes/año)
        $firstExpenseDate = $mobilityExpenses->min('expense_date');
        $period = Carbon::parse($firstExpenseDate)->format('m/Y');

        // Get sede_id from the per diem request
        $sedeId = $perDiemRequest->sede_service_id ?? null;

        // Get serie and correlative_start from AssignSalesSeries
        $assignedSeries = AssignSalesSeries::where('type_receipt_id', AssignSalesSeries::TRAVEL_EXPENSE_FORM)
          ->where('sede_id', $sedeId)
          ->first();

        if (!$assignedSeries) {
          throw new Exception('No se encontró una serie asignada para formularios de gastos de viaje en esta sede. Por favor, configure una serie en el maestro de series.');
        }

        $serie = $assignedSeries->series;
        $correlativeStart = $assignedSeries->correlative_start;

        // Generate next correlative for this serie, period and sede_id
        $correlative = MobilityPayroll::getNextCorrelative($serie, $period, $sedeId, $correlativeStart);

        // Create mobility payroll record
        $mobilityPayroll = MobilityPayroll::create([
          'worker_id' => $employee->id,
          'num_doc' => $perDiemRequest->sedeService->company->num_doc ?? '',
          'company_name' => $perDiemRequest->sedeService->company->name ?? '',
          'address' => $perDiemRequest->sedeService->company->address ?? '',
          'serie' => $serie,
          'correlative' => $correlative,
          'period' => $period,
          'sede_id' => $perDiemRequest->employee->sede_id ?? null,
        ]);

        // Update all mobility expenses with the payroll ID
        foreach ($mobilityExpenses as $expense) {
          $expense->mobility_payroll_id = $mobilityPayroll->id;
          $expense->save();
        }

        // Mark the per diem request as having mobility payroll generated
        $perDiemRequest->update(['mobility_payroll_generated' => true]);
      }

      DB::commit();

      // Generate and return PDF directly
      return $this->mobilityPayrollPDF($id);

    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Generate mobility payroll PDF report
   * Creates a PDF with mobility payroll header and expense details
   */
  public function mobilityPayrollPDF(int $id)
  {
    $perDiemRequest = $this->find($id);
    $perDiemRequest->load(['employee', 'company', 'expenses.expenseType']);

    // Check if mobility payroll has been generated for this request
    if (!$perDiemRequest->mobility_payroll_generated) {
      throw new Exception('Aún no se ha generado la planilla de movilidad para esta solicitud. Debe generar la planilla primero antes de poder visualizar el PDF.');
    }

    // if settled is false, throw exception
    if (!$perDiemRequest->settled) {
      throw new Exception('La solicitud debe estar liquidada para generar la planilla de movilidad.');
    }

    // Get mobility expenses with mobility_payroll_id
    $mobilityExpenses = $perDiemRequest->expenses->filter(function ($expense) {
      return !is_null($expense->mobility_payroll_id);
    });

    // Validate that there are mobility expenses with payroll
    if ($mobilityExpenses->isEmpty()) {
      throw new Exception('No se encontraron gastos de movilidad vinculados a la planilla. Verifique que existan gastos de movilidad y que la planilla haya sido generada correctamente.');
    }

    // Get the mobility payroll ID (should be the same for all expenses)
    $mobilityPayrollId = $mobilityExpenses->first()->mobility_payroll_id;

    // Load the mobility payroll header
    $mobilityPayroll = MobilityPayroll::with(['worker', 'sede'])->find($mobilityPayrollId);

    if (!$mobilityPayroll) {
      throw new Exception('No se encontró la planilla de movilidad');
    }

    // Sort expenses by date and group by date for subtotals
    $sortedExpenses = $mobilityExpenses->sortBy('expense_date')->values();
    $groupedExpenses = $sortedExpenses->groupBy('expense_date');

    // Calculate total
    $totalAmount = $mobilityExpenses->sum('receipt_amount');

    $pdf = PDF::loadView('reports.gp.gestionhumana.viaticos.mobility-payroll', [
      'mobilityPayroll' => $mobilityPayroll,
      'expenses' => $sortedExpenses,
      'groupedExpenses' => $groupedExpenses,
      'totalAmount' => $totalAmount,
      'perDiemRequest' => $perDiemRequest,
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

  /**
   * Get email addresses of accountants assigned to the district
   *
   * @param int|null $districtId
   * @return array Array of email addresses
   */
  private function getAccountantEmailsByDistrict(?int $districtId): array
  {
    if (!$districtId) {
      return [];
    }

    return AccountantDistrictAssignment::where('district_id', $districtId)
      ->with('worker')
      ->get()
      ->pluck('worker.email2')
      ->filter()
      ->values()
      ->toArray();
  }

  /**
   * Send email notifications when a per diem request is created
   */
  private function sendPerDiemRequestCreatedEmails(PerDiemRequest $request, bool $sendToEmployee = true, bool $sendToBoss = true): void
  {
    try {
      // Get accountant emails for this district
      $accountantEmails = $this->getAccountantEmailsByDistrict($request->district_id);

      // Email data for employee
      $employeeEmailData = [
        'employee_name' => $request->employee->nombre_completo,
        'request_code' => $request->code,
        'destination' => $request->district->name ?? 'N/A',
        'start_date' => $request->start_date->format('d/m/Y'),
        'end_date' => $request->end_date->format('d/m/Y'),
        'days_count' => $request->days_count,
        'purpose' => $request->purpose,
        'button_url' => config('app.frontend_url') . '/perfil/viaticos/' . $request->id,
      ];

      // Send email to employee
      if ($sendToEmployee) {
        $this->emailService->queue([
          'to' => $request->employee->email2,
//          'to' => "hvaldiviezos@automotorespakatnamu.com",
          'cc' => $accountantEmails,
          'subject' => 'Solicitud de Viáticos Creada - ' . $request->code,
          'template' => 'emails.per-diem-request-created-employee',
          'data' => $employeeEmailData,
        ]);
      }

      // Send email to boss if exists
      if ($sendToBoss && $request->employee->jefe_id && $request->employee->boss) {
        $bossEmailData = [
          'boss_name' => $request->employee->boss->nombre_completo,
          'employee_name' => $request->employee->nombre_completo,
          'request_code' => $request->code,
          'destination' => $request->district->name ?? 'N/A',
          'start_date' => $request->start_date->format('d/m/Y'),
          'end_date' => $request->end_date->format('d/m/Y'),
          'days_count' => $request->days_count,
          'total_budget' => $request->total_budget,
          'purpose' => $request->purpose,
          'button_url' => config('app.frontend_url') . '/perfil/viaticos/aprobar',
        ];

        $this->emailService->queue([
          'to' => [$request->employee->boss->email2],
//          'to' => "hvaldiviezos@automotorespakatnamu.com",
          'cc' => $accountantEmails,
          'subject' => 'Nueva Solicitud de Viáticos Pendiente de Aprobación - ' . $request->code,
          'template' => 'emails.per-diem-request-created-boss',
          'data' => $bossEmailData,
        ]);
      }
    } catch (Exception $e) {
      // Log error but don't fail the transaction
      \Log::error('Error sending per diem request created emails: ' . $e->getMessage());
    }
  }

  /**
   * Send email notification when a per diem request is approved
   */
  private function sendPerDiemRequestApprovedEmail(PerDiemRequest $request, bool $sendToEmployee = true, bool $sendToBoss = true, bool $sendToAccounting = true): void
  {
    try {
      // Get accountant emails for this district
      $accountantEmails = $this->getAccountantEmailsByDistrict($request->district_id);

      $emailData = [
        'employee_name' => $request->employee->nombre_completo,
        'request_code' => $request->code,
        'destination' => $request->district->name ?? 'N/A',
        'start_date' => $request->start_date->format('d/m/Y'),
        'end_date' => $request->end_date->format('d/m/Y'),
        'total_budget' => $request->total_budget,
      ];

      // Send email to employee
      if ($sendToEmployee) {
        $this->emailService->queue([
          'to' => $request->employee->email2,
//          'to' => "hvaldiviezos@automotorespakatnamu.com",
          'cc' => $accountantEmails,
          'subject' => 'Solicitud de Viáticos Aprobada - ' . $request->code,
          'template' => 'emails.per-diem-request-approved',
          'data' => array_merge($emailData, [
            'recipient_type' => 'employee',
            'button_url' => config('app.frontend_url') . '/perfil/viaticos/' . $request->id,
          ]),
        ]);
      }

      // Send email to boss if exists
      if ($sendToBoss && $request->employee->boss) {
        $this->emailService->queue([
          'to' => $request->employee->boss->email2,
//          'to' => "hvaldiviezos@automotorespakatnamu.com",
          'cc' => $accountantEmails,
          'subject' => 'Solicitud de Viáticos Aprobada - ' . $request->code,
          'template' => 'emails.per-diem-request-approved',
          'data' => array_merge($emailData, [
            'recipient_type' => 'boss',
            'button_url' => config('app.frontend_url') . '/perfil/viaticos/aprobar',
          ]),
        ]);
      }

      // Send email to accounting
      if ($sendToAccounting) {
        $this->emailService->queue([
          'to' => 'ngonzalesd@grupopakatnamu.com',
//          'to' => "hvaldiviezos@automotorespakatnamu.com",
          'cc' => $accountantEmails,
          'subject' => 'Solicitud de Viáticos Aprobada - ' . $request->code,
          'template' => 'emails.per-diem-request-approved',
          'data' => array_merge($emailData, [
            'recipient_type' => 'accounting',
            'button_url' => config('app.frontend_url') . '/perfil/viaticos/aprobar',
          ]),
        ]);
      }
    } catch (Exception $e) {
      \Log::error('Error sending per diem request approved email: ' . $e->getMessage());
    }
  }

  /**
   * Send email notification when settlement process starts
   */
  private function sendPerDiemRequestSettlementEmail(PerDiemRequest $request, bool $sendToEmployee = true, bool $sendToBoss = true, bool $sendToAccounting = true): void
  {
    try {
      // Get accountant emails for this district
      $accountantEmails = $this->getAccountantEmailsByDistrict($request->district_id);

      // Obtener gastos de la empresa
      $gastosEmpresa = $request->expenses->filter(function ($expense) {
        return $expense->is_company_expense === true && !$expense->rejected;
      });

      // Obtener gastos del colaborador
      $gastosColaborador = $request->expenses->filter(function ($expense) {
        return $expense->is_company_expense === false && !$expense->rejected;
      });

      // Formatear gastos de la empresa para el correo
      $gastosEmpresaDetalle = $gastosEmpresa->map(function ($expense) {
        return [
          'fecha' => Carbon::parse($expense->expense_date)->format('d/m/Y'),
          'tipo' => $expense->expenseType->name ?? 'N/A',
          'razon_social' => $expense->business_name ?? 'N/A',
          'monto_comprobante' => $expense->receipt_amount,
          'asume_empresa' => $expense->company_amount,
          'asume_colaborador' => $expense->employee_amount,
        ];
      })->toArray();

      // Formatear gastos del colaborador para el correo
      $gastosColaboradorDetalle = $gastosColaborador->map(function ($expense) {
        return [
          'fecha' => Carbon::parse($expense->expense_date)->format('d/m/Y'),
          'tipo' => $expense->expenseType->name ?? 'N/A',
          'razon_social' => $expense->business_name ?? 'N/A',
          'monto_comprobante' => $expense->receipt_amount,
          'asume_empresa' => $expense->company_amount,
          'asume_colaborador' => $expense->employee_amount,
        ];
      })->toArray();

      // Calcular totales
      $totalEmpresaComprobante = $gastosEmpresa->sum('receipt_amount');
      $totalEmpresaAsumeEmpresa = $gastosEmpresa->sum('company_amount');
      $totalEmpresaAsumeColaborador = $gastosEmpresa->sum('employee_amount');

      $totalColaboradorComprobante = $gastosColaborador->sum('receipt_amount');
      $totalColaboradorAsumeEmpresa = $gastosColaborador->sum('company_amount');
      $totalColaboradorAsumeColaborador = $gastosColaborador->sum('employee_amount');

      // Totales generales
      $totalGeneralComprobante = $totalEmpresaComprobante + $totalColaboradorComprobante;
      $totalGeneralAsumeEmpresa = $totalEmpresaAsumeEmpresa + $totalColaboradorAsumeEmpresa;
      $totalGeneralAsumeColaborador = $totalEmpresaAsumeColaborador + $totalColaboradorAsumeColaborador;

      $emailData = [
        'employee_name' => $request->employee->nombre_completo,
        'request_code' => $request->code,
        'destination' => $request->district->name ?? 'N/A',
        'start_date' => $request->start_date->format('d/m/Y'),
        'end_date' => $request->end_date->format('d/m/Y'),
        'total_budget' => $request->total_budget,
        // Gastos de la empresa
        'gastos_empresa' => $gastosEmpresaDetalle,
        'total_empresa_comprobante' => $totalEmpresaComprobante,
        'total_empresa_asume_empresa' => $totalEmpresaAsumeEmpresa,
        'total_empresa_asume_colaborador' => $totalEmpresaAsumeColaborador,
        // Gastos del colaborador
        'gastos_colaborador' => $gastosColaboradorDetalle,
        'total_colaborador_comprobante' => $totalColaboradorComprobante,
        'total_colaborador_asume_empresa' => $totalColaboradorAsumeEmpresa,
        'total_colaborador_asume_colaborador' => $totalColaboradorAsumeColaborador,
        // Totales generales
        'total_general_comprobante' => $totalGeneralComprobante,
        'total_general_asume_empresa' => $totalGeneralAsumeEmpresa,
        'total_general_asume_colaborador' => $totalGeneralAsumeColaborador,
      ];

      // Send to employee
      if ($sendToEmployee) {
        $this->emailService->queue([
          'to' => $request->employee->email2,
//          'to' => "hvaldiviezos@automotorespakatnamu.com",
          'cc' => $accountantEmails,
          'subject' => 'Liquidación de Viáticos - ' . $request->code,
          'template' => 'emails.per-diem-request-settlement',
          'data' => array_merge($emailData, [
            'recipient_type' => 'employee',
            'button_url' => config('app.frontend_url') . '/perfil/viaticos/' . $request->id,
          ]),
        ]);
      }

      // Send to boss if exists
      if ($sendToBoss && $request->employee->boss) {
        $this->emailService->queue([
          'to' => $request->employee->boss->email2,
//          'to' => "hvaldiviezos@automotorespakatnamu.com",
          'cc' => $accountantEmails,
          'subject' => 'Liquidación de Viáticos - ' . $request->code,
          'template' => 'emails.per-diem-request-settlement',
          'data' => array_merge($emailData, [
            'recipient_type' => 'boss',
            'button_url' => config('app.frontend_url') . '/perfil/viaticos/aprobar',
          ]),
        ]);
      }

      // Send to accounting (contabilidad)
      if ($sendToAccounting) {
        $this->emailService->queue([
          'to' => 'griojasf@automotorespakatnamu.com',
//          'to' => "hvaldiviezos@automotorespakatnamu.com",
          'cc' => $accountantEmails,
          'subject' => 'Liquidación de Viáticos - ' . $request->code,
          'template' => 'emails.per-diem-request-settlement',
          'data' => array_merge($emailData, [
            'recipient_type' => 'accounting',
            'button_url' => config('app.frontend_url') . '/ap/contabilidad/viaticos-ap',
          ]),
        ]);
      }
    } catch (Exception $e) {
      \Log::error('Error sending per diem request settlement email: ' . $e->getMessage());
    }
  }

  /**
   * Send email notification when settlement is completed
   */
  private function sendPerDiemRequestSettledEmail(PerDiemRequest $request, bool $sendToEmployee = true, bool $sendToBoss = true): void
  {
    try {
      // Get accountant emails for this district
      $accountantEmails = $this->getAccountantEmailsByDistrict($request->district_id);

      // Calcular total que asume la empresa (gastos de empresa)
      $gastosEmpresa = $request->expenses->filter(function ($expense) {
        return $expense->is_company_expense === true && !$expense->rejected;
      });
      $totalAsumeEmpresa = $gastosEmpresa->sum('company_amount');

      // Calcular total que asume el colaborador (gastos del colaborador)
      $gastosColaborador = $request->expenses->filter(function ($expense) {
        return $expense->is_company_expense === false && !$expense->rejected;
      });
      $totalAsumeColaborador = $gastosColaborador->sum('company_amount');

      // Calcular el total a reembolsar (importe otorgado - lo que asume el colaborador)
      $importeOtorgado = $request->cash_amount ?? 0;
      $totalReembolsar = $importeOtorgado - $totalAsumeColaborador;

      $emailData = [
        'employee_name' => $request->employee->nombre_completo,
        'request_code' => $request->code,
        'total_budget' => $request->total_budget,
        'total_spent' => $request->total_spent,
        'balance_to_return' => $request->balance_to_return,
        // Nuevos datos para el correo
        'total_asume_empresa' => $totalAsumeEmpresa,
        'total_asume_colaborador' => $totalAsumeColaborador,
        'total_reembolsar' => $totalReembolsar,
        'importe_otorgado' => $importeOtorgado,
      ];

      // Send to employee
      if ($sendToEmployee) {
        $this->emailService->queue([
          'to' => $request->employee->email2,
//          'to' => "hvaldiviezos@automotorespakatnamu.com",
          'cc' => $accountantEmails,
          'subject' => 'Liquidación de Viáticos Completada - ' . $request->code,
          'template' => 'emails.per-diem-request-settled',
          'data' => array_merge($emailData, [
            'recipient_type' => 'employee',
            'button_url' => config('app.frontend_url') . '/perfil/viaticos/' . $request->id,
          ]),
        ]);
      }

      // Send to boss if exists
      if ($sendToBoss && $request->employee->boss) {
        $this->emailService->queue([
          'to' => $request->employee->boss->email2,
//          'to' => "hvaldiviezos@automotorespakatnamu.com",
          'cc' => $accountantEmails,
          'subject' => 'Liquidación de Viáticos Completada - ' . $request->code,
          'template' => 'emails.per-diem-request-settled',
          'data' => array_merge($emailData, [
            'recipient_type' => 'boss',
            'button_url' => config('app.frontend_url') . '/perfil/viaticos/aprobar',
          ]),
        ]);
      }
    } catch (Exception $e) {
      \Log::error('Error sending per diem request settled email: ' . $e->getMessage());
    }
  }

  /**
   * Public method to regenerate budgets (called from HotelReservationService)
   *
   * @param PerDiemRequest $request
   * @param Collection $rates Collection of PerDiemRate
   * @return float Total budget amount
   */
  public function regenerateBudgets(PerDiemRequest $request, Collection $rates): float
  {
    return $this->generateBudgets($request, $rates);
  }

  /**
   * Send email notification when a per diem request is cancelled
   */
  private function sendPerDiemRequestCancelledEmail(PerDiemRequest $request, string $cancellationReason = '', bool $sendToEmployee = true, bool $sendToBoss = true): void
  {
    try {
      // Get accountant emails for this district
      $accountantEmails = $this->getAccountantEmailsByDistrict($request->district_id);

      $emailData = [
        'employee_name' => $request->employee->nombre_completo,
        'request_code' => $request->code,
        'destination' => $request->district->name ?? 'N/A',
        'start_date' => $request->start_date->format('d/m/Y'),
        'end_date' => $request->end_date->format('d/m/Y'),
        'cancellation_reason' => $cancellationReason,
      ];

      // Send to employee
      if ($sendToEmployee) {
        $this->emailService->queue([
          'to' => $request->employee->email2,
//          'to' => "hvaldiviezos@automotorespakatnamu.com",
          'cc' => $accountantEmails,
          'subject' => 'Solicitud de Viáticos Cancelada - ' . $request->code,
          'template' => 'emails.per-diem-request-cancelled',
          'data' => array_merge($emailData, [
            'button_url' => config('app.frontend_url') . '/perfil/viaticos/' . $request->id,
          ]),
        ]);
      }

      // Send to boss if exists
      if ($sendToBoss && $request->employee->boss) {
        $this->emailService->queue([
          'to' => $request->employee->boss->email2,
//          'to' => "hvaldiviezos@automotorespakatnamu.com",
          'cc' => $accountantEmails,
          'subject' => 'Solicitud de Viáticos Cancelada - ' . $request->code,
          'template' => 'emails.per-diem-request-cancelled',
          'data' => array_merge($emailData, [
            'button_url' => config('app.frontend_url') . '/perfil/viaticos/aprobar',
          ]),
        ]);
      }

    } catch (Exception $e) {
      \Log::error('Error sending per diem request cancelled email: ' . $e->getMessage());
    }
  }

  /**
   * Send email notification when a per diem request status changes to in_progress
   */
  private function sendPerDiemInProgressEmail(PerDiemRequest $request, bool $sendToEmployee = true, bool $sendToBoss = true): void
  {
    try {
      // Get accountant emails for this district
      $accountantEmails = $this->getAccountantEmailsByDistrict($request->district_id);

      $emailData = [
        'employee_name' => $request->employee->nombre_completo,
        'request_code' => $request->code,
        'destination' => $request->district->name ?? 'N/A',
        'start_date' => $request->start_date->format('d/m/Y'),
        'total_budget' => $request->total_budget,
      ];

      // Send to employee
      if ($sendToEmployee) {
        $this->emailService->queue([
          'to' => $request->employee->email2,
//          'to' => "hvaldiviezos@automotorespakatnamu.com",
          'cc' => $accountantEmails,
          'subject' => 'Tu Viaje Está en Progreso - ' . $request->code,
          'template' => 'emails.per-diem-in-progress',
          'data' => array_merge($emailData, [
            'button_url' => config('app.frontend_url') . '/perfil/viaticos/' . $request->id,
          ]),
        ]);
      }

      // Send to boss if exists
      if ($sendToBoss && $request->employee->boss) {
        $this->emailService->queue([
          'to' => $request->employee->boss->email2,
//          'to' => "hvaldiviezos@automotorespakatnamu.com",
          'cc' => $accountantEmails,
          'subject' => 'Tu Viaje Está en Progreso - ' . $request->code,
          'template' => 'emails.per-diem-in-progress',
          'data' => array_merge($emailData, [
            'button_url' => config('app.frontend_url') . '/perfil/viaticos/aprobar',
          ]),
        ]);
      }
    } catch (Exception $e) {
      \Log::error('Error sending per diem in progress email: ' . $e->getMessage());
    }
  }

  /**
   * Resend emails for a per diem request based on email type and recipient filters
   * This method calls the existing private email methods
   *
   * @param int $id Per diem request ID
   * @param array $data Request data with email_type and recipient filters
   * @return array Success message with emails sent
   * @throws Exception
   */
  public function resendEmails(int $id, array $data): array
  {
    $request = $this->find($id);

    // Load necessary relationships
    $request->load(['employee.boss', 'district', 'expenses.expenseType']);

    $emailType = $data['email_type'];
    $sendToEmployee = $data['send_to_employee'] ?? false;
    $sendToBoss = $data['send_to_boss'] ?? false;
    $sendToAccounting = $data['send_to_accounting'] ?? false;

    // Count emails to be sent
    $emailCount = 0;
    $recipients = [];

    try {
      switch ($emailType) {
        case 'created':
          $this->sendPerDiemRequestCreatedEmails($request, $sendToEmployee, $sendToBoss);
          if ($sendToEmployee && $request->employee->email2) {
            $emailCount++;
            $recipients[] = 'Empleado';
          }
          if ($sendToBoss && $request->employee->boss) {
            $emailCount++;
            $recipients[] = 'Jefe';
          }
          break;

        case 'approved':
          $this->sendPerDiemRequestApprovedEmail($request, $sendToEmployee, $sendToBoss, $sendToAccounting);
          if ($sendToEmployee && $request->employee->email2) {
            $emailCount++;
            $recipients[] = 'Empleado';
          }
          if ($sendToBoss && $request->employee->boss) {
            $emailCount++;
            $recipients[] = 'Jefe';
          }
          if ($sendToAccounting) {
            $emailCount++;
            $recipients[] = 'Contabilidad';
          }
          break;

        case 'settlement':
          $this->sendPerDiemRequestSettlementEmail($request, $sendToEmployee, $sendToBoss, $sendToAccounting);
          if ($sendToEmployee && $request->employee->email2) {
            $emailCount++;
            $recipients[] = 'Empleado';
          }
          if ($sendToBoss && $request->employee->boss) {
            $emailCount++;
            $recipients[] = 'Jefe';
          }
          if ($sendToAccounting) {
            $emailCount++;
            $recipients[] = 'Contabilidad';
          }
          break;

        case 'settled':
          $this->sendPerDiemRequestSettledEmail($request, $sendToEmployee, $sendToBoss);
          if ($sendToEmployee && $request->employee->email2) {
            $emailCount++;
            $recipients[] = 'Empleado';
          }
          if ($sendToBoss && $request->employee->boss) {
            $emailCount++;
            $recipients[] = 'Jefe';
          }
          break;

        case 'cancelled':
          $this->sendPerDiemRequestCancelledEmail($request, '', $sendToEmployee, $sendToBoss);
          if ($sendToEmployee && $request->employee->email2) {
            $emailCount++;
            $recipients[] = 'Empleado';
          }
          if ($sendToBoss && $request->employee->boss) {
            $emailCount++;
            $recipients[] = 'Jefe';
          }
          break;

        case 'in_progress':
          $this->sendPerDiemInProgressEmail($request, $sendToEmployee, $sendToBoss);
          if ($sendToEmployee && $request->employee->email2) {
            $emailCount++;
            $recipients[] = 'Empleado';
          }
          if ($sendToBoss && $request->employee->boss) {
            $emailCount++;
            $recipients[] = 'Jefe';
          }
          break;

        default:
          throw new Exception('Tipo de correo no válido');
      }

      return [
        'message' => 'Correos reenviados exitosamente',
        'email_type' => $emailType,
        'recipients' => $recipients,
        'total_sent' => $emailCount,
      ];
    } catch (Exception $e) {
      \Log::error('Error resending per diem request emails: ' . $e->getMessage());
      throw $e;
    }
  }

}
