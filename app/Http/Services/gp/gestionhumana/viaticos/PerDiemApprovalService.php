<?php

namespace App\Http\Services\gp\gestionhumana\viaticos;

use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemApprovalResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\viaticos\PerDiemApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Exception;

class PerDiemApprovalService extends BaseService implements BaseServiceInterface
{
  /**
   * Get all approvals with filters and pagination
   */
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PerDiemApproval::with(['request', 'approver']),
      $request,
      PerDiemApproval::filters,
      PerDiemApproval::sorts,
      PerDiemApprovalResource::class,
    );
  }

  /**
   * Find an approval by ID (internal method)
   */
  public function find($id)
  {
    $approval = PerDiemApproval::where('id', $id)->first();
    if (!$approval) {
      throw new Exception('Aprobación no encontrada');
    }
    return $approval;
  }

  /**
   * Show an approval by ID
   */
  public function show($id)
  {
    return new PerDiemApprovalResource($this->find($id)->load(['request', 'approver']));
  }

  /**
   * Create a new approval
   */
  public function store(mixed $data)
  {
    $approval = PerDiemApproval::create($data);
    return new PerDiemApprovalResource($approval->load(['request', 'approver']));
  }

  /**
   * Update an approval
   */
  public function update(mixed $data)
  {
    $approval = $this->find($data['id']);
    $approval->update($data);
    return new PerDiemApprovalResource($approval->fresh(['request', 'approver']));
  }

  /**
   * Delete an approval
   */
  public function destroy($id)
  {
    $approval = $this->find($id);
    DB::transaction(function () use ($approval) {
      $approval->delete();
    });
    return response()->json(['message' => 'Aprobación eliminada correctamente']);
  }

  /**
   * Get pending approvals for a specific approver
   */
  public function getPendingApprovals(int $approverId)
  {
    return PerDiemApproval::with(['request.employee', 'request.company', 'request.category', 'request.budgets.expenseType', 'approver'])
      ->where('approver_id', $approverId)
      ->where('status', 'pending')
      ->orderBy('created_at', 'desc')
      ->get();
  }

  /**
   * Approve a per diem request
   */
  public function approve(int $approvalId, int $approverId, ?string $comments = null): PerDiemApproval
  {
    try {
      DB::beginTransaction();

      // Find the approval
      $approval = $this->find($approvalId);

      // Validate that the approver is authorized
      if ($approval->approver_id !== $approverId) {
        throw new Exception('No tiene autorización para aprobar esta solicitud');
      }

      // Validate that the approval is pending
      if ($approval->status !== 'pending') {
        throw new Exception('Esta aprobación ya ha sido procesada');
      }

      // Update the approval
      $approval->update([
        'status' => 'approved',
        'comments' => $comments,
        'approved_at' => now(),
      ]);

      // Get the per diem request
      $request = $approval->request;

      // Check if all approvals are approved
      $pendingApprovals = $request->approvals()->where('status', 'pending')->count();

      if ($pendingApprovals === 0) {
        // All approvals are done, update request status
        $request->update(['status' => 'approved']);
      }

      DB::commit();
      return $approval->fresh(['request', 'approver']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Reject a per diem request
   */
  public function reject(int $approvalId, int $approverId, ?string $comments = null): PerDiemApproval
  {
    try {
      DB::beginTransaction();

      // Find the approval
      $approval = $this->find($approvalId);

      // Validate that the approver is authorized
      if ($approval->approver_id !== $approverId) {
        throw new Exception('No tiene autorización para rechazar esta solicitud');
      }

      // Validate that the approval is pending
      if ($approval->status !== 'pending') {
        throw new Exception('Esta aprobación ya ha sido procesada');
      }

      // Validate that comments are provided for rejection
      if (empty($comments)) {
        throw new Exception('Debe proporcionar un comentario al rechazar una solicitud');
      }

      // Update the approval
      $approval->update([
        'status' => 'rejected',
        'comments' => $comments,
        'approved_at' => now(),
      ]);

      // Get the per diem request and update its status
      $request = $approval->request;
      $request->update(['status' => 'rejected']);

      DB::commit();
      return $approval->fresh(['request', 'approver']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Get approvals by request ID
   */
  public function getByRequest(int $requestId)
  {
    return PerDiemApproval::with(['approver'])
      ->where('per_diem_request_id', $requestId)
      ->orderBy('created_at', 'asc')
      ->get();
  }

  /**
   * Check if a request can be approved by a specific user
   */
  public function canApprove(int $requestId, int $userId): bool
  {
    return PerDiemApproval::where('per_diem_request_id', $requestId)
      ->where('approver_id', $userId)
      ->where('status', 'pending')
      ->exists();
  }

  /**
   * Get approval history for a specific request
   */
  public function getApprovalHistory(int $requestId)
  {
    return PerDiemApproval::with(['approver'])
      ->where('per_diem_request_id', $requestId)
      ->orderBy('approved_at', 'asc')
      ->get();
  }
}
