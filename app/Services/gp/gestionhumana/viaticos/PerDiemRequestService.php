<?php

namespace App\Services\gp\gestionhumana\viaticos;

use App\Models\gp\gestionhumana\viaticos\PerDiemRequest;
use App\Models\gp\gestionhumana\viaticos\PerDiemRate;
use App\Models\gp\gestionhumana\viaticos\RequestBudget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class PerDiemRequestService
{
    /**
     * Get all per diem requests with filters
     */
    public function getAll(array $filters = []): Collection
    {
        $query = PerDiemRequest::with([
            'employee',
            'company',
            'category',
            'policy',
            'budgets.expenseType',
            'approvals.approver',
            'hotelReservation'
        ]);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get per diem request by ID with all relations
     */
    public function getById(int $id): ?PerDiemRequest
    {
        return PerDiemRequest::with([
            'employee',
            'company',
            'category',
            'policy',
            'budgets.expenseType',
            'approvals.approver',
            'hotelReservation.hotelAgreement',
            'expenses.expenseType'
        ])->find($id);
    }

    /**
     * Create new per diem request with budgets
     */
    public function create(array $data): PerDiemRequest
    {
        try {
            DB::beginTransaction();

            // Generate unique code
            $code = $this->generateCode();

            // Create request
            $request = PerDiemRequest::create([
                'code' => $code,
                'employee_id' => $data['employee_id'],
                'company_id' => $data['company_id'],
                'destination' => $data['destination'],
                'per_diem_category_id' => $data['per_diem_category_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'days_count' => $data['days_count'],
                'purpose' => $data['purpose'],
                'final_result' => $data['final_result'] ?? '',
                'cost_center' => $data['cost_center'] ?? null,
                'status' => 'draft',
                'total_budget' => 0,
                'cash_amount' => $data['cash_amount'] ?? 0,
                'transfer_amount' => $data['transfer_amount'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create budgets
            $totalBudget = 0;
            if (isset($data['budgets']) && is_array($data['budgets'])) {
                foreach ($data['budgets'] as $budget) {
                    RequestBudget::create([
                        'per_diem_request_id' => $request->id,
                        'expense_type_id' => $budget['expense_type_id'],
                        'daily_amount' => $budget['daily_amount'],
                        'days' => $budget['days'],
                        'total' => $budget['total'],
                    ]);
                    $totalBudget += $budget['total'];
                }
            }

            // Update total budget
            $request->update(['total_budget' => $totalBudget]);

            DB::commit();

            return $request->fresh(['budgets.expenseType', 'employee', 'company']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update per diem request (only if status is draft)
     */
    public function update(int $id, array $data): PerDiemRequest
    {
        $request = PerDiemRequest::findOrFail($id);

        if ($request->status !== 'draft') {
            throw new Exception('Cannot update request. Only draft requests can be modified.');
        }

        try {
            DB::beginTransaction();

            // Update request
            $request->update([
                'employee_id' => $data['employee_id'] ?? $request->employee_id,
                'company_id' => $data['company_id'] ?? $request->company_id,
                'destination' => $data['destination'] ?? $request->destination,
                'per_diem_category_id' => $data['per_diem_category_id'] ?? $request->per_diem_category_id,
                'start_date' => $data['start_date'] ?? $request->start_date,
                'end_date' => $data['end_date'] ?? $request->end_date,
                'days_count' => $data['days_count'] ?? $request->days_count,
                'purpose' => $data['purpose'] ?? $request->purpose,
                'final_result' => $data['final_result'] ?? $request->final_result,
                'cost_center' => $data['cost_center'] ?? $request->cost_center,
                'cash_amount' => $data['cash_amount'] ?? $request->cash_amount,
                'transfer_amount' => $data['transfer_amount'] ?? $request->transfer_amount,
                'notes' => $data['notes'] ?? $request->notes,
            ]);

            // Update budgets if provided
            if (isset($data['budgets']) && is_array($data['budgets'])) {
                // Delete existing budgets
                $request->budgets()->delete();

                // Create new budgets
                $totalBudget = 0;
                foreach ($data['budgets'] as $budget) {
                    RequestBudget::create([
                        'per_diem_request_id' => $request->id,
                        'expense_type_id' => $budget['expense_type_id'],
                        'daily_amount' => $budget['daily_amount'],
                        'days' => $budget['days'],
                        'total' => $budget['total'],
                    ]);
                    $totalBudget += $budget['total'];
                }

                // Update total budget
                $request->update(['total_budget' => $totalBudget]);
            }

            DB::commit();

            return $request->fresh(['budgets.expenseType', 'employee', 'company']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete per diem request (only if status is draft)
     */
    public function delete(int $id): bool
    {
        $request = PerDiemRequest::findOrFail($id);

        if ($request->status !== 'draft') {
            throw new Exception('Cannot delete request. Only draft requests can be deleted.');
        }

        return $request->delete();
    }

    /**
     * Submit request for approval (change status to pending_manager)
     */
    public function submit(int $id): PerDiemRequest
    {
        $request = PerDiemRequest::findOrFail($id);

        if ($request->status !== 'draft') {
            throw new Exception('Cannot submit request. Only draft requests can be submitted.');
        }

        $request->update(['status' => 'pending_manager']);

        return $request->fresh();
    }

    /**
     * Mark request as paid
     */
    public function markAsPaid(int $id, array $paymentData): PerDiemRequest
    {
        $request = PerDiemRequest::findOrFail($id);

        if ($request->status !== 'approved') {
            throw new Exception('Cannot mark as paid. Request must be approved first.');
        }

        $request->update([
            'paid' => true,
            'payment_date' => $paymentData['payment_date'] ?? now(),
            'payment_method' => $paymentData['payment_method'] ?? 'transfer',
            'status' => 'in_progress',
        ]);

        return $request->fresh();
    }

    /**
     * Start settlement process
     */
    public function startSettlement(int $id): PerDiemRequest
    {
        $request = PerDiemRequest::findOrFail($id);

        if (!in_array($request->status, ['in_progress', 'approved'])) {
            throw new Exception('Cannot start settlement. Request must be in progress or approved.');
        }

        $request->update(['status' => 'pending_settlement']);

        return $request->fresh();
    }

    /**
     * Complete settlement
     */
    public function completeSettlement(int $id, array $settlementData): PerDiemRequest
    {
        $request = PerDiemRequest::findOrFail($id);

        if ($request->status !== 'pending_settlement') {
            throw new Exception('Cannot complete settlement. Request must be in pending settlement status.');
        }

        $request->update([
            'settled' => true,
            'settlement_date' => $settlementData['settlement_date'] ?? now(),
            'total_spent' => $settlementData['total_spent'] ?? 0,
            'balance_to_return' => $settlementData['balance_to_return'] ?? 0,
            'status' => 'settled',
        ]);

        return $request->fresh();
    }

    /**
     * Get overdue settlements
     */
    public function getOverdueSettlements(int $daysOverdue = 30): Collection
    {
        $overdueDate = Carbon::now()->subDays($daysOverdue);

        return PerDiemRequest::where('status', 'in_progress')
            ->where('paid', true)
            ->where('payment_date', '<=', $overdueDate)
            ->where('settled', false)
            ->with(['employee', 'company'])
            ->get();
    }

    /**
     * Get rates for destination and category
     */
    public function getRatesForDestination(int $districtId, int $categoryId): Collection
    {
        return PerDiemRate::where('district_id', $districtId)
            ->where('per_diem_category_id', $categoryId)
            ->where('active', true)
            ->with(['expenseType', 'policy'])
            ->get();
    }

    /**
     * Generate unique code for per diem request
     */
    protected function generateCode(): string
    {
        $year = date('Y');
        $prefix = "PDR-{$year}-";

        $lastRequest = PerDiemRequest::where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->code, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
