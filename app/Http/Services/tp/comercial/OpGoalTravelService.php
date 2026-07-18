<?php

namespace App\Http\Services\tp\comercial;

use App\Http\Resources\tp\comercial\OpGoalTravelResource;
use App\Http\Services\BaseService;
use App\Models\tp\comercial\OpGoalTravel;
use App\Models\tp\comercial\Vehicle;
use App\Models\tp\Driver;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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
    private const CACHE_TTL = 3600;
    private const TOP_LIMIT = 5;
    private const MAX_MONTHS_RANGE = 24;
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
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year1, $month1, $year2, $month2) {
            try {

                //validar fechas
                $fecha1 = Carbon::create($year1, $month1, 1);
                $fecha2 = Carbon::create($year2, $month2, 1);

                if ($fecha1->isFuture() || $fecha2->isFuture()) {
                    throw new Exception("No se pueden comparar períodos futuros.");
                }
                $productosVacios = [109];
                $productosVaciosStr = implode(',', $productosVacios);
                $condicionCarga = "
                (
                    od.produccion > 0
                    OR (rp.b_cliente = 1)
                    OR EXISTS (
                        SELECT 1
                        FROM op_despacho_item odi
                        LEFT JOIN fac_producto_sales fps ON fps.id = odi.idproducto
                        WHERE odi.despacho_id = od.id
                          AND odi.idproducto IS NOT NULL
                          AND odi.idproducto NOT IN ($productosVaciosStr)
                          AND odi.idproducto != 0
                          AND (odi.precio_unit > 0 OR odi.total > 0 OR COALESCE(fps.descripcion, '') NOT LIKE '%VACIO%')
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM op_despacho_item odi2
                        WHERE odi2.despacho_id = od.id
                          AND (odi2.precio_unit > 0 OR odi2.total > 0)
                    )
                )
            ";

                $datos1 = DB::select("
                    SELECT 
                        rp.id as cliente_id,
                        rp.nombre_completo as cliente,
                        COUNT(od.id) as total_viajes,
                        SUM(od.produccion) as total_produccion
                    FROM op_despacho od
                    INNER JOIN rrhh_persona rp ON rp.id = od.idcliente
                    WHERE od.estado <> 10
                        AND rp.sede_id = 1   -- solo clientes de nuestra sede
                        AND YEAR(od.fecha_viaje) = ?
                        AND MONTH(od.fecha_viaje) = ?
                        AND {$condicionCarga}   -- solo viajes con carga real
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
                        AND rp.sede_id = 1
                        AND YEAR(od.fecha_viaje) = ?
                        AND MONTH(od.fecha_viaje) = ?
                        AND {$condicionCarga}
                    GROUP BY od.idcliente
                    ORDER BY total_viajes DESC
                ", [$year2, $month2]);

                $total1 = DB::selectOne("
                    SELECT COUNT(od.id) as total_viajes, SUM(od.produccion) as total_produccion
                    FROM op_despacho od
                    INNER JOIN rrhh_persona rp ON rp.id = od.idcliente
                    WHERE od.estado <> 10
                        AND rp.sede_id = 1
                        AND YEAR(od.fecha_viaje) = ?
                        AND MONTH(od.fecha_viaje) = ?
                        AND {$condicionCarga}
                ", [$year1, $month1]);

                $total2 = DB::selectOne("
                    SELECT COUNT(od.id) as total_viajes, SUM(od.produccion) as total_produccion
                    FROM op_despacho od
                    INNER JOIN rrhh_persona rp ON rp.id = od.idcliente
                    WHERE od.estado <> 10
                        AND rp.sede_id = 1
                        AND YEAR(od.fecha_viaje) = ?
                        AND MONTH(od.fecha_viaje) = ?
                        AND {$condicionCarga}
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

                $viajesExcluir = DB::table('op_despacho_item as odi')
                    ->join('op_despacho as od', 'od.id', '=', 'odi.despacho_id')
                    ->where('od.produccion', 0)
                    ->where(function ($query) {
                        $query->where('odi.idproducto', 89)
                            ->orWhere('odi.idproducto', 90)
                            ->orWhere(function ($q) {
                                $q->where('odi.precio_unit', 0)
                                    ->where('odi.total', 0);
                            });
                    })
                    ->pluck('odi.despacho_id')
                    ->toArray();
                // Combinar con los viajes facturados para excluir
                $viajesExcluir = array_merge($viajesExcluir, $viajesFacturados);
                $viajesExcluir = empty($viajesExcluir) ? [0] : array_unique($viajesExcluir);



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
                        AND od.id NOT IN (" . implode(',', $viajesExcluir) . ")
                        AND rp.sede_id = 1
                        AND rp.b_cliente = 1
                        {$filtrosFecha}
                    GROUP BY od.idcliente
                    HAVING COUNT(od.id) > 0
                    ORDER BY total_viajes DESC
                    ", $params);

                $totalGeneral = DB::selectOne("
                    SELECT 
                        COUNT(od.id) as total_viajes,
                        SUM(od.produccion) as total_produccion
                    FROM op_despacho od
                    WHERE od.estado <> 10
                        AND od.por_facturar = 1
                        AND od.fecha_viaje <= ?
                        AND od.id NOT IN (" . implode(',', $viajesExcluir) . ")
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
                $productosVacios = [109];
                $productosVaciosStr = implode(',', $productosVacios);

                $condicionCargaConductores = "
                    (
                        od.produccion > 0
                        OR (rp.b_cliente = 1)
                        OR EXISTS (
                            SELECT 1
                            FROM op_despacho_item odi
                            LEFT JOIN fac_producto_sales fps ON fps.id = odi.idproducto
                            WHERE odi.despacho_id = od.id
                            AND odi.idproducto IS NOT NULL
                            AND odi.idproducto NOT IN ($productosVaciosStr)
                            AND odi.idproducto != 0
                            AND (odi.precio_unit > 0 OR odi.total > 0 OR COALESCE(fps.descripcion, '') NOT LIKE '%VACIO%')
                        )
                        OR EXISTS (
                            SELECT 1
                            FROM op_despacho_item odi2
                            WHERE odi2.despacho_id = od.id
                            AND (odi2.precio_unit > 0 OR odi2.total > 0)
                        )
                    )
                ";

                $condicionCargaVehiculos = "
                    (
                        od.produccion > 0
                        OR EXISTS (
                            SELECT 1
                            FROM rrhh_persona rp_cliente
                            INNER JOIN op_despacho od_cliente ON od_cliente.idcliente = rp_cliente.id
                            WHERE od_cliente.id = od.id
                            AND rp_cliente.b_cliente = 1
                        )
                        OR EXISTS (
                            SELECT 1
                            FROM op_despacho_item odi
                            LEFT JOIN fac_producto_sales fps ON fps.id = odi.idproducto
                            WHERE odi.despacho_id = od.id
                            AND odi.idproducto IS NOT NULL
                            AND odi.idproducto NOT IN ($productosVaciosStr)
                            AND odi.idproducto != 0
                            AND (odi.precio_unit > 0 OR odi.total > 0 OR COALESCE(fps.descripcion, '') NOT LIKE '%VACIO%')
                        )
                        OR EXISTS (
                            SELECT 1
                            FROM op_despacho_item odi2
                            WHERE odi2.despacho_id = od.id
                            AND (odi2.precio_unit > 0 OR odi2.total > 0)
                        )
                    )
                ";

                $condicionCargaResumen = "
                    (
                        od.produccion > 0
                        OR (rp_cliente.b_cliente = 1)
                        OR EXISTS (
                            SELECT 1
                            FROM op_despacho_item odi
                            LEFT JOIN fac_producto_sales fps ON fps.id = odi.idproducto
                            WHERE odi.despacho_id = od.id
                            AND odi.idproducto IS NOT NULL
                            AND odi.idproducto NOT IN ($productosVaciosStr)
                            AND odi.idproducto != 0
                            AND (odi.precio_unit > 0 OR odi.total > 0 OR COALESCE(fps.descripcion, '') NOT LIKE '%VACIO%')
                        )
                        OR EXISTS (
                            SELECT 1
                            FROM op_despacho_item odi2
                            WHERE odi2.despacho_id = od.id
                            AND (odi2.precio_unit > 0 OR odi2.total > 0)
                        )
                    )
                ";


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
                                    AND rp.status_deleted = 1 
                                    AND rp.b_empleado = 1 
                                    AND rp.status_id = 22 
                                    AND rp.cargo_id in (11,12)
                                    AND rp.sede_id = 1
                                    AND YEAR(od.fecha_viaje) = ?
                                    AND MONTH(od.fecha_viaje) = ?
                                    AND {$condicionCargaConductores}
                            GROUP BY od.conductor_id
                            ORDER BY produccion_real DESC
                        ", [$goal->meta_conductor, $goal->meta_conductor, $year, $month]);

                $vehiculos = DB::select("
                        SELECT 
                            v.id as vehiculo_id,
                            v.placa as vehiculo,
                            COUNT(od.id) as total_viajes,
                            COALESCE(SUM(od.produccion), 0) as produccion_real,
                            ? as meta_vehiculo,
                            COALESCE(ROUND((SUM(od.produccion) / ?) * 100, 2), 0) as porcentaje_cumplimiento
                        FROM op_vehiculo v
                        INNER JOIN op_despacho od ON od.tracto_id = v.id 
                            AND od.estado <> 10 
                            AND YEAR(od.fecha_viaje) = ?
                            AND MONTH(od.fecha_viaje) = ?
                            AND {$condicionCargaVehiculos}
                        WHERE v.sede_id = 1 
                            AND v.tipo_vehiculo_id = 1 
                            AND v.status_deleted = 1 
                            AND v.tercero = 0
                        GROUP BY v.id, v.placa
                        ORDER BY produccion_real DESC
                ", [$goal->meta_vehiculo, $goal->meta_vehiculo, $year, $month]);

                $resumen = DB::selectOne("
                        SELECT 
                            COUNT(DISTINCT CASE 
                                WHEN rp_conductor.id IS NOT NULL 
                                THEN od.conductor_id 
                                ELSE NULL 
                            END) as conductores_activos,
                            COUNT(DISTINCT CASE 
                                WHEN v.id IS NOT NULL 
                                THEN od.tracto_id 
                                ELSE NULL 
                            END) as vehiculos_activos,
                            COUNT(od.id) as total_viajes,
                            COALESCE(SUM(od.produccion), 0) as produccion_total,
                            ? as meta_total,
                            COALESCE(ROUND((SUM(od.produccion) / ?) * 100, 2), 0) as porcentaje_cumplimiento
                        FROM op_despacho od
                        LEFT JOIN rrhh_persona rp_conductor ON rp_conductor.id = od.conductor_id 
                            AND rp_conductor.status_deleted = 1 
                            AND rp_conductor.b_empleado = 1 
                            AND rp_conductor.status_id = 22 
                            AND rp_conductor.cargo_id in (11,12)
                            AND rp_conductor.sede_id = 1
                        LEFT JOIN op_vehiculo v ON v.id = od.tracto_id 
                            AND v.sede_id = 1 
                            AND v.tipo_vehiculo_id = 1 
                            AND v.status_deleted = 1 
                            AND v.tercero = 0
                        LEFT JOIN rrhh_persona rp_cliente ON rp_cliente.id = od.idcliente
                        WHERE od.estado <> 10
                            AND rp_cliente.sede_id = 1
                            AND rp_cliente.b_cliente = 1
                            AND YEAR(od.fecha_viaje) = ?
                            AND MONTH(od.fecha_viaje) = ?
                            AND {$condicionCargaResumen}
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
                $startOfWeek = null;
                $endOfWeek = null;
                $periodoLabel = "{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT);
                if ($periodo === 'week') {
                    $fechaInicio = Carbon::create($year, $month, 1);
                    $startOfWeek = $fechaInicio->copy()->startOfWeek();
                    $endOfWeek = $fechaInicio->copy()->endOfWeek();

                    $dateCondition = "od.fecha_viaje BETWEEN '{$startOfWeek->toDateString()}' AND '{$endOfWeek->toDateString()}'";
                    $periodoLabel = "Semana {$startOfWeek->weekOfYear} - {$year}";
                } else {
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
                        AND od.estado <> 10
                        AND {$dateCondition}
                    WHERE rp.b_empleado = 1 
                        AND rp.status_id = 22 
                        AND rp.status_deleted = 1
                        AND rp.sede_id = 1
                        AND rp.cargo_id in (11,12)
                    GROUP BY rp.id, rp.nombre_completo
                    ORDER BY produccion_total DESC
                    LIMIT ?
                ", [$limit]);

                $result = [];
                $position = 1;


                foreach ($ranking as $index => $item) {
                    $medal = '';
                    if ((float)$item->produccion_total > 0) {
                        if ($index === 0) $medal = '🥇';
                        else if ($index === 1) $medal = '🥈';
                        else if ($index === 2) $medal = '🥉';
                    }

                    $result[] = [
                        'position' => $position++,
                        'medal' => $medal,
                        'conductor_id' => (int) $item->conductor_id,
                        'conductor' => $item->conductor ?? 'Sin conductor',
                        'total_viajes' => (int) $item->total_viajes,
                        'produccion_total' => (float) $item->produccion_total,
                        'promedio_por_viaje' => (float) $item->promedio_por_viaje,
                        'vehiculos_usados' => (int) $item->vehiculos_usados,
                        'periodo' => $periodoLabel,
                        'primer_viaje' => $item->primer_viaje ?? null,
                        'ultimo_viaje' => $item->ultimo_viaje ?? null,
                    ];
                }

                return $result;
            } catch (Exception $e) {
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
                $hoy = date('Y-m-d');

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
                $conductoresVacaciones = DB::table('rrhh_vacaciones as rv')
                                        ->join('rrhh_persona as rp', 'rv.empleado_id', '=', 'rp.id')
                                        ->where('rp.sede_id', 1)
                                        ->where('rp.status_id', 22)
                                        ->whereIn('rp.cargo_id', [11, 12])
                                        ->where('rv.status_deleted', 1)
                                        ->where('rv.aprobacion_jefatura', 0)
                                        ->where('rv.aprobacion_rrhh', 1)
                                        ->where('rv.fecha_inicio', '<=', $hoy)
                                        ->where('rv.fecha_fin', '>=', $hoy)
                                        ->pluck('rp.id')
                                        ->toArray();

                    //si no hay conductores en vacaciones, usar un array vacio para el NOT IN
                    $conductoresVacaciones = empty($conductoresVacaciones) ? [0] : $conductoresVacaciones;


                $productosVacios = [109];
                $productosVaciosStr = implode(',', $productosVacios);

                $condicionCargaConductores = "
                    (
                        od.produccion > 0
                        OR (rp.b_cliente = 1)
                        OR EXISTS (
                            SELECT 1
                            FROM op_despacho_item odi
                            LEFT JOIN fac_producto_sales fps ON fps.id = odi.idproducto
                            WHERE odi.despacho_id = od.id
                            AND odi.idproducto IS NOT NULL
                            AND odi.idproducto NOT IN ($productosVaciosStr)
                            AND odi.idproducto != 0
                            AND (odi.precio_unit > 0 OR odi.total > 0 OR COALESCE(fps.descripcion, '') NOT LIKE '%VACIO%')
                        )
                        OR EXISTS (
                            SELECT 1
                            FROM op_despacho_item odi2
                            WHERE odi2.despacho_id = od.id
                            AND (odi2.precio_unit > 0 OR odi2.total > 0)
                        )
                    )
                ";

                 $condicionCargaVehiculos = "
                    (
                        od.produccion > 0
                        OR EXISTS (
                            SELECT 1
                            FROM rrhh_persona rp_cliente
                            INNER JOIN op_despacho od_cliente ON od_cliente.idcliente = rp_cliente.id
                            WHERE od_cliente.id = od.id
                            AND rp_cliente.b_cliente = 1
                        )
                        OR EXISTS (
                            SELECT 1
                            FROM op_despacho_item odi
                            LEFT JOIN fac_producto_sales fps ON fps.id = odi.idproducto
                            WHERE odi.despacho_id = od.id
                            AND odi.idproducto IS NOT NULL
                            AND odi.idproducto NOT IN ($productosVaciosStr)
                            AND odi.idproducto != 0
                            AND (odi.precio_unit > 0 OR odi.total > 0 OR COALESCE(fps.descripcion, '') NOT LIKE '%VACIO%')
                        )
                        OR EXISTS (
                            SELECT 1
                            FROM op_despacho_item odi2
                            WHERE odi2.despacho_id = od.id
                            AND (odi2.precio_unit > 0 OR odi2.total > 0)
                        )
                    )
                ";

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
                                    AND od.estado <> 10 
                                    AND YEAR(od.fecha_viaje) = ?
                                    AND MONTH(od.fecha_viaje) = ?
                                    AND {$condicionCargaConductores} 
                                WHERE rp.b_empleado = 1 
                                    AND rp.status_id = 22 
                                    AND rp.cargo_id in (11,12)
                                    AND rp.status_deleted = 1
                                    AND rp.sede_id = 1
                                    AND rp.id NOT IN (". implode(',', $conductoresVacaciones) . ") 
                                GROUP BY rp.id, rp.nombre_completo
                                HAVING porcentaje < ?
                                ORDER BY porcentaje ASC
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
                        AND od.estado <> 10  -- MISMA CONDICIÓN que conductores
                        AND YEAR(od.fecha_viaje) = ?
                        AND MONTH(od.fecha_viaje) = ?
                        AND {$condicionCargaVehiculos}
                    WHERE v.tipo_vehiculo_id = 1 
                        AND v.status_deleted = 1 
                        AND v.vehiculo_status = 1
                        AND v.tercero = 0
                        AND v.sede_id = 1
                    GROUP BY v.id, v.placa
                    HAVING porcentaje < ?  -- Ya no excluimos porcentaje = 0
                    ORDER BY porcentaje ASC
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

    private function validateAnalisisData(array $data): bool
    {
        // Verificar que existe tendencia
        if (!isset($data['tendencia']) || !is_array($data['tendencia'])) {
            return false;
        }

        // Si no hay datos de tendencia, es válido pero vacío
        if (empty($data['tendencia'])) {
            return true;
        }

        // Verificar que cada elemento de tendencia tiene los campos requeridos
        foreach ($data['tendencia'] as $item) {
            if (!isset($item['periodo'], $item['meta'], $item['real'], $item['cumplimiento'])) {
                return false;
            }
        }

        return true;
    }

    public function getAnalisisEstrategico(?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        $fechas = $this->prepareAndValidateFechas($fechaInicio, $fechaFin);
        $cacheKey = $this->generateAnalisisCacheKey($fechas);
        $cachedData = Cache::get($cacheKey);

        //verificar que en cache los datos son correctos
        if ($cachedData !== null) {
            if ($this->validateAnalisisData($cachedData)) {
                return $cachedData;
            }
            Cache::forget($cacheKey);
        }
        $data = $this->calculateAnalisisCompleto($fechas);

        if ($this->validateAnalisisData($data)) {
            Cache::put($cacheKey, $data, self::CACHE_TTL);
        }

        return $data;
    }

    private function prepareAndValidateFechas(?string $fechaInicio, ?string $fechaFin): array
    {
        $inicio = $fechaInicio ? Carbon::parse($fechaInicio) : null;
        $fin = $fechaFin ? Carbon::parse($fechaFin) : null;


        if (!$inicio || !$fin) {
            $fin = Carbon::now()->startOfMonth();
            $inicio = $fin->copy()->subMonths(5)->startOfMonth();
        } else {
            if ($inicio->greaterThan($fin)) {
                throw new \Exception("La fecha inicial no puede ser mayor que la fecha final.");
            }

            if ($fin->isFuture()) {
                throw new \Exception("La fecha final no puede ser una fecha futura.");
            }

            if ($inicio->diffInMonths($fin) > self::MAX_MONTHS_RANGE) {
                throw new \Exception("El rango máximo permitido es de " . self::MAX_MONTHS_RANGE . " meses.");
            }

            $inicio = $inicio->copy()->startOfMonth();
            $fin = $fin->copy()->endOfMonth();
        }

        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'inicio_str' => $inicio->toDateString(),
            'fin_str' => $fin->toDateString(),
            'year_inicio' => $inicio->year,
            'month_inicio' => $inicio->month,
            'year_fin' => $fin->year,
            'month_fin' => $fin->month,
        ];
    }

    private function generateAnalisisCacheKey(array $fechas): string
    {
        return 'analisis_estrategico_' . md5(
            $fechas['inicio_str'] . '-' . $fechas['fin_str']
        );
    }

    private function calculateAnalisisCompleto(array $fechas): array
    {
        //obtener tendencias
        $tendencia = $this->getTendenciaOptimizada($fechas);

        //obtener datos de clientes
        $clientesData = $this->getClientesDataOptimizada($fechas);

        //calcular proyección
        $proyeccion = $this->calculateProyeccionOptimizada($fechas);

        //calcular distribución Pareto
        $distribucion = $this->calculateParetoOptimizado($fechas);


        return [
            'tendencia' => $tendencia,
            'top_crecimiento' => array_slice($clientesData['crecimiento'], 0, self::TOP_LIMIT),
            'top_decrecimiento' => array_slice($clientesData['decrecimiento'], 0, self::TOP_LIMIT),
            'clientes_nuevos' => array_slice($clientesData['nuevos'], 0, self::TOP_LIMIT),
            'clientes_inactivos' => array_slice($clientesData['inactivos'], 0, self::TOP_LIMIT),
            'proyeccion' => $proyeccion,
            'distribucion' => $distribucion,
        ];
    }

    private function calculateProyeccionOptimizada(array $fechas): array
    {
        $year = $fechas['year_fin'];
        $month = $fechas['month_fin'];
        $fechaFin = $fechas['fin'];

        // Obtener acumulado del mes
        $acumuladoMes = DB::selectOne("
            SELECT COALESCE(SUM(produccion), 0) as total
            FROM op_despacho
            WHERE estado <> 10
                AND YEAR(fecha_viaje) = ? AND MONTH(fecha_viaje) = ?
        ", [$year, $month]);

        $acumulado = (float) ($acumuladoMes->total ?? 0);
        $diasTranscurridos = min((int) date('d'), $fechaFin->day);
        $diasTotales = $fechaFin->daysInMonth;

        $promedioDiario = $diasTranscurridos > 0 ? $acumulado / $diasTranscurridos : 0;
        $proyeccion = $promedioDiario * $diasTotales;

        // Obtener meta del mes
        $metaActual = OpGoalTravel::where('status_deleted', 1)
            ->whereYear('fecha', $year)
            ->whereMonth('fecha', $month)
            ->first();

        $metaValor = (float) ($metaActual->total ?? 0);
        $cumplimientoProyectado = $metaValor > 0
            ? round(($proyeccion / $metaValor) * 100, 2)
            : 0;

        return [
            'acumulado' => $acumulado,
            'promedio_diario' => $promedioDiario,
            'proyeccion' => $proyeccion,
            'meta' => $metaValor,
            'cumplimiento' => $cumplimientoProyectado,
            'dias_transcurridos' => $diasTranscurridos,
            'dias_totales' => $diasTotales,
            'periodo' => self::MESES[$month] . ' ' . $year,
        ];
    }

    private function getTendenciaOptimizada(array $fechas): array
    {
        $datosMensuales = $this->getDatosMensualesAgrupados($fechas);
        $metas = $this->getMetasPorPeriodo($fechas);
        $tendencia = [];
        $current = $fechas['inicio']->copy();

        while ($current->lte($fechas['fin'])) {
            $mes = $current->month;
            $anio = $current->year;
            $key = "{$anio}-{$mes}";

            $metaValue = (float) ($metas[$key] ?? 0);
            $realValue = (float) ($datosMensuales[$key] ?? 0);
            $cumplimiento = 0;
            if ($metaValue > 0) {
                $cumplimiento = round(($realValue / $metaValue) * 100, 2);
            }

            $tendencia[] = [
                'periodo' => self::MESES[$mes] . ' ' . $anio,
                'meta' => $metaValue,
                'real' => $realValue,
                'cumplimiento' => $cumplimiento,
            ];

            $current->addMonth();
        }
        return $tendencia;
    }

    private function getDatosMensualesAgrupados(array $fechas): array
    {
        $resultados = DB::select("
            SELECT 
                YEAR(fecha_viaje) as year,
                MONTH(fecha_viaje) as month,
                COALESCE(SUM(produccion), 0) as total
            FROM op_despacho
            WHERE estado <> 10
                AND fecha_viaje BETWEEN ? AND ?
            GROUP BY YEAR(fecha_viaje), MONTH(fecha_viaje)
        ", [$fechas['inicio_str'], $fechas['fin_str']]);

        $data = [];
        foreach ($resultados as $item) {
            $data["{$item->year}-{$item->month}"] = (float) $item->total;
        }

        return $data;
    }
    private function getMetasPorPeriodo(array $fechas): array
    {
        $inicioStr = $fechas['inicio']->startOfMonth()->toDateString();
        $finStr = $fechas['fin']->endOfMonth()->toDateString();
        // Obtener todas las metas y procesar en PHP para mayor control
        $metas = OpGoalTravel::where('status_deleted', 1)
            ->whereBetween('fecha', [$inicioStr, $finStr])
            ->get(['fecha', 'total']);

        $data = [];
        foreach ($metas as $meta) {
            if ($meta->fecha instanceof Carbon) {
                $year = $meta->fecha->year;
                $month = $meta->fecha->month;
            } else {
                $fecha = Carbon::parse($meta->fecha);
                $year = $fecha->year;
                $month = $fecha->month;
            }

            $key = "{$year}-{$month}";
            $data[$key] = (float) $meta->total;
        }

        return $data;
    }
    private function calculateParetoOptimizado(array $fechas): array
    {
        $distribucion = DB::select("
            SELECT 
                rp.nombre_completo as cliente,
                COALESCE(SUM(od.produccion), 0) as produccion
            FROM op_despacho od
            INNER JOIN rrhh_persona rp ON rp.id = od.idcliente
            WHERE od.estado <> 10
                AND od.fecha_viaje BETWEEN ? AND ?
            GROUP BY od.idcliente, rp.nombre_completo
            ORDER BY produccion DESC
        ", [$fechas['inicio_str'], $fechas['fin_str']]);

        $totalProduccion = array_sum(array_column($distribucion, 'produccion'));

        if ($totalProduccion == 0) {
            return [];
        }

        $result = [];
        $acumulado = 0;

        foreach ($distribucion as $item) {
            $porcentaje = round(($item->produccion / $totalProduccion) * 100, 2);
            $acumulado += $porcentaje;

            $result[] = [
                'cliente' => $item->cliente,
                'produccion' => (float) $item->produccion,
                'porcentaje' => $porcentaje,
                'acumulado' => round($acumulado, 2),
            ];
        }

        return $result;
    }

    public function getAnalisisEstrategicoConPrediccion(
        ?string $fechaInicio = null,
        ?string $fechaFin = null,
        bool $incluirPrediccion = true,
        int $mesesHistoricos = 12,
        float $factorConfianza = 1.0
    ): array {
        // Obtener análisis base
        $analisis = $this->getAnalisisEstrategico($fechaInicio, $fechaFin);

        if (!$incluirPrediccion) {
            return $analisis;
        }

        // Agregar predicción
        try {
            $prediccionService = new PrediccionCumplimientoService();
            $prediccion = $prediccionService->predecirCumplimiento($mesesHistoricos, $factorConfianza);

            $analisis['prediccion_ia'] = $prediccion;
        } catch (\Exception $e) {
            Log::error('Error en predicción IA', ['error' => $e->getMessage()]);
            $analisis['prediccion_ia'] = [
                'success' => false,
                'message' => 'Error al generar predicción: ' . $e->getMessage()
            ];
        }

        return $analisis;
    }

    private function getClientesDataOptimizada(array $fechas): array
    {
        // Período actual (todo el rango seleccionado)
        $inicioActual = $fechas['inicio_str'];
        $finActual = $fechas['fin_str'];
        $inicioCarbon = Carbon::parse($inicioActual);
        $finCarbon = Carbon::parse($finActual);

        // Período anterior (mismo rango, año anterior)
        $anteriorInicio = $inicioCarbon->copy()->subYear()->toDateString();
        $anteriorFin = $finCarbon->copy()->subYear()->toDateString();
        // 1. Obtener datos agregados por cliente para ambos períodos
        $clientesData = DB::select("
        SELECT 
            rp.id as cliente_id,
            rp.nombre_completo as cliente,
            rp.vat as ruc,
            -- Período actual
            COALESCE(SUM(CASE 
                WHEN DATE(od.fecha_viaje) BETWEEN ? AND ? 
                THEN od.produccion 
                ELSE 0 
            END), 0) as produccion_actual,
            COALESCE(COUNT(CASE 
                WHEN DATE(od.fecha_viaje) BETWEEN ? AND ? 
                THEN od.id 
            END), 0) as viajes_actual,
            COALESCE(AVG(CASE 
                WHEN DATE(od.fecha_viaje) BETWEEN ? AND ? 
                THEN od.produccion 
            END), 0) as promedio_viaje_actual,
            -- Período anterior
            COALESCE(SUM(CASE 
                WHEN DATE(od.fecha_viaje) BETWEEN ? AND ? 
                THEN od.produccion 
                ELSE 0 
            END), 0) as produccion_anterior,
            COALESCE(COUNT(CASE 
                WHEN DATE(od.fecha_viaje) BETWEEN ? AND ? 
                THEN od.id 
            END), 0) as viajes_anterior,
            COALESCE(AVG(CASE 
                WHEN DATE(od.fecha_viaje) BETWEEN ? AND ? 
                THEN od.produccion 
            END), 0) as promedio_viaje_anterior
        FROM op_despacho od
        INNER JOIN rrhh_persona rp ON rp.id = od.idcliente
        WHERE od.estado <> 10
            AND (
                DATE(od.fecha_viaje) BETWEEN ? AND ?
                OR DATE(od.fecha_viaje) BETWEEN ? AND ?
            )
        GROUP BY rp.id, rp.nombre_completo, rp.vat
    ", [
            $inicioActual,
            $finActual,
            $inicioActual,
            $finActual,
            $inicioActual,
            $finActual,
            $anteriorInicio,
            $anteriorFin,
            $anteriorInicio,
            $anteriorFin,
            $anteriorInicio,
            $anteriorFin,
            $inicioActual,
            $finActual,
            $anteriorInicio,
            $anteriorFin
        ]);

        // 2. Obtener datos mensuales por cliente (para tendencias)
        $datosMensuales = $this->getClientesMensuales($fechas);

        // 3. Procesar y enriquecer los datos
        return $this->processClientesDataAvanzado($clientesData, $datosMensuales, $fechas);
    }

    /**
     * Obtiene la producción mensual de cada cliente en el período
     */
    private function getClientesMensuales(array $fechas): array
    {
        $inicio = $fechas['inicio_str'];
        $fin = $fechas['fin_str'];

        $resultados = DB::select("
        SELECT 
            rp.id as cliente_id,
            rp.nombre_completo as cliente,
            YEAR(od.fecha_viaje) as year,
            MONTH(od.fecha_viaje) as month,
            COALESCE(SUM(od.produccion), 0) as produccion,
            COUNT(od.id) as viajes
        FROM op_despacho od
        INNER JOIN rrhh_persona rp ON rp.id = od.idcliente
        WHERE od.estado <> 10
            AND DATE(od.fecha_viaje) BETWEEN ? AND ?
        GROUP BY rp.id, rp.nombre_completo, YEAR(od.fecha_viaje), MONTH(od.fecha_viaje)
        ORDER BY YEAR(od.fecha_viaje), MONTH(od.fecha_viaje)
    ", [$inicio, $fin]);

        $data = [];
        foreach ($resultados as $item) {
            $key = "{$item->cliente_id}";
            if (!isset($data[$key])) {
                $data[$key] = [
                    'cliente_id' => $item->cliente_id,
                    'cliente' => $item->cliente,
                    'mensual' => []
                ];
            }
            $data[$key]['mensual'][] = [
                'periodo' => self::MESES[$item->month] . ' ' . $item->year,
                'year' => $item->year,
                'month' => $item->month,
                'produccion' => (float) $item->produccion,
                'viajes' => (int) $item->viajes
            ];
        }

        return $data;
    }

    /**
     * Procesa datos de clientes con análisis avanzado
     */
    private function processClientesDataAvanzado(array $clientesData, array $datosMensuales, array $fechas): array
    {
        $resultados = [
            'crecimiento' => [],
            'decrecimiento' => [],
            'nuevos' => [],
            'inactivos' => [],
            'top_meses_decrecimiento' => [],
            'resumen' => [
                'total_clientes_actual' => 0,
                'total_clientes_anterior' => 0,
                'clientes_con_crecimiento' => 0,
                'clientes_con_decrecimiento' => 0,
                'clientes_nuevos' => 0,
                'clientes_inactivos' => 0,
                'total_produccion_actual' => 0,
                'total_produccion_anterior' => 0,
                'variacion_total' => 0,
                'porcentaje_variacion' => 0,
                'periodo_actual' => '',
                'periodo_anterior' => '',
                'mejor_mes' => null,
                'peor_mes' => null,
            ]
        ];

        $clientesActual = [];
        $clientesAnterior = [];
        $clientesActualIds = [];
        $clientesAnteriorIds = [];
        $totalActual = 0;
        $totalAnterior = 0;
        $todosMeses = [];

        foreach ($clientesData as $item) {
            $actual = (float) $item->produccion_actual;
            $anterior = (float) $item->produccion_anterior;
            $viajesActual = (int) $item->viajes_actual;
            $viajesAnterior = (int) $item->viajes_anterior;

            if ($actual > 0 || $anterior > 0) {
                $datosMensualesCliente = $datosMensuales[$item->cliente_id]['mensual'] ?? [];
                $tendencia = $this->calcularTendencia($datosMensualesCliente);
                $categoria = $this->categorizarCliente($actual, $anterior);
                $variacion = $anterior > 0
                    ? round((($actual - $anterior) / $anterior) * 100, 2)
                    : ($actual > 0 ? 100 : 0);

                // Recolectar meses para análisis de decrecimiento
                foreach ($datosMensualesCliente as $mes) {
                    $key = $mes['periodo'];
                    if (!isset($todosMeses[$key])) {
                        $todosMeses[$key] = [
                            'periodo' => $mes['periodo'],
                            'year' => $mes['year'],
                            'month' => $mes['month'],
                            'decrementos' => 0,
                            'clientes_afectados' => []
                        ];
                    }
                }

                $clienteInfo = [
                    'cliente_id' => (int) $item->cliente_id,
                    'cliente' => $item->cliente,
                    'ruc' => $item->ruc ?? null,
                    'telefono' => $item->telefono ?? null,
                    'correo' => $item->correo ?? null,
                    // CAMPOS PARA EL FRONTEND (NOMBRES ORIGINALES)
                    'actual' => $actual,
                    'anterior' => $anterior,
                    'variacion' => $variacion,
                    'diferencia' => $actual - $anterior,
                    // CAMPOS ADICIONALES
                    'produccion_actual' => $actual,
                    'produccion_anterior' => $anterior,
                    'viajes_actual' => $viajesActual,
                    'viajes_anterior' => $viajesAnterior,
                    'promedio_viaje_actual' => (float) $item->promedio_viaje_actual,
                    'promedio_viaje_anterior' => (float) $item->promedio_viaje_anterior,
                    'variacion_produccion' => $actual - $anterior,
                    'variacion_viajes' => $viajesActual - $viajesAnterior,
                    'porcentaje_variacion' => $variacion,
                    'mensual' => $datosMensualesCliente,
                    'tendencia' => $tendencia,
                    'categoria' => $categoria,
                    'mes_maximo' => $tendencia['mes_maximo'] ?? null,
                    'mes_minimo' => $tendencia['mes_minimo'] ?? null,
                ];

                if ($actual > 0) {
                    $clientesActual[] = $clienteInfo;
                    $clientesActualIds[] = $item->cliente_id;
                    $totalActual += $actual;
                }

                if ($anterior > 0) {
                    $clientesAnterior[] = $clienteInfo;
                    $clientesAnteriorIds[] = $item->cliente_id;
                    $totalAnterior += $anterior;
                }
            }
        }

        // Clasificar clientes
        foreach ($clientesActual as $cliente) {
            if (!in_array($cliente['cliente_id'], $clientesAnteriorIds)) {
                // Para nuevos clientes, mantener compatibilidad
                $cliente['produccion'] = $cliente['produccion_actual'];
                $cliente['produccion_anterior'] = 0;
                $resultados['nuevos'][] = $cliente;
            } else {
                $variacion = $cliente['variacion_produccion'];
                if ($variacion > 0) {
                    $resultados['crecimiento'][] = $cliente;
                } elseif ($variacion < 0) {
                    $resultados['decrecimiento'][] = $cliente;

                    // Analizar en qué mes tuvo más decrecimiento
                    $meses = $cliente['mensual'] ?? [];
                    for ($i = 1; $i < count($meses); $i++) {
                        $decremento = $meses[$i]['produccion'] - $meses[$i - 1]['produccion'];
                        if ($decremento < 0) {
                            $key = $meses[$i]['periodo'];
                            if (isset($todosMeses[$key])) {
                                $todosMeses[$key]['decrementos'] += abs($decremento);
                                $todosMeses[$key]['clientes_afectados'][] = $cliente['cliente'];
                            }
                        }
                    }
                }
            }
        }

        foreach ($clientesAnterior as $cliente) {
            if (!in_array($cliente['cliente_id'], $clientesActualIds)) {
                // Para clientes inactivos, mantener compatibilidad
                $cliente['produccion'] = $cliente['produccion_anterior'];
                $cliente['produccion_actual'] = 0;
                $resultados['inactivos'][] = $cliente;
            }
        }

        // Ordenar
        usort($resultados['crecimiento'], fn($a, $b) => $b['variacion_produccion'] <=> $a['variacion_produccion']);
        usort($resultados['decrecimiento'], fn($a, $b) => $a['variacion_produccion'] <=> $b['variacion_produccion']);
        usort($resultados['nuevos'], fn($a, $b) => $b['produccion_actual'] <=> $a['produccion_actual']);
        usort($resultados['inactivos'], fn($a, $b) => $b['produccion_anterior'] <=> $a['produccion_anterior']);

        // Top meses con más decrecimiento
        $topMesesDecrecimiento = array_filter($todosMeses, fn($mes) => $mes['decrementos'] > 0);
        usort($topMesesDecrecimiento, fn($a, $b) => $b['decrementos'] <=> $a['decrementos']);
        $resultados['top_meses_decrecimiento'] = array_slice($topMesesDecrecimiento, 0, 5);

        // Calcular mejor y peor mes
        $todosLosMeses = [];
        foreach ($datosMensuales as $cliente) {
            foreach ($cliente['mensual'] as $mes) {
                $key = $mes['periodo'];
                if (!isset($todosLosMeses[$key])) {
                    $todosLosMeses[$key] = ['periodo' => $mes['periodo'], 'total' => 0];
                }
                $todosLosMeses[$key]['total'] += $mes['produccion'];
            }
        }
        $mejorMes = !empty($todosLosMeses) ? array_reduce($todosLosMeses, fn($max, $item) => (!$max || $item['total'] > $max['total']) ? $item : $max) : null;
        $peorMes = !empty($todosLosMeses) ? array_reduce($todosLosMeses, fn($min, $item) => (!$min || $item['total'] < $min['total']) ? $item : $min) : null;

        // Resumen
        $periodoActual = Carbon::parse($fechas['inicio_str'])->format('M Y') . ' - ' . Carbon::parse($fechas['fin_str'])->format('M Y');
        $periodoAnterior = Carbon::parse($fechas['inicio_str'])->subYear()->format('M Y') . ' - ' . Carbon::parse($fechas['fin_str'])->subYear()->format('M Y');
        $variacionTotal = $totalActual - $totalAnterior;
        $porcentajeVariacion = $totalAnterior > 0 ? round(($variacionTotal / $totalAnterior) * 100, 2) : 0;

        $resultados['resumen'] = [
            'total_clientes_actual' => count($clientesActual),
            'total_clientes_anterior' => count($clientesAnterior),
            'clientes_con_crecimiento' => count($resultados['crecimiento']),
            'clientes_con_decrecimiento' => count($resultados['decrecimiento']),
            'clientes_nuevos' => count($resultados['nuevos']),
            'clientes_inactivos' => count($resultados['inactivos']),
            'total_produccion_actual' => $totalActual,
            'total_produccion_anterior' => $totalAnterior,
            'variacion_total' => $variacionTotal,
            'porcentaje_variacion' => $porcentajeVariacion,
            'periodo_actual' => $periodoActual,
            'periodo_anterior' => $periodoAnterior,
            'mejor_mes' => $mejorMes ? ['periodo' => $mejorMes['periodo'], 'total' => $mejorMes['total']] : null,
            'peor_mes' => $peorMes ? ['periodo' => $peorMes['periodo'], 'total' => $peorMes['total']] : null,
        ];

        return $resultados;
    }
    /**
     * Calcula la tendencia de un cliente basado en sus datos mensuales
     */
    private function calcularTendencia(array $datosMensuales): array
    {
        if (count($datosMensuales) < 2) {
            return [
                'tipo' => 'insuficiente',
                'descripcion' => 'Datos insuficientes',
                'cambios_positivos' => 0,
                'cambios_negativos' => 0,
                'variacion_total_periodo' => 0,
                'porcentaje_total' => 0,
                'mes_maximo' => null,
                'mes_minimo' => null,
            ];
        }

        $valores = array_column($datosMensuales, 'produccion');
        $cambios = [];

        for ($i = 1; $i < count($valores); $i++) {
            $cambios[] = $valores[$i] - $valores[$i - 1];
        }

        $positivos = count(array_filter($cambios, fn($v) => $v > 0));
        $negativos = count(array_filter($cambios, fn($v) => $v < 0));

        if ($positivos > $negativos * 1.5) {
            $tipo = 'creciente_fuerte';
            $descripcion = '🚀 Crecimiento acelerado';
        } elseif ($positivos > $negativos) {
            $tipo = 'creciente';
            $descripcion = '📈 Tendencia al alza';
        } elseif ($negativos > $positivos * 1.5) {
            $tipo = 'decreciente_fuerte';
            $descripcion = '⚠️ Caída pronunciada';
        } elseif ($negativos > $positivos) {
            $tipo = 'decreciente';
            $descripcion = '📉 Tendencia a la baja';
        } else {
            $tipo = 'estable';
            $descripcion = '➖ Estable';
        }

        $ultimoMes = end($datosMensuales);
        $primerMes = reset($datosMensuales);

        return [
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'cambios_positivos' => $positivos,
            'cambios_negativos' => $negativos,
            'variacion_total_periodo' => $ultimoMes['produccion'] - $primerMes['produccion'],
            'porcentaje_total' => $primerMes['produccion'] > 0
                ? round((($ultimoMes['produccion'] - $primerMes['produccion']) / $primerMes['produccion']) * 100, 2)
                : 0,
            'mes_maximo' => array_reduce($datosMensuales, function ($max, $item) {
                return (!$max || $item['produccion'] > $max['produccion']) ? $item : $max;
            }, null),
            'mes_minimo' => array_reduce($datosMensuales, function ($min, $item) {
                return (!$min || $item['produccion'] < $min['produccion']) ? $item : $min;
            }, null),
        ];
    }

    /**
     * Categoriza al cliente según su comportamiento
     */
    private function categorizarCliente(float $actual, float $anterior): string
    {
        if ($actual > 0 && $anterior == 0) return 'nuevo';
        if ($actual == 0 && $anterior > 0) return 'inactivo';

        if ($anterior == 0) return 'nuevo';

        $variacion = ($actual - $anterior) / $anterior;

        if ($variacion > 0.3) return 'alto_crecimiento';
        if ($variacion > 0.1) return 'crecimiento';
        if ($variacion > -0.1) return 'estable';
        if ($variacion > -0.3) return 'decrecimiento';
        return 'alto_decrecimiento';
    }



    public function exportComparativaClientes(int $year1, int $month1, ?int $year2 = null, ?int $month2 = null)
    {
        if ($year2 === null || $month2 === null) {
            $year2 = $year1;
            $month2 = $month1 - 1;
            if ($month2 == 0) {
                $month2 = 12;
                $year2 = $year1 - 1;
            }
        }

        // Obtener los mismos datos que en getComparativaMensual
        $fecha1 = Carbon::create($year1, $month1, 1);
        $fecha2 = Carbon::create($year2, $month2, 1);

        $productosVacios = [109];
        $productosVaciosStr = implode(',', $productosVacios);

        $condicionCarga = "
        (
            od.produccion > 0
            OR (rp.b_cliente = 1)
            OR EXISTS (
                SELECT 1
                FROM op_despacho_item odi
                LEFT JOIN fac_producto_sales fps ON fps.id = odi.idproducto
                WHERE odi.despacho_id = od.id
                  AND odi.idproducto IS NOT NULL
                  AND odi.idproducto NOT IN ($productosVaciosStr)
                  AND odi.idproducto != 0
                  AND (odi.precio_unit > 0 OR odi.total > 0 OR COALESCE(fps.descripcion, '') NOT LIKE '%VACIO%')
            )
            OR EXISTS (
                SELECT 1
                FROM op_despacho_item odi2
                WHERE odi2.despacho_id = od.id
                  AND (odi2.precio_unit > 0 OR odi2.total > 0)
            )
        )
    ";

        // Obtener datos detallados de clientes para el período 1 (actual)
        $datosDetallados1 = DB::select("
        SELECT 
            rp.id as cliente_id,
            rp.nombre_completo as cliente,
            rp.vat as ruc,
            od.id as viaje_id,
            CONCAT('TPV', LPAD(od.id, 8, '0')) as codigo_viaje,
            od.fecha_viaje,
            od.produccion,
            od.condiciones,
            od.nliquidacion,
            v.placa as tracto,
            v2.placa as carreta,
            CONCAT(rp_conductor.nombre_completo) as conductor,
            (SELECT GROUP_CONCAT(DISTINCT CONCAT(fps.descripcion, ' (', odi.cantidad, ' und)') SEPARATOR ', ')
             FROM op_despacho_item odi
             LEFT JOIN fac_producto_sales fps ON fps.id = odi.idproducto
             WHERE odi.despacho_id = od.id
            ) as productos,
            (SELECT GROUP_CONCAT(DISTINCT CONCAT(rco.descripcion, ' - ', rci.descripcion) SEPARATOR ', ')
             FROM op_despacho_item odi
             LEFT JOIN fac_ciudades_sales rci ON rci.id = odi.iddestino
             LEFT JOIN fac_ciudades_sales rco ON rco.id = odi.idorigen
             WHERE odi.despacho_id = od.id
            ) as ruta
        FROM op_despacho od
        INNER JOIN rrhh_persona rp ON rp.id = od.idcliente
        LEFT JOIN op_vehiculo v ON v.id = od.tracto_id
        LEFT JOIN op_vehiculo v2 ON v2.id = od.carreta_id
        LEFT JOIN rrhh_persona rp_conductor ON rp_conductor.id = od.conductor_id
        WHERE od.estado <> 10
            AND rp.sede_id = 1
            AND YEAR(od.fecha_viaje) = ?
            AND MONTH(od.fecha_viaje) = ?
            AND {$condicionCarga}
        ORDER BY rp.nombre_completo, od.fecha_viaje DESC
    ", [$year1, $month1]);

        // Obtener datos detallados de clientes para el período 2 (anterior)
        $datosDetallados2 = DB::select("
        SELECT 
            rp.id as cliente_id,
            rp.nombre_completo as cliente,
            rp.vat as ruc,
            od.id as viaje_id,
            CONCAT('TPV', LPAD(od.id, 8, '0')) as codigo_viaje,
            od.fecha_viaje,
            od.produccion,
            od.condiciones,
            od.nliquidacion,
            v.placa as tracto,
            v2.placa as carreta,
            CONCAT(rp_conductor.nombre_completo) as conductor,
            (SELECT GROUP_CONCAT(DISTINCT CONCAT(fps.descripcion, ' (', odi.cantidad, ' und)') SEPARATOR ', ')
             FROM op_despacho_item odi
             LEFT JOIN fac_producto_sales fps ON fps.id = odi.idproducto
             WHERE odi.despacho_id = od.id
            ) as productos,
            (SELECT GROUP_CONCAT(DISTINCT CONCAT(rco.descripcion, ' - ', rci.descripcion) SEPARATOR ', ')
             FROM op_despacho_item odi
             LEFT JOIN fac_ciudades_sales rci ON rci.id = odi.iddestino
             LEFT JOIN fac_ciudades_sales rco ON rco.id = odi.idorigen
             WHERE odi.despacho_id = od.id
            ) as ruta
        FROM op_despacho od
        INNER JOIN rrhh_persona rp ON rp.id = od.idcliente
        LEFT JOIN op_vehiculo v ON v.id = od.tracto_id
        LEFT JOIN op_vehiculo v2 ON v2.id = od.carreta_id
        LEFT JOIN rrhh_persona rp_conductor ON rp_conductor.id = od.conductor_id
        WHERE od.estado <> 10
            AND rp.sede_id = 1
            AND YEAR(od.fecha_viaje) = ?
            AND MONTH(od.fecha_viaje) = ?
            AND {$condicionCarga}
        ORDER BY rp.nombre_completo, od.fecha_viaje DESC
    ", [$year2, $month2]);

        // Obtener resumen por cliente para ambos períodos
        $totalViajes1 = count($datosDetallados1);
        $totalProd1 = array_sum(array_column($datosDetallados1, 'produccion'));
        $totalViajes2 = count($datosDetallados2);
        $totalProd2 = array_sum(array_column($datosDetallados2, 'produccion'));

        // 2. Obtener datos resumidos por cliente para ambos períodos
        $clientesMap = [];

        // Procesar datos actuales
        foreach ($datosDetallados1 as $item) {
            $id = $item->cliente_id;
            if (!isset($clientesMap[$id])) {
                $clientesMap[$id] = [
                    'cliente' => $item->cliente,
                    'viajes1' => 0,
                    'prod1' => 0,
                    'viajes2' => 0,
                    'prod2' => 0
                ];
            }
            $clientesMap[$id]['viajes1']++;
            $clientesMap[$id]['prod1'] += (float) $item->produccion;
        }

        // Procesar datos anteriores
        foreach ($datosDetallados2 as $item) {
            $id = $item->cliente_id;
            if (!isset($clientesMap[$id])) {
                $clientesMap[$id] = [
                    'cliente' => $item->cliente,
                    'viajes1' => 0,
                    'prod1' => 0,
                    'viajes2' => 0,
                    'prod2' => 0
                ];
            }
            $clientesMap[$id]['viajes2']++;
            $clientesMap[$id]['prod2'] += (float) $item->produccion;
        }

        // 3. Construir arrays para la hoja de comparativa
        $clientes = [];
        $viajes1 = [];
        $viajes2 = [];
        $produccion1 = [];
        $produccion2 = [];

        foreach ($clientesMap as $id => $data) {
            $clientes[] = $data['cliente'];
            $viajes1[] = $data['viajes1'];
            $viajes2[] = $data['viajes2'];
            $produccion1[] = $data['prod1'];
            $produccion2[] = $data['prod2'];
        }

        // Ordenar por viajes actuales descendente
        array_multisort($viajes1, SORT_DESC, $clientes, $viajes2, $produccion1, $produccion2);

        // Calcular participaciones
        $participacion1 = [];
        $participacion2 = [];
        foreach ($produccion1 as $prod) {
            $participacion1[] = $totalProd1 > 0 ? round(($prod / $totalProd1) * 100, 2) : 0;
        }
        foreach ($produccion2 as $prod) {
            $participacion2[] = $totalProd2 > 0 ? round(($prod / $totalProd2) * 100, 2) : 0;
        }

        // 4. Construir el resumen para la hoja de comparativa
        $resumenComparativa = [
            'clientes' => $clientes,
            'viajes_actual' => $viajes1,
            'viajes_anterior' => $viajes2,
            'produccion_actual' => $produccion1,
            'produccion_anterior' => $produccion2,
            'participacion_actual' => $participacion1,
            'participacion_anterior' => $participacion2,
            'resumen' => [
                'actual' => [
                    'viajes' => $totalViajes1,
                    'produccion' => $totalProd1,
                    'label' => self::MESES[$month1] . ' ' . $year1,
                ],
                'anterior' => [
                    'viajes' => $totalViajes2,
                    'produccion' => $totalProd2,
                    'label' => self::MESES[$month2] . ' ' . $year2,
                ]
            ]
        ];



        // Generar Excel
        $spreadsheet = new Spreadsheet();

        // Hoja 1: Resumen General
        $this->crearHojaResumen($spreadsheet, $resumenComparativa, $year1, $month1, $year2, $month2);

        // Hoja 2: Detalle de Viajes - Período Actual
        $this->crearHojaDetalleViajes($spreadsheet, $datosDetallados1, $year1, $month1, 'Actual');

        // Hoja 3: Detalle de Viajes - Período Anterior
        $this->crearHojaDetalleViajes($spreadsheet, $datosDetallados2, $year2, $month2, 'Anterior');

        // Hoja 4: Comparativa por Cliente
        $this->crearHojaComparativaCliente($spreadsheet, $resumenComparativa);

        // Configurar el writer y descargar
        $writer = new Xlsx($spreadsheet);

        $filename = 'Comparativa_Clientes_' . $year1 . '-' . str_pad($month1, 2, '0', STR_PAD_LEFT) .
            '_vs_' . $year2 . '-' . str_pad($month2, 2, '0', STR_PAD_LEFT) . '.xlsx';

        $tempFile = tempnam(sys_get_temp_dir(), 'export_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    private function crearHojaResumen($spreadsheet, $resumen, $year1, $month1, $year2, $month2)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen');

        // Título
        $sheet->setCellValue('A1', 'COMPARATIVA MENSUAL DE PRODUCCIÓN POR CLIENTE');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Subtítulo
        $periodo1 = self::MESES[$month1] . ' ' . $year1;
        $periodo2 = self::MESES[$month2] . ' ' . $year2;
        $sheet->setCellValue('A2', 'Comparando: ' . $periodo1 . ' vs ' . $periodo2);
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        // Fila vacía
        $sheet->setCellValue('A4', '');

        // Encabezados
        $sheet->setCellValue('A4', 'Cliente');
        $sheet->setCellValue('B4', 'Viajes ' . $periodo1);
        $sheet->setCellValue('C4', 'Producción ' . $periodo1);
        $sheet->setCellValue('D4', 'Viajes ' . $periodo2);
        $sheet->setCellValue('E4', 'Producción ' . $periodo2);
        $sheet->setCellValue('F4', 'Variación Viajes');
        $sheet->setCellValue('G4', 'Variación Producción');

        // Estilo de encabezados
        $sheet->getStyle('A4:G4')->getFont()->setBold(true);
        $sheet->getStyle('A4:G4')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A4:G4')->getFont()->getColor()->setRGB('FFFFFF');

        // Datos
        $row = 5;
        $totalActual = 0;
        $totalAnterior = 0;

        foreach ($resumen['clientes'] as $index => $cliente) {
            $sheet->setCellValue('A' . $row, $cliente);
            $sheet->setCellValue('B' . $row, $resumen['viajes_actual'][$index] ?? 0);
            $sheet->setCellValue('C' . $row, $resumen['produccion_actual'][$index] ?? 0);
            $sheet->setCellValue('D' . $row, $resumen['viajes_anterior'][$index] ?? 0);
            $sheet->setCellValue('E' . $row, $resumen['produccion_anterior'][$index] ?? 0);

            $varViajes = ($resumen['viajes_actual'][$index] ?? 0) - ($resumen['viajes_anterior'][$index] ?? 0);
            $varProd = ($resumen['produccion_actual'][$index] ?? 0) - ($resumen['produccion_anterior'][$index] ?? 0);

            $sheet->setCellValue('F' . $row, $varViajes);
            $sheet->setCellValue('G' . $row, $varProd);

            // Color según variación
            if ($varProd > 0) {
                $sheet->getStyle('G' . $row)->getFont()->getColor()->setRGB('00B050');
            } elseif ($varProd < 0) {
                $sheet->getStyle('G' . $row)->getFont()->getColor()->setRGB('FF0000');
            }

            $totalActual += $resumen['produccion_actual'][$index] ?? 0;
            $totalAnterior += $resumen['produccion_anterior'][$index] ?? 0;
            $row++;
        }

        // Fila de totales
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, array_sum($resumen['viajes_actual']));
        $sheet->setCellValue('C' . $row, $totalActual);
        $sheet->setCellValue('D' . $row, array_sum($resumen['viajes_anterior']));
        $sheet->setCellValue('E' . $row, $totalAnterior);
        $sheet->setCellValue('F' . $row, array_sum($resumen['viajes_actual']) - array_sum($resumen['viajes_anterior']));
        $sheet->setCellValue('G' . $row, $totalActual - $totalAnterior);

        $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':G' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E7E6E6');

        // Ajustar columnas
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function crearHojaDetalleViajes($spreadsheet, $datos, $year, $month, $periodo)
    {
        // Crear nueva hoja
        $sheet = $spreadsheet->createSheet();
        $hojaNombre = 'Detalle Viajes ' . $periodo;
        $sheet->setTitle($hojaNombre);

        $rowIndex = 1;

        // Título
        $sheet->setCellValue('A' . $rowIndex, 'DETALLE DE VIAJES - PERÍODO ' . strtoupper($periodo));
        $sheet->mergeCells('A' . $rowIndex . ':K' . $rowIndex);
        $sheet->getStyle('A' . $rowIndex)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $rowIndex)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $rowIndex++;

        // Período
        $sheet->setCellValue('A' . $rowIndex, 'Período: ' . self::MESES[$month] . ' ' . $year);
        $sheet->mergeCells('A' . $rowIndex . ':K' . $rowIndex);
        $sheet->getStyle('A' . $rowIndex)->getFont()->setItalic(true);
        $rowIndex += 2;

        // Encabezados
        $headers = [
            'Cliente',
            'RUC',
            'Código Viaje',
            'Fecha',
            'Producción',
            'Conductor',
            'Tracto',
            'Carreta',
            'Productos',
            'Ruta',
            'N° Liquidación'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $rowIndex, $header);
            $col++;
        }

        $sheet->getStyle('A' . $rowIndex . ':K' . $rowIndex)->getFont()->setBold(true);
        $sheet->getStyle('A' . $rowIndex . ':K' . $rowIndex)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A' . $rowIndex . ':K' . $rowIndex)->getFont()->getColor()->setRGB('FFFFFF');
        $rowIndex++;

        // Datos
        foreach ($datos as $item) {
            $sheet->setCellValue('A' . $rowIndex, $item->cliente);
            $sheet->setCellValue('B' . $rowIndex, $item->ruc ?? '');
            $sheet->setCellValue('C' . $rowIndex, $item->codigo_viaje);
            $sheet->setCellValue('D' . $rowIndex, $item->fecha_viaje);
            $sheet->setCellValue('E' . $rowIndex, (float)$item->produccion);
            $sheet->setCellValue('F' . $rowIndex, $item->conductor ?? '');
            $sheet->setCellValue('G' . $rowIndex, $item->tracto ?? '');
            $sheet->setCellValue('H' . $rowIndex, $item->carreta ?? '');
            $sheet->setCellValue('I' . $rowIndex, $item->productos ?? '');
            $sheet->setCellValue('J' . $rowIndex, $item->ruta ?? '');
            $sheet->setCellValue('K' . $rowIndex, $item->nliquidacion ?? '');
            $rowIndex++;
        }

        // Formato de números para producción
        $sheet->getStyle('E5:E' . ($rowIndex - 1))
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        // Ajustar columnas
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function crearHojaComparativaCliente($spreadsheet, $resumen)
    {
        // Crear nueva hoja
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Comparativa Clientes');

        $rowIndex = 1;

        // Título
        $sheet->setCellValue('A' . $rowIndex, 'COMPARATIVA POR CLIENTE (DETALLADO)');
        $sheet->mergeCells('A' . $rowIndex . ':H' . $rowIndex);
        $sheet->getStyle('A' . $rowIndex)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $rowIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $rowIndex += 2;

        // Encabezados
        $headers = [
            'Cliente',
            'Viajes Actual',
            'Producción Actual',
            'Participación Actual (%)',
            'Viajes Anterior',
            'Producción Anterior',
            'Participación Anterior (%)',
            'Variación Viajes',
            'Variación Producción'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $rowIndex, $header);
            $col++;
        }

        $sheet->getStyle('A' . $rowIndex . ':I' . $rowIndex)->getFont()->setBold(true);
        $sheet->getStyle('A' . $rowIndex . ':I' . $rowIndex)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A' . $rowIndex . ':I' . $rowIndex)->getFont()->getColor()->setRGB('FFFFFF');
        $rowIndex++;

        // Datos
        $totalActual = array_sum($resumen['produccion_actual']);
        $totalAnterior = array_sum($resumen['produccion_anterior']);

        foreach ($resumen['clientes'] as $index => $cliente) {
            $viajesActual = $resumen['viajes_actual'][$index] ?? 0;
            $prodActual = $resumen['produccion_actual'][$index] ?? 0;
            $pctActual = $totalActual > 0 ? round(($prodActual / $totalActual) * 100, 2) : 0;

            $viajesAnterior = $resumen['viajes_anterior'][$index] ?? 0;
            $prodAnterior = $resumen['produccion_anterior'][$index] ?? 0;
            $pctAnterior = $totalAnterior > 0 ? round(($prodAnterior / $totalAnterior) * 100, 2) : 0;

            $sheet->setCellValue('A' . $rowIndex, $cliente);
            $sheet->setCellValue('B' . $rowIndex, $viajesActual);
            $sheet->setCellValue('C' . $rowIndex, $prodActual);
            $sheet->setCellValue('D' . $rowIndex, $pctActual);
            $sheet->setCellValue('E' . $rowIndex, $viajesAnterior);
            $sheet->setCellValue('F' . $rowIndex, $prodAnterior);
            $sheet->setCellValue('G' . $rowIndex, $pctAnterior);

            $sheet->setCellValue('H' . $rowIndex, $viajesActual - $viajesAnterior);
            $varProd = $prodActual - $prodAnterior;
            $sheet->setCellValue('I' . $rowIndex, $varProd);

            // Color según variación de producción
            if ($varProd > 0) {
                $sheet->getStyle('I' . $rowIndex)->getFont()->getColor()->setRGB('00B050');
            } elseif ($varProd < 0) {
                $sheet->getStyle('I' . $rowIndex)->getFont()->getColor()->setRGB('FF0000');
            }

            $rowIndex++;
        }

        // Fila de totales
        $sheet->setCellValue('A' . $rowIndex, 'TOTAL');
        $sheet->setCellValue('B' . $rowIndex, array_sum($resumen['viajes_actual']));
        $sheet->setCellValue('C' . $rowIndex, $totalActual);
        $sheet->setCellValue('D' . $rowIndex, 100);
        $sheet->setCellValue('E' . $rowIndex, array_sum($resumen['viajes_anterior']));
        $sheet->setCellValue('F' . $rowIndex, $totalAnterior);
        $sheet->setCellValue('G' . $rowIndex, 100);
        $sheet->setCellValue('H' . $rowIndex, array_sum($resumen['viajes_actual']) - array_sum($resumen['viajes_anterior']));
        $sheet->setCellValue('I' . $rowIndex, $totalActual - $totalAnterior);

        $sheet->getStyle('A' . $rowIndex . ':I' . $rowIndex)->getFont()->setBold(true);
        $sheet->getStyle('A' . $rowIndex . ':I' . $rowIndex)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E7E6E6');

        // Formato de moneda para producción
        $sheet->getStyle('C5:C' . ($rowIndex - 1))
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');
        $sheet->getStyle('F5:F' . ($rowIndex - 1))
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');
        $sheet->getStyle('I5:I' . $rowIndex)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        // Formato de porcentaje
        $sheet->getStyle('D5:D' . ($rowIndex - 1))
            ->getNumberFormat()
            ->setFormatCode('0.00%');
        $sheet->getStyle('G5:G' . ($rowIndex - 1))
            ->getNumberFormat()
            ->setFormatCode('0.00%');

        // Ajustar columnas
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
