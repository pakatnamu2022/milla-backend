<?php

namespace App\Http\Controllers\ap\compras;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\compras\UnidadesReportRequest;
use App\Http\Services\ap\compras\ApUnidadesReportService;
use Illuminate\Http\JsonResponse;

/**
 * Dashboard de Unidades (Órdenes de Compra Comerciales).
 * Reproduce dinámicamente los resúmenes de la TABLA 1 del Excel de vencimientos.
 *
 * Endpoints:
 *   GET /api/ap/commercial/dashboard/unidades/resumen       → pivot estatus × sede
 *   GET /api/ap/commercial/dashboard/unidades/dashboard     → indicadores generales
 *   GET /api/ap/commercial/dashboard/unidades/vencimientos  → distribución por rango de días
 */
class ApUnidadesDashboardController extends Controller
{
    public function __construct(protected ApUnidadesReportService $service) {}

    /**
     * Resumen pivotado: filas = estatus, columnas = sedes.
     *
     * Parámetros opcionales (query string):
     *   estatus, sede, renovaciones (0-8),
     *   dias_vencido_min, dias_vencido_max,
     *   fecha_emision_desde, fecha_emision_hasta,
     *   fecha_vencimiento_desde, fecha_vencimiento_hasta
     */
    public function resumen(UnidadesReportRequest $request): JsonResponse
    {
        try {
            $data = $this->service->getResumen($request->validated());
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Indicadores generales: totales, por sede, por estatus, por renovaciones,
     * por rango de vencimiento. Acepta los mismos filtros que /resumen.
     */
    public function dashboard(UnidadesReportRequest $request): JsonResponse
    {
        try {
            $data = $this->service->getDashboard($request->validated());
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Distribución por rangos de días vencidos (equivale a la clasificación de
     * renovaciones del Excel: 01-30, 31-60, …, 241-270).
     * Acepta los mismos filtros que /resumen.
     */
    public function vencimientos(UnidadesReportRequest $request): JsonResponse
    {
        try {
            $data = $this->service->getVencimientos($request->validated());
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
