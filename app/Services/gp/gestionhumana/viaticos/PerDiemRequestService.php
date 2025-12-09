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
        return DB::transaction(function () use ($data) {
            // Extract budgets from data
            $budgets = $data['budgets'] ?? [];
            unset($data['budgets']);

            // Create request
            $request = PerDiemRequest::create($data);

            // Create budgets
            foreach ($budgets as $budget) {
                RequestBudget::create(array_merge($budget, [
                    'per_diem_request_id' => $request->id
                ]));
            }

            return $request->fresh(['budgets.expenseType', 'employee', 'company', 'category', 'policy']);
        });
    }

    /**
     * Update per diem request (only if status is draft)
     */
    public function update(int $id, array $data): PerDiemRequest
    {
        $request = PerDiemRequest::findOrFail($id);

        // Business validation: only draft requests can be modified
        if ($request->status !== 'draft') {
            throw new Exception('No se puede actualizar la solicitud. Solo las solicitudes en borrador pueden ser modificadas.');
        }

        return DB::transaction(function () use ($request, $data) {
            // Extract budgets if provided
            $budgets = $data['budgets'] ?? null;
            unset($data['budgets']);

            // Update request
            $request->update($data);

            // Update budgets if provided
            if ($budgets !== null && is_array($budgets)) {
                // Delete existing budgets
                $request->budgets()->delete();

                // Create new budgets
                foreach ($budgets as $budget) {
                    RequestBudget::create(array_merge($budget, [
                        'per_diem_request_id' => $request->id
                    ]));
                }
            }

            return $request->fresh(['budgets.expenseType', 'employee', 'company', 'category', 'policy']);
        });
    }

    /**
     * Delete per diem request (only if status is draft)
     */
    public function delete(int $id): bool
    {
        $request = PerDiemRequest::findOrFail($id);

        // Business validation: only draft requests can be deleted
        if ($request->status !== 'draft') {
            throw new Exception('No se puede eliminar la solicitud. Solo las solicitudes en borrador pueden ser eliminadas.');
        }

        return $request->delete();
    }

    /**
     * Submit request for approval (change status to pending_manager)
     */
    public function submit(int $id): PerDiemRequest
    {
        $request = PerDiemRequest::findOrFail($id);

        // Business validation: only draft requests can be submitted
        if ($request->status !== 'draft') {
            throw new Exception('No se puede enviar la solicitud. Solo las solicitudes en borrador pueden ser enviadas.');
        }

        $request->update(['status' => 'pending_manager']);

        return $request->fresh();
    }

    /**
     * Mark request as paid
     */
    public function markAsPaid(int $id, array $data): PerDiemRequest
    {
        $request = PerDiemRequest::findOrFail($id);

        // Business validation: only approved requests can be marked as paid
        if ($request->status !== 'approved') {
            throw new Exception('No se puede marcar como pagado. La solicitud debe estar aprobada primero.');
        }

        $request->update($data);

        return $request->fresh();
    }

    /**
     * Start settlement process
     */
    public function startSettlement(int $id, array $data): PerDiemRequest
    {
        $request = PerDiemRequest::findOrFail($id);

        // Business validation: only in_progress or approved requests can start settlement
        if (!in_array($request->status, ['in_progress', 'approved'])) {
            throw new Exception('No se puede iniciar la liquidación. La solicitud debe estar en progreso o aprobada.');
        }

        $request->update($data);

        return $request->fresh();
    }

    /**
     * Complete settlement
     */
    public function completeSettlement(int $id, array $data): PerDiemRequest
    {
        $request = PerDiemRequest::findOrFail($id);

        // Business validation: only pending_settlement requests can be completed
        if ($request->status !== 'pending_settlement') {
            throw new Exception('No se puede completar la liquidación. La solicitud debe estar pendiente de liquidación.');
        }

        $request->update($data);

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
}
