<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemPolicyResource;
use App\Services\gp\gestionhumana\viaticos\PerDiemPolicyService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerDiemPolicyController extends Controller
{
  protected PerDiemPolicyService $service;

  public function __construct(PerDiemPolicyService $service)
  {
    $this->service = $service;
  }

  /**
   * Get all policies
   */
  public function index(): JsonResponse
  {
    try {
      $policies = $this->service->getAll();

      return response()->json([
        'success' => true,
        'data' => PerDiemPolicyResource::collection($policies),
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al obtener políticas',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Get current active policy
   */
  public function current(): JsonResponse
  {
    try {
      $policy = $this->service->getCurrent();

      return response()->json([
        'success' => true,
        'data' => $policy ? new PerDiemPolicyResource($policy) : null,
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al obtener política actual',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Create new policy
   */
  public function store(Request $request): JsonResponse
  {
    try {
      $validated = $request->validate([
        'version' => 'required|string|max:50',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'start_date' => 'required|date',
        'end_date' => 'nullable|date|after:start_date',
        'is_current' => 'nullable|boolean',
      ]);

      $policy = $this->service->create($validated);

      return response()->json([
        'success' => true,
        'message' => 'Política creada exitosamente',
        'data' => new PerDiemPolicyResource($policy),
      ], 201);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al crear política',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Update policy
   */
  public function update(Request $request, string $id): JsonResponse
  {
    try {
      $validated = $request->validate([
        'version' => 'nullable|string|max:50',
        'name' => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date',
        'is_current' => 'nullable|boolean',
      ]);

      $policy = $this->service->update((int)$id, $validated);

      return response()->json([
        'success' => true,
        'message' => 'Política actualizada exitosamente',
        'data' => new PerDiemPolicyResource($policy),
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al actualizar política',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Activate policy
   */
  public function activate(string $id): JsonResponse
  {
    try {
      $policy = $this->service->activate((int)$id);

      return response()->json([
        'success' => true,
        'message' => 'Política activada exitosamente',
        'data' => new PerDiemPolicyResource($policy),
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al activar política',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Close policy
   */
  public function close(Request $request, string $id): JsonResponse
  {
    try {
      $validated = $request->validate([
        'end_date' => 'nullable|date',
      ]);

      $policy = $this->service->close((int)$id, $validated['end_date'] ?? null);

      return response()->json([
        'success' => true,
        'message' => 'Política cerrada exitosamente',
        'data' => new PerDiemPolicyResource($policy),
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al cerrar política',
        'error' => $e->getMessage(),
      ], 500);
    }
  }
}
