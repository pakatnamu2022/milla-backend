<?php

namespace App\Http\Services\tp\comercial;

use App\Http\Resources\tp\comercial\OpGoalTravelResource;
use App\Http\Services\BaseService;
use App\Models\tp\comercial\OpGoalTravel;
use App\Models\tp\comercial\Vehicle;
use App\Models\tp\Driver;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;
use Illuminate\Support\Facades\Cache;

class OpGoalTravelService extends BaseService
{

    const MESES = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre'
    ];
    public function list(Request $request)
    {
        $query = OpGoalTravel::query();
        if ($request->has('status_id')) {
            $statusId = $request->input('status_id');

            switch ($statusId) {
                case '0':
                    $query->where('status_deleted', 0);
                    break;
                case '1':
                    $query->where('status_deleted', 1);
                    break;
                case 'all':
                    break;
                default:
                    $query->where('status_deleted', 1);
            }
        } else {
            $query->where('status_deleted', 1);
        }

        $availableYears = $this->getAvailableYears();

        $response = $this->getFilteredResults(
            $query,
            $request,
            OpGoalTravel::filters,
            OpGoalTravel::sorts,
            OpGoalTravelResource::class
        );

        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);


            $data['available_years'] = $availableYears;

            return response()->json($data);
        }

        return $response;
    }

    public function getAvailableYears()
    {
        try {

            $years = OpGoalTravel::where('status_deleted', 1)
                ->get()
                ->map(function ($item) {
                    if ($item->fecha && !is_null($item->fecha)) {
                        try {

                            return $item->fecha->year;
                        } catch (Exception $e) {
                            return null;
                        }
                    }

                    return null;
                })
                ->filter()
                ->unique()
                ->sortDesc()
                ->values()
                ->toArray();

            return $years;
        } catch (\Exception $e) {
            Log::error('Error getting available years: ' . $e->getMessage());
            return [];
        }
    }


    public function store($data)
    {
        DB::beginTransaction();
        try {
            $driverCount = Driver::where('sede_id', 1)->count();
            $vehicleCount = Vehicle::where('tercero', 0)
                ->where('sede_id', 1)
                ->where('tipo_vehiculo_id', 1)
                ->where('vehiculo_status', 1)
                ->where('status_deleted', 1)
                ->count();

            if ($driverCount === 0 || $vehicleCount === 0) {
                throw new Exception("No hay conductores o vehículos activos para calcular las metas.");
            }

            $goalTravel = OpGoalTravel::create([
                'fecha' => $data['date'],
                'total' => $data['total'],
                'meta_conductor' => $data['total'] / $driverCount,
                'meta_vehiculo' => $data['total'] / $vehicleCount,
                'total_unidades' => $vehicleCount,
                'status_deleted' => 1,
            ]);

            DB::commit();
            return [
                'success' => true,
                'data' => new OpGoalTravelResource($goalTravel),
                'message' => 'Meta creada exitosamente'
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error("Error en el servicio de metas de viajes" . $th->getMessage());
            throw $th;
        }
    }

    public function show($id)
    {
        $goalTravel = OpGoalTravel::where('status_deleted', 1)
            ->findOrFail($id);
        return new OpGoalTravelResource($goalTravel);
    }

    public function update($data)
    {
        DB::beginTransaction();
        try {
            $goalTravel = OpGoalTravel::where('status_deleted', 1)
                ->findOrFail($data['id']);

            if (!$goalTravel) {
                throw new Exception('Meta no encontrado');
            }
            $driverCount = Driver::where('sede_id', 1)->count();
            $vehicleCount = Vehicle::where('tercero', 0)
                ->where('sede_id', 1)
                ->where('tipo_vehiculo_id', 1)
                ->where('vehiculo_status', 1)
                ->where('status_deleted', 1)
                ->count();

            if ($driverCount === 0 || $vehicleCount === 0) {
                throw new Exception("No hay conductores o vehículos activos para calcular las metas.");
            }

            $goalTravel->update([
                'fecha' => $data['date'],
                'total' => $data['total'],
                'meta_conductor' => $data['total'] / $driverCount,
                'meta_vehiculo' => $data['total'] / $vehicleCount,
                'total_unidades' => $vehicleCount,
            ]);

            DB::commit();
            return [
                'success' => true,
                'data' => new OpGoalTravelResource($goalTravel->fresh()),
                'message' => 'Meta actualizada exitosamente'
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error("Error en el servicio de meta de viajes: " . $th->getMessage());
            throw $th;
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $freight = OpGoalTravel::where('status_deleted', 1)
                ->findOrFail($id);

            $freight->update(['status_deleted' => 0]);

            DB::commit();
            return [
                'success' => true,
                'message' => 'Meta eliminado exitosamente'
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error("Error en el servicio de meta: " . $th->getMessage());
            throw new Exception("Error al eliminar el meta: " . $th->getMessage());
        }
    }

    public function getComparativaMensual(int $year1, int $month1, ?int $year2 = null, ?int $month2 = null)
    {
        if ($year2 === null || $month2 === null) {
            $year2 = $year1;
            $month2 = $month1 - 1;
            if ($month2 == 0) {
                $month2 = 12;
                $year2 = $year1 - 1;
            }
        }

        $cacheKey = "comparativa_{$year1}_{$month1}_{$year2}_{$month2}";
        return Cache::remember($cacheKey, 3600, function () use ($year1, $month1, $year2, $month2) {
            try {

                //validar fechas
                $fecha1 = \Carbon\Carbon::create($year1, $month1, 1);
                $fecha2 = \Carbon\Carbon::create($year2, $month2, 1);

                if ($fecha1->isFuture() || $fecha2->isFuture()) {
                    throw new Exception("No se pueden comparar períodos futuros.");
                }


                $datos1 = DB::select("
                SELECT 
                    rp.id as cliente_id,
                    rp.nombre_completo as cliente,
                    COUNT(od.id) as total_viajes,
                    SUM(od.produccion) as total_produccion
                    FROM op_despacho od
                    INNER JOIN rrhh_persona rp ON rp.id = od.idcliente
                    WHERE od.estado <> 10
                        AND YEAR(od.fecha_viaje) = ?
                        AND MONTH(od.fecha_viaje) = ?
                    GROUP BY od.idcliente
                    ORDER BY total_viajes DESC
                ", [$year1, $month1]);

                $datos2 = DB::select("
                    SELECT 
                        rp.id as cliente_id,
                        rp.nombre_completo as cliente,
                        COUNT(od.id) as total_viajes,
                        SUM(od.produccion) as total_produccion
                    FROM op_despacho od
                    INNER JOIN rrhh_persona rp ON rp.id = od.idcliente
                    WHERE od.estado <> 10
                        AND YEAR(od.fecha_viaje) = ?
                        AND MONTH(od.fecha_viaje) = ?
                    GROUP BY od.idcliente
                    ORDER BY total_viajes DESC
                ", [$year2, $month2]);

                $total1 = DB::selectOne("
                    SELECT COUNT(od.id) as total_viajes, SUM(od.produccion) as total_produccion
                    FROM op_despacho od
                    WHERE od.estado <> 10 AND YEAR(od.fecha_viaje) = ? AND MONTH(od.fecha_viaje) = ?
                ", [$year1, $month1]);

                $total2 = DB::selectOne("
                    SELECT COUNT(od.id) as total_viajes, SUM(od.produccion) as total_produccion
                    FROM op_despacho od
                    WHERE od.estado <> 10 AND YEAR(od.fecha_viaje) = ? AND MONTH(od.fecha_viaje) = ?
                ", [$year2, $month2]);

                $clientes = [];
                $viajes1 = [];
                $viajes2 = [];
                $produccion1 = [];
                $produccion2 = [];
                $participacion1 = [];
                $participacion2 = [];

                $totalViajes1 = $total1->total_viajes ?? 0;
                $totalProd1 = $total1->total_produccion ?? 0;
                $totalViajes2 = $total2->total_viajes ?? 0;
                $totalProd2 = $total2->total_produccion ?? 0;

                $mapa2 = [];
                foreach ($datos2 as $item) {
                    $mapa2[$item->cliente_id] = [
                        'viajes' => (int) $item->total_viajes,
                        'produccion' => (float) $item->total_produccion
                    ];
                }


                foreach ($datos1 as $item) {
                    $clientes[] = $item->cliente;
                    $viajes1[] = (int) $item->total_viajes;
                    $produccion1[] = (float) $item->total_produccion;
                    $participacion1[] = $totalProd1 > 0
                        ? round(($item->total_produccion / $totalProd1) * 100, 2)
                        : 0;

                    if (isset($mapa2[$item->cliente_id])) {
                        $viajes2[] = $mapa2[$item->cliente_id]['viajes'];
                        $produccion2[] = $mapa2[$item->cliente_id]['produccion'];
                        $participacion2[] = $totalProd2 > 0
                            ? round(($mapa2[$item->cliente_id]['produccion'] / $totalProd2) * 100, 2)
                            : 0;
                    } else {
                        $viajes2[] = 0;
                        $produccion2[] = 0;
                        $participacion2[] = 0;
                    }
                }

                return [
                    'clientes' => $clientes,
                    'viajes_actual' => $viajes1,
                    'viajes_anterior' => $viajes2,
                    'produccion_actual' => $produccion1,
                    'produccion_anterior' => $produccion2,
                    'participacion_actual' => $participacion1,
                    'participacion_anterior' => $participacion2,
                    'resumen' => [
                        'actual' => [
                            'viajes' => (int) $totalViajes1,
                            'produccion' => (float) $totalProd1,
                            'label' => self::MESES[$month1] . ' ' . $year1,
                        ],
                        'anterior' => [
                            'viajes' => (int) $totalViajes2,
                            'produccion' => (float) $totalProd2,
                            'label' => self::MESES[$month2] . ' ' . $year2,
                        ]
                    ],
                    'periodo_actual' => [
                        'mes' => $month1,
                        'anio' => $year1,
                        'label' => self::MESES[$month1] . ' ' . $year1
                    ],
                    'periodo_anterior' => [
                        'mes' => $month2,
                        'anio' => $year2,
                        'label' => self::MESES[$month2] . ' ' . $year2
                    ]
                ];
            } catch (Throwable $th) {
                Log::error("Error en el servicio de comparativa mensual: " . $th->getMessage());
                throw new Exception("Error al obtener la comparativa mensual: " . $th->getMessage());
            }
        });
    }

    public function getViajesNoFacturados(int $dias = 4, ?int $year = null, ?int $month = null)
    {

        $monthKey = $month ?? 'all';
        $cacheKey = "viajes_no_facturados_{$dias}_{$year}_{$monthKey}";

        return Cache::remember($cacheKey, 600, function () use ($dias, $year, $month) {
            try {

                if ($year === null) {
                    $year = (int)date('Y');
                }

                $fechaLimite = date('Y-m-d', strtotime("-{$dias} days"));

                $filtrosFecha = "AND YEAR(od.fecha_viaje) = ?";
                $params = [$fechaLimite, $year];

                if ($month !== null) {
                    $filtrosFecha .= " AND MONTH(od.fecha_viaje) = ?";
                    $params[] = $month;
                }

                $viajesFacturados = DB::table('fac_viaje_asignada')
                    ->where('status_deleted', 1)
                    ->pluck('viaje_id')
                    ->toArray();
                $viajesFacturados = empty($viajesFacturados) ? [0] : $viajesFacturados;

                $resultados = DB::select("
                    SELECT 
                        rp.id as cliente_id,
                        rp.nombre_completo as cliente,
                        COUNT(od.id) as total_viajes,
                        SUM(od.produccion) as total_produccion,
                        MIN(od.fecha_viaje) as viaje_mas_antiguo,
                        MAX(od.fecha_viaje) as viaje_mas_reciente,
                        GROUP_CONCAT(DISTINCT CONCAT('TPV', LPAD(od.id, 8, '0')) SEPARATOR ', ') as codigos_viajes
                    FROM op_despacho od
                    INNER JOIN rrhh_persona rp ON rp.id = od.idcliente
                    WHERE od.estado <> 10
                        AND od.por_facturar = 1
                        AND od.fecha_viaje <= ?
                        AND od.id NOT IN (" . implode(',', $viajesFacturados) . ")
                        {$filtrosFecha}
                    GROUP BY od.idcliente
                    HAVING COUNT(od.id) > 0
                    ORDER BY total_viajes DESC
                    LIMIT 500
                    ", $params);

                $totalGeneral = DB::selectOne("
                    SELECT 
                        COUNT(od.id) as total_viajes,
                        SUM(od.produccion) as total_produccion
                    FROM op_despacho od
                    WHERE od.estado <> 10
                        AND od.por_facturar = 1
                        AND od.fecha_viaje <= ?
                        AND od.id NOT IN (" . implode(',', $viajesFacturados) . ")
                        {$filtrosFecha}
                        ", $params);

                return [
                    'data' => $resultados,
                    'resumen' => [
                        'total_viajes' => $totalGeneral ? (int) $totalGeneral->total_viajes : 0,
                        'total_produccion' => $totalGeneral ? (float) $totalGeneral->total_produccion : 0,
                        'dias_umbral' => $dias,
                        'fecha_limite' => $fechaLimite,
                        'periodo' => [
                            'anio' => $year,
                            'mes' => $month,
                        ]
                    ]
                ];
            } catch (Throwable $th) {
                Log::error("Error en el servicio de viajes no facturados: " . $th->getMessage());
                throw new Exception("Error al obtener los viajes no facturados: " . $th->getMessage());
            }
        });
    }

    public function getDashboardData($year, $month)
    {
        $cacheKey = "dashboard_{$year}_{$month}";

        return Cache::remember($cacheKey, 300, function () use ($year, $month) {
            try {
                $goal = OpGoalTravel::whereYear('fecha', $year)
                    ->whereMonth('fecha', $month)
                    ->where('status_deleted', 1)
                    ->first();

                if (!$goal) {
                    return [
                        'meta' => null,
                        'conductores' => [],
                        'vehiculos' => [],
                        'resumen' => null
                    ];
                }
                $conductores = DB::select("
                            SELECT 
                                rp.id as conductor_id,
                                rp.nombre_completo as conductor,
                                COUNT(od.id) as total_viajes,
                                SUM(od.produccion) as produccion_real,
                                ? as meta_conductor,
                                ROUND((SUM(od.produccion) / ?) * 100, 2) as porcentaje_cumplimiento
                            FROM op_despacho od
                            INNER JOIN rrhh_persona rp ON rp.id = od.conductor_id
                            WHERE od.estado <> 10
                                AND YEAR(od.fecha_viaje) = ?
                                AND MONTH(od.fecha_viaje) = ?
                            GROUP BY od.conductor_id
                            ORDER BY produccion_real DESC
                        ", [$goal->meta_conductor, $goal->meta_conductor, $year, $month]);

                $vehiculos = DB::select("
                            SELECT 
                                v.id as vehiculo_id,
                                v.placa as vehiculo,
                                COUNT(od.id) as total_viajes,
                                SUM(od.produccion) as produccion_real,
                                ? as meta_vehiculo,
                                ROUND((SUM(od.produccion) / ?) * 100, 2) as porcentaje_cumplimiento
                            FROM op_despacho od
                            INNER JOIN op_vehiculo v ON v.id = od.tracto_id
                            WHERE od.estado <> 10
                                AND YEAR(od.fecha_viaje) = ?
                                AND MONTH(od.fecha_viaje) = ?
                            GROUP BY od.tracto_id
                            ORDER BY produccion_real DESC
                        ", [$goal->meta_vehiculo, $goal->meta_vehiculo, $year, $month]);

                $resumen = DB::selectOne("
                        SELECT 
                            COUNT(DISTINCT od.conductor_id) as conductores_activos,
                            COUNT(DISTINCT od.tracto_id) as vehiculos_activos,
                            COUNT(od.id) as total_viajes,
                            SUM(od.produccion) as produccion_total,
                            ? as meta_total,
                            ROUND((SUM(od.produccion) / ?) * 100, 2) as porcentaje_cumplimiento
                        FROM op_despacho od
                        WHERE od.estado <> 10
                            AND YEAR(od.fecha_viaje) = ?
                            AND MONTH(od.fecha_viaje) = ?
                    ", [$goal->total, $goal->total, $year, $month]);

                return [
                    'meta' => [
                        'id' => (int) $goal->id,
                        'fecha' => $goal->fecha ? $goal->fecha->format('Y-m-d') : null,
                        'total' => (float) $goal->total,
                        'meta_conductor' => (float) $goal->meta_conductor,
                        'meta_vehiculo' => (float) $goal->meta_vehiculo,
                        'total_unidades' => (int) $goal->total_unidades,
                    ],
                    'conductores' => array_map(function ($item) {
                        return [
                            'conductor_id' => (int) $item->conductor_id,
                            'conductor' => $item->conductor ?? 'Sin conductor',
                            'total_viajes' => (int) $item->total_viajes,
                            'produccion_real' => (float) $item->produccion_real,
                            'meta_conductor' => (float) $item->meta_conductor,
                            'porcentaje_cumplimiento' => (float) $item->porcentaje_cumplimiento,
                        ];
                    }, $conductores),
                    'vehiculos' => array_map(function ($item) {
                        return [
                            'vehiculo_id' => (int) $item->vehiculo_id,
                            'vehiculo' => $item->vehiculo ?? 'Sin vehículo',
                            'total_viajes' => (int) $item->total_viajes,
                            'produccion_real' => (float) $item->produccion_real,
                            'meta_vehiculo' => (float) $item->meta_vehiculo,
                            'porcentaje_cumplimiento' => (float) $item->porcentaje_cumplimiento,
                        ];
                    }, $vehiculos),
                    'resumen' => $resumen ? [
                        'conductores_activos' => (int) $resumen->conductores_activos,
                        'vehiculos_activos' => (int) $resumen->vehiculos_activos,
                        'total_viajes' => (int) $resumen->total_viajes,
                        'produccion_total' => (float) $resumen->produccion_total,
                        'meta_total' => (float) $resumen->meta_total,
                        'porcentaje_cumplimiento' => (float) $resumen->porcentaje_cumplimiento,
                    ] : null
                ];
            } catch (Throwable $th) {
                Log::error("Error en el servicio dashboard: " . $th->getMessage());
                throw new Exception("Error al obtener los datos para el dashboard: " . $th->getMessage());
            }
        });
    }

    public function getRanking(string $periodo = 'month', int $limit = 10, ?int $year = null, ?int $month = null)
    {

        $year = $year ?? date('Y');
        $month = $month ?? date('m');
        $cacheKey = "ranking_{$periodo}_{$year}_{$month}_{$limit}";
        return Cache::remember($cacheKey, 300, function () use ($periodo, $limit, $year, $month) {
            try {
                if ($periodo === 'week') {
                    // Para semana: usamos la semana del año/mes especificado
                    $dateCondition = "YEARWEEK(od.fecha_viaje) = YEARWEEK('{$year}-{$month}-01')";
                } else {
                    // Para mes: año y mes específicos
                    $dateCondition = "YEAR(od.fecha_viaje) = {$year} AND MONTH(od.fecha_viaje) = {$month}";
                }
                $ranking = DB::select("
                SELECT 
                    rp.id as conductor_id,
                    rp.nombre_completo as conductor,
                    COUNT(od.id) as total_viajes,
                    COALESCE(SUM(od.produccion), 0) as produccion_total,
                    COALESCE(AVG(od.produccion), 0) as promedio_por_viaje,
                    COUNT(DISTINCT od.tracto_id) as vehiculos_usados,
                    MIN(od.fecha_viaje) as primer_viaje,
                    MAX(od.fecha_viaje) as ultimo_viaje
                FROM rrhh_persona rp
                LEFT JOIN op_despacho od ON od.conductor_id = rp.id 
                    AND od.estado = 9
                    AND {$dateCondition}
                WHERE rp.b_empleado = 1 
                    AND rp.status_id = 22 
                    AND rp.status_deleted = 1
                GROUP BY rp.id, rp.nombre_completo
                HAVING COUNT(od.id) > 0
                ORDER BY produccion_total DESC
                LIMIT ?
            ", [$limit]);

                $result = [];
                foreach ($ranking as $index => $item) {
                    $medal = '';
                    if ($index === 0) $medal = '🥇';
                    else if ($index === 1) $medal = '🥈';
                    else if ($index === 2) $medal = '🥉';

                    $result[] = [
                        'position' => $index + 1,
                        'medal' => $medal,
                        'conductor_id' => (int) $item->conductor_id,
                        'conductor' => $item->conductor,
                        'total_viajes' => (int) $item->total_viajes,
                        'produccion_total' => (float) $item->produccion_total,
                        'promedio_por_viaje' => (float) $item->promedio_por_viaje,
                        'vehiculos_usados' => (int) $item->vehiculos_usados,
                        'periodo' => "{$year}-{$month}",
                    ];
                }

                return $result;
            } catch (\Exception $e) {
                Log::error('Error en getRanking: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    public function getAlerts(int $threshold = 70, ?int $year = null, ?int $month = null)
    {
        $cacheKey = "alerts_{$threshold}_{$year}_{$month}";

        return Cache::remember($cacheKey, 300, function () use ($threshold, $year, $month) {
            try {
                $year = $year ?? date('Y');
                $month = $month ?? date('m');

                $goal = OpGoalTravel::whereYear('fecha', $year)
                    ->whereMonth('fecha', $month)
                    ->where('status_deleted', 1)
                    ->first();

                if (!$goal) {
                    return [
                        'conductores' => [],
                        'vehiculos' => [],
                        'periodo' => "{$year}-{$month}"
                    ];
                }

                // Conductores por debajo del umbral
                $conductores = DB::select("
                    SELECT 
                        rp.id as conductor_id,
                        rp.nombre_completo as conductor,
                        COUNT(od.id) as total_viajes,
                        COALESCE(SUM(od.produccion), 0) as produccion,
                        ? as meta,
                        COALESCE(ROUND((SUM(od.produccion) / ?) * 100, 2), 0) as porcentaje
                    FROM rrhh_persona rp
                    LEFT JOIN op_despacho od ON od.conductor_id = rp.id 
                        AND od.estado = 9
                        AND YEAR(od.fecha_viaje) = ?
                        AND MONTH(od.fecha_viaje) = ?
                    WHERE rp.b_empleado = 1 
                        AND rp.status_id = 22 
                        AND rp.status_deleted = 1
                    GROUP BY rp.id, rp.nombre_completo
                    HAVING porcentaje < ? AND porcentaje > 0
                ", [$goal->meta_conductor, $goal->meta_conductor, $year, $month, $threshold]);

                // Vehículos por debajo del umbral
                $vehiculos = DB::select("
                    SELECT 
                        v.id as vehiculo_id,
                        v.placa as vehiculo,
                        COUNT(od.id) as total_viajes,
                        COALESCE(SUM(od.produccion), 0) as produccion,
                        ? as meta,
                        COALESCE(ROUND((SUM(od.produccion) / ?) * 100, 2), 0) as porcentaje
                    FROM op_vehiculo v
                    LEFT JOIN op_despacho od ON od.tracto_id = v.id 
                        AND od.estado = 9
                        AND YEAR(od.fecha_viaje) = ?
                        AND MONTH(od.fecha_viaje) = ?
                    WHERE v.tipo_vehiculo_id = 1 
                        AND v.status_deleted = 1 
                        AND v.vehiculo_status = 1
                        AND v.tercero = 0
                    GROUP BY v.id, v.placa
                    HAVING porcentaje < ? AND porcentaje > 0
                ", [$goal->meta_vehiculo, $goal->meta_vehiculo, $year, $month, $threshold]);

                return [
                    'conductores' => array_map(function ($item) {
                        return [
                            'conductor_id' => (int) $item->conductor_id,
                            'conductor' => $item->conductor,
                            'total_viajes' => (int) $item->total_viajes,
                            'produccion' => (float) $item->produccion,
                            'meta' => (float) $item->meta,
                            'porcentaje' => (float) $item->porcentaje,
                        ];
                    }, $conductores),
                    'vehiculos' => array_map(function ($item) {
                        return [
                            'vehiculo_id' => (int) $item->vehiculo_id,
                            'vehiculo' => $item->vehiculo,
                            'total_viajes' => (int) $item->total_viajes,
                            'produccion' => (float) $item->produccion,
                            'meta' => (float) $item->meta,
                            'porcentaje' => (float) $item->porcentaje,
                        ];
                    }, $vehiculos)
                ];
            } catch (\Exception $e) {
                Log::error('Error en getAlerts: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * Obtiene datos para el análisis estratégico
     * - Tendencia últimos 6 meses
     * - Top clientes (crecimiento/decrecimiento)
     * - Proyección de cierre del mes actual
     * - Distribución por cliente
     */
    public function getAnalisisEstrategico(): array
    {
        $yearActual = (int) date('Y');
        $monthActual = (int) date('m');

        $tendencia = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = $monthActual - $i;
            $anio = $yearActual;
            if ($mes <= 0) {
                $mes += 12;
                $anio--;
            }
            if ($mes > 12) {
                $mes -= 12;
                $anio++;
            }

            $meta = OpGoalTravel::whereYear('fecha', $anio)
                ->whereMonth('fecha', $mes)
                ->where('status_deleted', 1)
                ->first();

            $real = DB::selectOne("
            SELECT COALESCE(SUM(produccion), 0) as total
            FROM op_despacho
            WHERE estado <> 10
                AND YEAR(fecha_viaje) = ? AND MONTH(fecha_viaje) = ?
        ", [$anio, $mes]);

            $metaValue = (float) ($meta->total ?? 0);
            $realValue = (float) ($real->total ?? 0);

            $tendencia[] = [
                'periodo' => self::MESES[$mes] . ' ' . $anio,
                'meta' => $metaValue,
                'real' => $realValue,
                'cumplimiento' => $metaValue > 0 ? round(($realValue / $metaValue) * 100, 2) : 0,
            ];
        }

        // 2. Top clientes (crecimiento vs decrecimiento)
        $mesAnterior = $monthActual - 1;
        $anioAnterior = $yearActual;
        if ($mesAnterior == 0) {
            $mesAnterior = 12;
            $anioAnterior--;
        }

        $clientesActual = DB::select("
        SELECT 
            rp.id as cliente_id,
            rp.nombre_completo as cliente,
            SUM(od.produccion) as produccion
        FROM op_despacho od
        INNER JOIN rrhh_persona rp ON rp.id = od.idcliente
        WHERE od.estado <> 10
            AND YEAR(od.fecha_viaje) = ? AND MONTH(od.fecha_viaje) = ?
        GROUP BY od.idcliente
    ", [$yearActual, $monthActual]);

        $clientesAnterior = DB::select("
        SELECT 
            rp.id as cliente_id,
            rp.nombre_completo as cliente,
            SUM(od.produccion) as produccion
        FROM op_despacho od
        INNER JOIN rrhh_persona rp ON rp.id = od.idcliente
        WHERE od.estado <> 10
            AND YEAR(od.fecha_viaje) = ? AND MONTH(od.fecha_viaje) = ?
        GROUP BY od.idcliente
    ", [$anioAnterior, $mesAnterior]);

        // Mapear producción anterior por cliente_id
        $mapaAnterior = [];
        foreach ($clientesAnterior as $item) {
            $mapaAnterior[$item->cliente_id] = (float) $item->produccion;
        }

        $topCrecimiento = [];
        $topDecrecimiento = [];

        foreach ($clientesActual as $item) {
            $actual = (float) $item->produccion;
            $anterior = $mapaAnterior[$item->cliente_id] ?? 0;
            $diferencia = $actual - $anterior;
            $variacion = $anterior > 0 ? round(($diferencia / $anterior) * 100, 2) : ($actual > 0 ? 100 : 0);

            $data = [
                'cliente_id' => (int) $item->cliente_id,
                'cliente' => $item->cliente,
                'actual' => $actual,
                'anterior' => $anterior,
                'diferencia' => $diferencia,
                'variacion' => $variacion,
            ];

            if ($diferencia > 0) {
                $topCrecimiento[] = $data;
            } elseif ($diferencia < 0) {
                $topDecrecimiento[] = $data;
            }
        }

        // Ordenar: mayor crecimiento primero, mayor decrecimiento primero (más negativo)
        usort($topCrecimiento, fn($a, $b) => $b['diferencia'] <=> $a['diferencia']);
        usort($topDecrecimiento, fn($a, $b) => $a['diferencia'] <=> $b['diferencia']);

        // 3. Proyección de cierre del mes actual
        $diasTranscurridos = (int) date('d');
        $diasTotales = (int) date('t');

        $acumuladoMes = DB::selectOne("
        SELECT COALESCE(SUM(produccion), 0) as total
        FROM op_despacho
        WHERE estado <> 10
            AND YEAR(fecha_viaje) = ? AND MONTH(fecha_viaje) = ?
    ", [$yearActual, $monthActual]);

        $acumulado = (float) ($acumuladoMes->total ?? 0);
        $promedioDiario = $diasTranscurridos > 0 ? $acumulado / $diasTranscurridos : 0;
        $proyeccion = $promedioDiario * $diasTotales;

        $metaActual = OpGoalTravel::whereYear('fecha', $yearActual)
            ->whereMonth('fecha', $monthActual)
            ->where('status_deleted', 1)
            ->first();

        $metaValor = (float) ($metaActual->total ?? 0);
        $cumplimientoProyectado = $metaValor > 0 ? round(($proyeccion / $metaValor) * 100, 2) : 0;

        // 4. Distribución por cliente (Pareto)
        $distribucion = DB::select("
        SELECT 
            rp.nombre_completo as cliente,
            SUM(od.produccion) as produccion
        FROM op_despacho od
        INNER JOIN rrhh_persona rp ON rp.id = od.idcliente
        WHERE od.estado <> 10
            AND YEAR(od.fecha_viaje) = ? AND MONTH(od.fecha_viaje) = ?
        GROUP BY od.idcliente
        ORDER BY produccion DESC
    ", [$yearActual, $monthActual]);

        $totalProduccion = array_sum(array_column($distribucion, 'produccion'));
        $distribucion = array_map(function ($item) use ($totalProduccion) {
            return [
                'cliente' => $item->cliente,
                'produccion' => (float) $item->produccion,
                'porcentaje' => $totalProduccion > 0 ? round(($item->produccion / $totalProduccion) * 100, 2) : 0,
            ];
        }, $distribucion);

        // Calcular Pareto acumulado
        $acumuladoPareto = 0;
        foreach ($distribucion as &$item) {
            $acumuladoPareto += $item['porcentaje'];
            $item['acumulado'] = round($acumuladoPareto, 2);
        }

        return [
            'tendencia' => $tendencia,
            'top_crecimiento' => array_slice($topCrecimiento, 0, 5),
            'top_decrecimiento' => array_slice($topDecrecimiento, 0, 5),
            'proyeccion' => [
                'acumulado' => $acumulado,
                'promedio_diario' => $promedioDiario,
                'proyeccion' => $proyeccion,
                'meta' => $metaValor,
                'cumplimiento' => $cumplimientoProyectado,
                'dias_transcurridos' => $diasTranscurridos,
                'dias_totales' => $diasTotales,
            ],
            'distribucion' => $distribucion,
        ];
    }
}
