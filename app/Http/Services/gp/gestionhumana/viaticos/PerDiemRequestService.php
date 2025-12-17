<?php

namespace App\Http\Services\gp\gestionhumana\viaticos;

use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemRequestResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\viaticos\PerDiemRequest;
use App\Models\gp\gestionhumana\viaticos\PerDiemRate;
use App\Models\gp\gestionhumana\viaticos\PerDiemPolicy;
use App\Models\gp\gestionhumana\viaticos\PerDiemApproval;
use App\Models\gp\gestionhumana\viaticos\RequestBudget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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
            PerDiemRequest::with(['employee', 'company', 'category', 'policy', 'budgets.expenseType']),
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
        return new PerDiemRequestResource($this->find($id)->load([
            'employee',
            'company',
            'category',
            'policy',
            'budgets.expenseType',
            'approvals.approver',
            'hotelReservation',
            'expenses'
        ]));
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
                'employee_id' => $data['employee_id'] ?? Auth::id(),
                'company_id' => $data['company_id'],
                'destination' => $data['destination'],
                'per_diem_category_id' => $data['per_diem_category_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'days_count' => $daysCount,
                'purpose' => $data['purpose'],
                'cost_center' => $data['cost_center'] ?? null,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
            ];

            // Create the request
            $request = PerDiemRequest::create($requestData);

            // Create budget items if provided
            if (isset($data['budgets']) && is_array($data['budgets'])) {
                $totalBudget = 0;

                foreach ($data['budgets'] as $budget) {
                    $budgetTotal = $budget['daily_amount'] * $budget['days'];

                    RequestBudget::create([
                        'per_diem_request_id' => $request->id,
                        'expense_type_id' => $budget['expense_type_id'],
                        'daily_amount' => $budget['daily_amount'],
                        'days' => $budget['days'],
                        'total' => $budgetTotal,
                    ]);

                    $totalBudget += $budgetTotal;
                }

                // Update total budget
                $request->update(['total_budget' => $totalBudget]);
            }

            DB::commit();
            return new PerDiemRequestResource($request->fresh(['employee', 'company', 'category', 'policy', 'budgets.expenseType']));
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

            // Only allow updates if status is draft or rejected
            if (!in_array($request->status, ['draft', 'rejected'])) {
                throw new Exception('Solo se pueden actualizar solicitudes en estado borrador o rechazadas');
            }

            // Calculate days count if dates are updated
            if (isset($data['start_date']) || isset($data['end_date'])) {
                $startDate = Carbon::parse($data['start_date'] ?? $request->start_date);
                $endDate = Carbon::parse($data['end_date'] ?? $request->end_date);
                $data['days_count'] = $startDate->diffInDays($endDate) + 1;
            }

            // Update the request
            $request->update($data);

            // Update budget items if provided
            if (isset($data['budgets']) && is_array($data['budgets'])) {
                // Delete existing budgets
                $request->budgets()->delete();

                $totalBudget = 0;

                foreach ($data['budgets'] as $budget) {
                    $budgetTotal = $budget['daily_amount'] * $budget['days'];

                    RequestBudget::create([
                        'per_diem_request_id' => $request->id,
                        'expense_type_id' => $budget['expense_type_id'],
                        'daily_amount' => $budget['daily_amount'],
                        'days' => $budget['days'],
                        'total' => $budgetTotal,
                    ]);

                    $totalBudget += $budgetTotal;
                }

                // Update total budget
                $request->update(['total_budget' => $totalBudget]);
            }

            DB::commit();
            return new PerDiemRequestResource($request->fresh(['employee', 'company', 'category', 'policy', 'budgets.expenseType']));
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

        // Only allow deletion if status is draft
        if ($request->status !== 'draft') {
            throw new Exception('Solo se pueden eliminar solicitudes en estado borrador');
        }

        DB::transaction(function () use ($request) {
            // Delete related records
            $request->budgets()->delete();
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
     * Get rates for a specific destination and category
     */
    public function getRatesForDestination(int $districtId, int $categoryId)
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
            if (!in_array($request->status, ['draft', 'rejected'])) {
                throw new Exception('Solo se pueden enviar solicitudes en estado borrador o rechazadas');
            }

            // Validate that request has budgets
            if ($request->budgets()->count() === 0) {
                throw new Exception('La solicitud debe tener al menos un presupuesto');
            }

            // Update status
            $request->update(['status' => 'pending_approval']);

            // Create approval records (this can be customized based on approval workflow)
            // For now, we'll create a basic approval record
            PerDiemApproval::create([
                'per_diem_request_id' => $request->id,
                'approver_id' => $request->employee->manager_id ?? null, // Assuming employee has manager_id
                'approver_type' => 'manager',
                'status' => 'pending',
            ]);

            DB::commit();
            return $request->fresh(['employee', 'company', 'category', 'policy', 'budgets.expenseType', 'approvals.approver']);
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
                'status' => 'paid',
            ]);

            DB::commit();
            return $request->fresh(['employee', 'company', 'category', 'policy', 'budgets.expenseType']);
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

            // Validate status
            if (!in_array($request->status, ['paid', 'in_progress'])) {
                throw new Exception('Solo se puede iniciar liquidación de solicitudes pagadas o en progreso');
            }

            // Update status
            $request->update([
                'status' => 'pending_settlement',
            ]);

            DB::commit();
            return $request->fresh(['employee', 'company', 'category', 'policy', 'budgets.expenseType']);
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

            // Validate status
            if ($request->status !== 'pending_settlement') {
                throw new Exception('Solo se puede completar la liquidación de solicitudes en estado pendiente de liquidación');
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
                'status' => 'settled',
            ]);

            DB::commit();
            return $request->fresh(['employee', 'company', 'category', 'policy', 'budgets.expenseType', 'expenses']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
