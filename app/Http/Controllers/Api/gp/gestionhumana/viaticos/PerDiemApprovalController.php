<?php

namespace App\Http\Controllers\Api\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\ApprovePerDiemRequestRequest;
use App\Services\gp\gestionhumana\viaticos\PerDiemApprovalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

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
    public function pending(Request $request): JsonResponse
    {
        try {
            // Assuming user ID is passed or from auth
            $approverId = $request->input('approver_id') ?? auth()->id();
            $approvals = $this->service->getPendingApprovals((int) $approverId);

            return response()->json([
                'success' => true,
                'data' => $approvals,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener aprobaciones pendientes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve a per diem request
     */
    public function approve(ApprovePerDiemRequestRequest $request, string $id): JsonResponse
    {
        try {
            // Assuming user ID is passed or from auth
            $approverId = $request->input('approver_id') ?? auth()->id();
            $comments = $request->input('comments');

            $approval = $this->service->approve((int) $id, (int) $approverId, $comments);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud aprobada exitosamente',
                'data' => $approval,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar solicitud',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a per diem request
     */
    public function reject(ApprovePerDiemRequestRequest $request, string $id): JsonResponse
    {
        try {
            // Assuming user ID is passed or from auth
            $approverId = $request->input('approver_id') ?? auth()->id();
            $comments = $request->input('comments');

            $approval = $this->service->reject((int) $id, (int) $approverId, $comments);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud rechazada',
                'data' => $approval,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar solicitud',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
