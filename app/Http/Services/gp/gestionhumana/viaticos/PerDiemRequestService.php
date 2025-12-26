<?php

namespace App\Http\Services\gp\gestionhumana\viaticos;

use App\Http\Resources\gp\gestionhumana\viaticos\ExpenseTypeResource;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemRequestResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\EmailService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
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
        'with_active' => $data['with_active'] ?? false,
        'with_request' => $data['with_request'] ?? false,
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
      return new PerDiemRequestResource($request->fresh(['employee', 'company', 'companyService', 'district', 'policy', 'category', 'budgets.expenseType', 'approvals.approver']));
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

      // Send settlement email to employee
      $this->sendPerDiemRequestSettlementEmail($request->fresh(['employee', 'district']));

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

      // Send settlement completed email to employee
      $this->sendPerDiemRequestSettledEmail($request->fresh(['employee', 'district']));

      DB::commit();
      return $request->fresh(['employee', 'company', 'companyService', 'district', 'policy', 'category', 'expenses']);
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
      if ($request->status !== 'approved') {
        throw new Exception('Solo se pueden confirmar solicitudes aprobadas');
      }

      // Change status to 'in_progress'
      $request->update(['status' => 'in_progress']);

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

      DB::commit();

      return new PerDiemRequestResource(
        $request->fresh([
          'employee',
          'company',
          'companyService',
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

  /**
   * Get available expense types for a per diem request
   * Returns expense types that have budgets assigned to the request
   *
   * @param int $id Per diem request ID
   * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
   * @throws Exception
   */
  public function getAvailableExpenseTypes(int $id)
  {
    $request = $this->find($id);

    // Get all expense types from budgets
    $expenseTypeIds = $request->budgets()->pluck('expense_type_id')->unique();

    // Get expense types with parent relation
    $expenseTypes = ExpenseType::whereIn('id', $expenseTypeIds)
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
      'companyService',
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
   * Generate approved expenses export PDF with attachments
   */
  public function generateExpensesPDF($id)
  {
    $perDiemRequest = $this->find($id);

    // Load all necessary relationships
    $perDiemRequest->load([
      'employee.boss.position',
      'employee.position.area',
      'company',
      'companyService',
      'district',
      'category',
      'policy',
      'approvals.approver.position',
      'expenses.expenseType',
    ]);

    $dataResource = new PerDiemRequestResource($perDiemRequest);
    $dataArray = $dataResource->resolve();

    // Filtrar solo gastos aprobados (validated = true)
    $approvedExpenses = collect($dataArray['expenses'] ?? [])->filter(function ($expense) {
      return $expense['validated'] === true;
    });

    // Calcular totales
    $totalApproved = $approvedExpenses->sum('company_amount');

    // Filtrar gastos que tienen archivos adjuntos
    $expensesWithAttachments = $approvedExpenses->filter(function ($expense) {
      return !empty($expense['receipt_path']);
    });

    $pdf = PDF::loadView('reports.gp.gestionhumana.viaticos.expenses-export', [
      'request' => $dataArray,
      'approvedExpenses' => $approvedExpenses,
      'totalApproved' => $totalApproved,
      'expensesWithAttachments' => $expensesWithAttachments,
    ]);

    $pdf->setOptions([
      'defaultFont' => 'Arial',
      'isHtml5ParserEnabled' => true,
      'isRemoteEnabled' => true,
      'dpi' => 96,
    ]);

    $pdf->setPaper('A4', 'portrait');

    return $pdf;
  }

  /**
   * Generate expense detail PDF report for travel expenses
   * Shows all expenses grouped by type (alimentación, hospedaje, movilidad, otros, sin comprobante)
   *
   * @param int $id Per diem request ID
   * @return mixed PDF instance
   */
  public function generateExpenseDetailPDF($id)
  {
    $perDiemRequest = $this->find($id);

    // Load all necessary relationships
    $perDiemRequest->load([
      'employee.position.area',
      'company',
      'district',
      'expenses.expenseType',
    ]);

    $dataResource = new PerDiemRequestResource($perDiemRequest);
    $dataArray = $dataResource->resolve();

    // Agrupar gastos por tipo de gasto
    $allExpenses = collect($dataArray['expenses'] ?? []);

    // Gastos de alimentación
    $alimentacion = $allExpenses->filter(function ($expense) {
      $typeId = $expense['expense_type_id'] ?? ($expense['expense_type']['id'] ?? null);
      return $typeId === ExpenseType::MEALS_ID;
    });

    // Gastos de hospedaje
    $hospedaje = $allExpenses->filter(function ($expense) {
      $typeId = $expense['expense_type_id'] ?? ($expense['expense_type']['id'] ?? null);
      return $typeId === ExpenseType::ACCOMMODATION_ID;
    });

    // Gastos de movilidad (transporte local + transporte)
    $movilidad = $allExpenses->filter(function ($expense) {
      $typeId = $expense['expense_type_id'] ?? ($expense['expense_type']['id'] ?? null);
      return in_array($typeId, [
        ExpenseType::LOCAL_TRANSPORT_ID,
        ExpenseType::TRANSPORTATION_ID
      ]);
    });

    // Gastos sin comprobante
    $sinComprobante = $allExpenses->filter(function ($expense) {
      return isset($expense['receipt_type']) && $expense['receipt_type'] === 'no_receipt';
    });

    // Otros gastos (los que no están en las categorías anteriores)
    $otros = $allExpenses->filter(function ($expense) {
      $typeId = $expense['expense_type_id'] ?? ($expense['expense_type']['id'] ?? null);
      $isMainCategory = in_array($typeId, [
        ExpenseType::MEALS_ID,
        ExpenseType::ACCOMMODATION_ID,
        ExpenseType::LOCAL_TRANSPORT_ID,
        ExpenseType::TRANSPORTATION_ID
      ]);
      $isNoReceipt = isset($expense['receipt_type']) && $expense['receipt_type'] === 'no_receipt';

      return $typeId !== null && !$isMainCategory && !$isNoReceipt;
    });

    // Calcular totales por categoría
    $totalAlimentacion = $alimentacion->sum('company_amount');
    $totalHospedaje = $hospedaje->sum('company_amount');
    $totalMovilidad = $movilidad->sum('company_amount');
    $totalOtros = $otros->sum('company_amount');
    $totalSinComprobante = $sinComprobante->sum('company_amount');
    $totalGeneral = $allExpenses->sum('company_amount');

    // Calcular importes de pie de página
    $importeOtorgado = $dataArray['cash_amount'] ?? 0;
    $montoDevolver = $importeOtorgado - $totalGeneral;

    $pdf = PDF::loadView('reports.gp.gestionhumana.viaticos.expense-detail', [
      'request' => $dataArray,
      'alimentacion' => $alimentacion,
      'hospedaje' => $hospedaje,
      'movilidad' => $movilidad,
      'otros' => $otros,
      'sinComprobante' => $sinComprobante,
      'totalAlimentacion' => $totalAlimentacion,
      'totalHospedaje' => $totalHospedaje,
      'totalMovilidad' => $totalMovilidad,
      'totalOtros' => $totalOtros,
      'totalSinComprobante' => $totalSinComprobante,
      'totalGeneral' => $totalGeneral,
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

      // Validate that the request has with_request enabled
      if (!$request->with_request) {
        throw new Exception('Esta solicitud no requiere voucher de depósito (with_request es false)');
      }

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
      $request->save();

      DB::commit();

      return new PerDiemRequestResource(
        $request->fresh([
          'employee',
          'company',
          'companyService',
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
   * Creates a payroll record and links all mobility expenses to it
   */
  public function generateMobilityPayroll(int $id)
  {
    try {
      DB::beginTransaction();

      // Get the per diem request with necessary relationships
      $perDiemRequest = $this->find($id);
      $perDiemRequest->load(['employee', 'company', 'expenses.expenseType']);

      // Get mobility expenses only
      $mobilityExpenses = $perDiemRequest->expenses->filter(function ($expense) {
        return in_array($expense->expense_type_id, [
          ExpenseType::LOCAL_TRANSPORT_ID,
          ExpenseType::TRANSPORTATION_ID
        ]);
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

      // Extract period from expense dates (format: mes/año)
      $firstExpenseDate = $mobilityExpenses->min('expense_date');
      $period = Carbon::parse($firstExpenseDate)->format('m/Y');

      // Get serie based on company (you can adjust this logic as needed)
      $serie = 'MOV-' . ($perDiemRequest->company->id ?? '001');

      // Generate next correlative for this serie and period
      $correlative = MobilityPayroll::getNextCorrelative($serie, $period);

      // Create mobility payroll record
      $mobilityPayroll = MobilityPayroll::create([
        'worker_id' => $employee->id,
        'num_doc' => $employee->num_documento ?? '',
        'company_name' => $perDiemRequest->company->nombre ?? '',
        'address' => $perDiemRequest->company->direccion ?? '',
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

      DB::commit();

      // Load relationships for response
      $mobilityPayroll->load(['worker', 'sede', 'expenses.expenseType']);

      return [
        'mobility_payroll' => $mobilityPayroll,
        'expenses_count' => $mobilityExpenses->count(),
        'total_amount' => $mobilityExpenses->sum('receipt_amount'),
        'message' => 'Planilla de movilidad generada exitosamente'
      ];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Generate mobility payroll PDF report
   * Creates a PDF with mobility payroll header and expense details
   */
  public function generateMobilityPayrollPDF(int $id)
  {
    $perDiemRequest = $this->find($id);
    $perDiemRequest->load(['employee', 'company', 'expenses.expenseType']);

    // Get mobility expenses with mobility_payroll_id
    $mobilityExpenses = $perDiemRequest->expenses->filter(function ($expense) {
      return !is_null($expense->mobility_payroll_id);
    });

    // Validate that there are mobility expenses with payroll
    if ($mobilityExpenses->isEmpty()) {
      throw new Exception('No hay gastos de movilidad con planilla generada');
    }

    // Get the mobility payroll ID (should be the same for all expenses)
    $mobilityPayrollId = $mobilityExpenses->first()->mobility_payroll_id;

    // Load the mobility payroll header
    $mobilityPayroll = MobilityPayroll::with(['worker', 'sede'])->find($mobilityPayrollId);

    if (!$mobilityPayroll) {
      throw new Exception('No se encontró la planilla de movilidad');
    }

    // Calculate total
    $totalAmount = $mobilityExpenses->sum('receipt_amount');

    $pdf = PDF::loadView('reports.gp.gestionhumana.viaticos.mobility-payroll', [
      'mobilityPayroll' => $mobilityPayroll,
      'expenses' => $mobilityExpenses,
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
   * Send email notifications when a per diem request is created
   */
  private function sendPerDiemRequestCreatedEmails(PerDiemRequest $request): void
  {
    try {
      // Email data for employee
      $employeeEmailData = [
        'employee_name' => $request->employee->nombre_completo,
        'request_code' => $request->code,
        'destination' => $request->district->nombre ?? 'N/A',
        'start_date' => $request->start_date->format('d/m/Y'),
        'end_date' => $request->end_date->format('d/m/Y'),
        'days_count' => $request->days_count,
        'purpose' => $request->purpose,
      ];

      // Send email to employee
      $this->emailService->send([
        'to' => 'hvaldiviezos@automotorespakatnamu.com', // For testing
        'subject' => 'Solicitud de Viáticos Creada - ' . $request->code,
        'template' => 'emails.per-diem-request-created-employee',
        'data' => $employeeEmailData,
      ]);

      // Send email to boss if exists
      if ($request->employee->jefe_id && $request->employee->boss) {
        $bossEmailData = [
          'boss_name' => $request->employee->boss->nombre_completo,
          'employee_name' => $request->employee->nombre_completo,
          'request_code' => $request->code,
          'destination' => $request->district->nombre ?? 'N/A',
          'start_date' => $request->start_date->format('d/m/Y'),
          'end_date' => $request->end_date->format('d/m/Y'),
          'days_count' => $request->days_count,
          'total_budget' => $request->total_budget,
          'purpose' => $request->purpose,
        ];

        $this->emailService->send([
          'to' => 'hvaldiviezos@automotorespakatnamu.com', // For testing
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
  private function sendPerDiemRequestApprovedEmail(PerDiemRequest $request): void
  {
    try {
      $emailData = [
        'employee_name' => $request->employee->nombre_completo,
        'request_code' => $request->code,
        'destination' => $request->district->nombre ?? 'N/A',
        'start_date' => $request->start_date->format('d/m/Y'),
        'end_date' => $request->end_date->format('d/m/Y'),
        'total_budget' => $request->total_budget,
      ];

      $this->emailService->send([
        'to' => 'hvaldiviezos@automotorespakatnamu.com', // For testing
        'subject' => 'Solicitud de Viáticos Aprobada - ' . $request->code,
        'template' => 'emails.per-diem-request-approved',
        'data' => $emailData,
      ]);
    } catch (Exception $e) {
      \Log::error('Error sending per diem request approved email: ' . $e->getMessage());
    }
  }

  /**
   * Send email notification when settlement process starts
   */
  private function sendPerDiemRequestSettlementEmail(PerDiemRequest $request): void
  {
    try {
      $emailData = [
        'employee_name' => $request->employee->nombre_completo,
        'request_code' => $request->code,
        'destination' => $request->district->nombre ?? 'N/A',
        'start_date' => $request->start_date->format('d/m/Y'),
        'end_date' => $request->end_date->format('d/m/Y'),
        'total_budget' => $request->total_budget,
      ];

      $this->emailService->send([
        'to' => 'hvaldiviezos@automotorespakatnamu.com', // For testing
        'subject' => 'Liquidación de Viáticos - ' . $request->code,
        'template' => 'emails.per-diem-request-settlement',
        'data' => $emailData,
      ]);
    } catch (Exception $e) {
      \Log::error('Error sending per diem request settlement email: ' . $e->getMessage());
    }
  }

  /**
   * Send email notification when settlement is completed
   */
  private function sendPerDiemRequestSettledEmail(PerDiemRequest $request): void
  {
    try {
      $emailData = [
        'employee_name' => $request->employee->nombre_completo,
        'request_code' => $request->code,
        'total_budget' => $request->total_budget,
        'total_spent' => $request->total_spent,
        'balance_to_return' => $request->balance_to_return,
      ];

      $this->emailService->send([
        'to' => 'hvaldiviezos@automotorespakatnamu.com', // For testing
        'subject' => 'Liquidación de Viáticos Completada - ' . $request->code,
        'template' => 'emails.per-diem-request-settled',
        'data' => $emailData,
      ]);
    } catch (Exception $e) {
      \Log::error('Error sending per diem request settled email: ' . $e->getMessage());
    }
  }
}
