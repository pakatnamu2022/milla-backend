<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\ApprovePerDiemRequestRequest;
use App\Http\Services\gp\gestionhumana\viaticos\PerDiemApprovalService;
use Exception;
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
      $approverId = auth()->user()->partner_id;
      return $this->service->getPendingApprovals($approverId);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
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
      return $this->success($this->service->approve($id, $approverId, $comments));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
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
      return $this->success($this->service->reject($id, $approverId, $comments));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
