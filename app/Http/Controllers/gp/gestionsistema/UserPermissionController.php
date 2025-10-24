<?php

namespace App\Http\Controllers\gp\gestionsistema;

use App\Http\Controllers\Controller;
use App\Http\Services\gp\gestionsistema\UserPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPermissionController extends Controller
{
  protected UserPermissionService $service;

  public function __construct(UserPermissionService $service)
  {
    $this->service = $service;
  }

  /**
   * Obtener todos los permisos del usuario autenticado
   *
   * @OA\Get(
   *     path="/api/users/permissions",
   *     tags={"User Permissions"},
   *     summary="Obtener permisos del usuario autenticado",
   *     security={{"sanctum":{}}},
   *     @OA\Response(
   *         response=200,
   *         description="Permisos obtenidos exitosamente"
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="No autenticado"
   *     )
   * )
   */
  public function index(Request $request): JsonResponse
  {
    try {
      $permissions = $this->service->getUserPermissions();

      return response()->json([
        'success' => true,
        'message' => 'Permisos obtenidos exitosamente',
        'data' => $permissions,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al obtener permisos',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Obtener permisos de un usuario específico (solo admin)
   *
   * @OA\Get(
   *     path="/api/users/{userId}/permissions",
   *     tags={"User Permissions"},
   *     summary="Obtener permisos de un usuario específico",
   *     security={{"sanctum":{}}},
   *     @OA\Parameter(
   *         name="userId",
   *         in="path",
   *         required=true,
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Permisos obtenidos exitosamente"
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Usuario no encontrado"
   *     )
   * )
   */
  public function show(Request $request, int $userId): JsonResponse
  {
    try {
      $permissions = $this->service->getUserPermissions($userId);

      return response()->json([
        'success' => true,
        'message' => 'Permisos obtenidos exitosamente',
        'data' => $permissions,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al obtener permisos',
        'error' => $e->getMessage(),
      ], $e->getCode() === 404 ? 404 : 500);
    }
  }

  /**
   * Obtener permisos de un módulo específico
   *
   * @OA\Get(
   *     path="/api/users/permissions/module/{moduleCode}",
   *     tags={"User Permissions"},
   *     summary="Obtener permisos de un módulo específico",
   *     security={{"sanctum":{}}},
   *     @OA\Parameter(
   *         name="moduleCode",
   *         in="path",
   *         required=true,
   *         @OA\Schema(type="string")
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Permisos del módulo obtenidos exitosamente"
   *     )
   * )
   */
  public function modulePermissions(Request $request, string $moduleCode): JsonResponse
  {
    try {
      $permissions = $this->service->getModulePermissions($moduleCode);

      return response()->json([
        'success' => true,
        'message' => 'Permisos del módulo obtenidos exitosamente',
        'data' => $permissions,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al obtener permisos del módulo',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Verificar si el usuario tiene un permiso específico
   *
   * @OA\Post(
   *     path="/api/users/permissions/check",
   *     tags={"User Permissions"},
   *     summary="Verificar si el usuario tiene un permiso",
   *     security={{"sanctum":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             @OA\Property(property="permission_code", type="string", example="vehicle_purchase_order.create")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Verificación exitosa"
   *     )
   * )
   */
  public function checkPermission(Request $request): JsonResponse
  {
    $request->validate([
      'permission_code' => 'required|string',
    ]);

    try {
      $hasPermission = $this->service->hasPermission($request->permission_code);

      return response()->json([
        'success' => true,
        'data' => [
          'permission_code' => $request->permission_code,
          'has_permission' => $hasPermission,
        ],
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al verificar permiso',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Obtener todos los módulos disponibles en el sistema
   *
   * @OA\Get(
   *     path="/api/modules",
   *     tags={"User Permissions"},
   *     summary="Obtener todos los módulos disponibles",
   *     security={{"sanctum":{}}},
   *     @OA\Response(
   *         response=200,
   *         description="Módulos obtenidos exitosamente"
   *     )
   * )
   */
  public function modules(Request $request): JsonResponse
  {
    try {
      $modules = $this->service->getAllModules();

      return response()->json([
        'success' => true,
        'message' => 'Módulos obtenidos exitosamente',
        'data' => $modules,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al obtener módulos',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Limpiar caché de permisos del usuario autenticado
   *
   * @OA\Post(
   *     path="/api/users/permissions/clear-cache",
   *     tags={"User Permissions"},
   *     summary="Limpiar caché de permisos",
   *     security={{"sanctum":{}}},
   *     @OA\Response(
   *         response=200,
   *         description="Caché limpiado exitosamente"
   *     )
   * )
   */
  public function clearCache(Request $request): JsonResponse
  {
    try {
      $this->service->clearUserPermissionsCache(auth()->id());

      return response()->json([
        'success' => true,
        'message' => 'Caché de permisos limpiado exitosamente',
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al limpiar caché',
        'error' => $e->getMessage(),
      ], 500);
    }
  }
}