<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\ApprovePerDiemRequestRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemApprovalResource;
use App\Http\Services\gp\gestionhumana\viaticos\PerDiemApprovalService;
use Illuminate\Http\Request;

class PerDiemApprovalController extends Controller
{
  protected PerDiemApprovalService $service;

  public function __construct(PerDiemApprovalService $service)
  {
    $this->service = $service;
  }

  /**
   * Get pending approvals for current user
   */
  public function pending()
  {
    try {
      $approverId = auth()->id();
      $approvals = $this->service->getPendingApprovals($approverId);

      return response()->json([
        'success' => true,
        'data' => PerDiemApprovalResource::collection($approvals)
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Approve a per diem request
   */
  public function approve(ApprovePerDiemRequestRequest $request, int $id)
  {
    try {
      $approverId = auth()->id();
      $comments = $request->input('comments');

      $approval = $this->service->approve($id, $approverId, $comments);

      return response()->json([
        'success' => true,
        'data' => new PerDiemApprovalResource($approval),
        'message' => 'Solicitud aprobada exitosamente'
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Reject a per diem request
   */
  public function reject(Request $request, int $id)
  {
    try {
      $approverId = auth()->id();
      $comments = $request->input('comments');

      $approval = $this->service->reject($id, $approverId, $comments);

      return response()->json([
        'success' => true,
        'data' => new PerDiemApprovalResource($approval),
        'message' => 'Solicitud rechazada exitosamente'
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }
}
