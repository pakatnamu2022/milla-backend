<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar permisos granulares
 *
 * Uso en rutas:
 * Route::middleware(['auth:sanctum', 'permission:vehicle_purchase_order.export'])
 *     ->get('/api/vehicle-purchase-orders/export', [Controller::class, 'export']);
 *
 * Uso en controllers:
 * public function __construct()
 * {
 *     $this->middleware('permission:vehicle_purchase_order.export')->only('export');
 * }
 */
class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission Código del permiso a verificar
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Verificar que el usuario esté autenticado
        if (!auth()->check()) {
            return response()->json([
                'message' => 'No autenticado',
                'error' => 'Unauthorized'
            ], 401);
        }

        $user = auth()->user();

        // Verificar que el usuario tenga el permiso
        if (!$user->hasPermission($permission)) {
            return response()->json([
                'message' => "No tienes permiso para realizar esta acción: {$permission}",
                'error' => 'Forbidden',
                'required_permission' => $permission
            ], 403);
        }

        return $next($request);
    }
}
