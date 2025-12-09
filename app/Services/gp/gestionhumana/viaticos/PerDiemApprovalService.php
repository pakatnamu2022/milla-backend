<?php

namespace App\Services\gp\gestionhumana\viaticos;

use App\Models\gp\gestionhumana\viaticos\PerDiemApproval;
use App\Models\gp\gestionhumana\viaticos\PerDiemRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class PerDiemApprovalService
{
    /**
     * Approve a per diem request
     */
    public function approve(int $requestId, int $approverId, ?string $comments = null): PerDiemApproval
    {
        return DB::transaction(function () use ($requestId, $approverId, $comments) {
            $request = PerDiemRequest::findOrFail($requestId);

            // Find pending approval for this approver
            $approval = PerDiemApproval::where('per_diem_request_id', $requestId)
                ->where('approver_id', $approverId)
                ->where('status', 'pending')
                ->firstOrFail();

            // Update approval
            $approval->update([
                'status' => 'approved',
                'comments' => $comments,
                'approved_at' => now(),
            ]);

            // Update request status based on approval flow
            $this->updateRequestStatus($request);

            return $approval->fresh(['approver', 'request']);
        });
    }

    /**
     * Reject a per diem request
     */
    public function reject(int $requestId, int $approverId, ?string $comments = null): PerDiemApproval
    {
        return DB::transaction(function () use ($requestId, $approverId, $comments) {
            $request = PerDiemRequest::findOrFail($requestId);

            // Find pending approval for this approver
            $approval = PerDiemApproval::where('per_diem_request_id', $requestId)
                ->where('approver_id', $approverId)
                ->where('status', 'pending')
                ->firstOrFail();

            // Update approval
            $approval->update([
                'status' => 'rejected',
                'comments' => $comments,
                'approved_at' => now(),
            ]);

            // Update request status to rejected
            $request->update(['status' => 'rejected']);

            // Cancel all other pending approvals
            PerDiemApproval::where('per_diem_request_id', $requestId)
                ->where('status', 'pending')
                ->where('id', '!=', $approval->id)
                ->update(['status' => 'cancelled']);

            return $approval->fresh(['approver', 'request']);
        });
    }

    /**
     * Get pending approvals for an approver
     */
    public function getPendingApprovals(int $approverId): Collection
    {
        return PerDiemApproval::where('approver_id', $approverId)
            ->where('status', 'pending')
            ->with([
                'request.employee',
                'request.company',
                'request.category',
                'request.budgets.expenseType'
            ])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Create approval flow for a request
     * Approver types: 0 = direct_manager, 1 = hr_partner, 2 = general_management
     */
    public function createApprovalFlow(int $requestId, array $approvers): void
    {
        try {
            DB::beginTransaction();

            $request = PerDiemRequest::findOrFail($requestId);

            // Create approvals for each approver type
            foreach ($approvers as $approverData) {
                PerDiemApproval::create([
                    'per_diem_request_id' => $requestId,
                    'approver_id' => $approverData['approver_id'],
                    'approver_type' => $approverData['approver_type'],
                    'status' => 'pending',
                ]);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update request status based on approval flow
     */
    protected function updateRequestStatus(PerDiemRequest $request): void
    {
        // Get all approvals for this request
        $approvals = PerDiemApproval::where('per_diem_request_id', $request->id)
            ->orderBy('approver_type', 'asc')
            ->get();

        // Check if there are any rejected approvals
        if ($approvals->contains('status', 'rejected')) {
            $request->update(['status' => 'rejected']);
            return;
        }

        // Get pending approvals by type
        $pendingManager = $approvals->where('approver_type', 0)->where('status', 'pending')->count();
        $pendingHR = $approvals->where('approver_type', 1)->where('status', 'pending')->count();
        $pendingGeneral = $approvals->where('approver_type', 2)->where('status', 'pending')->count();

        // Update status based on pending approvals
        if ($pendingManager > 0) {
            $request->update(['status' => 'pending_manager']);
        } elseif ($pendingHR > 0) {
            $request->update(['status' => 'pending_hr']);
        } elseif ($pendingGeneral > 0) {
            $request->update(['status' => 'pending_general']);
        } else {
            // All approvals are complete
            $allApproved = $approvals->every(fn($approval) => $approval->status === 'approved');
            if ($allApproved) {
                $request->update(['status' => 'approved']);
            }
        }
    }
}
