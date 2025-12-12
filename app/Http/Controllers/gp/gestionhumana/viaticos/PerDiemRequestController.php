<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\StorePerDiemRequestRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\UpdatePerDiemRequestRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemRequestResource;
use App\Services\gp\gestionhumana\viaticos\PerDiemRequestService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerDiemRequestController extends Controller
{
  protected PerDiemRequestService $service;

  public function __construct(PerDiemRequestService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of per diem requests
   */
  public function index(Request $request): JsonResponse
  {
    try {
      $filters = $request->only(['status', 'employee_id', 'company_id', 'start_date', 'end_date']);
      $requests = $this->service->getAll($filters);

      return response()->json([
        'success' => true,
        'data' => PerDiemRequestResource::collection($requests),
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al obtener solicitudes de viáticos',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Store a newly created per diem request
   */
  public function store(StorePerDiemRequestRequest $request): JsonResponse
  {
    try {
      $perDiemRequest = $this->service->create($request->validated());

      return response()->json([
        'success' => true,
        'message' => 'Solicitud de viático creada exitosamente',
        'data' => new PerDiemRequestResource($perDiemRequest),
      ], 201);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al crear solicitud de viático',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Display the specified per diem request
   */
  public function show(string $id): JsonResponse
  {
    try {
      $request = $this->service->getById((int)$id);

      if (!$request) {
        return response()->json([
          'success' => false,
          'message' => 'Solicitud de viático no encontrada',
        ], 404);
      }

      return response()->json([
        'success' => true,
        'data' => new PerDiemRequestResource($request),
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al obtener solicitud de viático',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Update the specified per diem request
   */
  public function update(UpdatePerDiemRequestRequest $request, string $id): JsonResponse
  {
    try {
      $perDiemRequest = $this->service->update((int)$id, $request->validated());

      return response()->json([
        'success' => true,
        'message' => 'Solicitud de viático actualizada exitosamente',
        'data' => new PerDiemRequestResource($perDiemRequest),
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al actualizar solicitud de viático',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Remove the specified per diem request
   */
  public function destroy(string $id): JsonResponse
  {
    try {
      $this->service->delete((int)$id);

      return response()->json([
        'success' => true,
        'message' => 'Solicitud de viático eliminada exitosamente',
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al eliminar solicitud de viático',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Submit per diem request for approval
   */
  public function submit(string $id): JsonResponse
  {
    try {
      $request = $this->service->submit((int)$id);

      return response()->json([
        'success' => true,
        'message' => 'Solicitud enviada para aprobación exitosamente',
        'data' => new PerDiemRequestResource($request),
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al enviar solicitud para aprobación',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Mark per diem request as paid
   */
  public function markAsPaid(Request $request, string $id): JsonResponse
  {
    try {
      $validatedData = $request->validate([
        'payment_date' => 'nullable|date',
        'payment_method' => 'nullable|string|in:transfer,cash',
      ]);

      $perDiemRequest = $this->service->markAsPaid((int)$id, $validatedData);

      return response()->json([
        'success' => true,
        'message' => 'Solicitud marcada como pagada exitosamente',
        'data' => new PerDiemRequestResource($perDiemRequest),
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al marcar solicitud como pagada',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Start settlement process
   */
  public function startSettlement(string $id): JsonResponse
  {
    try {
      $request = $this->service->startSettlement((int)$id);

      return response()->json([
        'success' => true,
        'message' => 'Proceso de liquidación iniciado exitosamente',
        'data' => new PerDiemRequestResource($request),
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al iniciar proceso de liquidación',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Complete settlement
   */
  public function completeSettlement(Request $request, string $id): JsonResponse
  {
    try {
      $validatedData = $request->validate([
        'settlement_date' => 'nullable|date',
        'total_spent' => 'nullable|numeric|min:0',
        'balance_to_return' => 'nullable|numeric|min:0',
      ]);

      $perDiemRequest = $this->service->completeSettlement((int)$id, $validatedData);

      return response()->json([
        'success' => true,
        'message' => 'Liquidación completada exitosamente',
        'data' => new PerDiemRequestResource($perDiemRequest),
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al completar liquidación',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Get overdue settlements
   */
  public function overdue(Request $request): JsonResponse
  {
    try {
      $daysOverdue = $request->input('days', 30);
      $requests = $this->service->getOverdueSettlements((int)$daysOverdue);

      return response()->json([
        'success' => true,
        'data' => PerDiemRequestResource::collection($requests),
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al obtener solicitudes vencidas',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Get rates for destination
   */
  public function rates(Request $request): JsonResponse
  {
    try {
      $validated = $request->validate([
        'district_id' => 'required|integer',
        'category_id' => 'required|integer',
      ]);

      $rates = $this->service->getRatesForDestination(
        $validated['district_id'],
        $validated['category_id']
      );

      return response()->json([
        'success' => true,
        'data' => $rates,
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al obtener tarifas',
        'error' => $e->getMessage(),
      ], 500);
    }
  }
}
