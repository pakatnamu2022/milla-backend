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

    public function getComparativaMensual(int $year, int $month)
    {

        $cacheKey = "comparativa_{$year}_{$month}";
        return Cache::remember($cacheKey, 3600, function () use ($year, $month) {
            try {
            $mesActual = $month;
            $anioActual = $year;

            $mesAnterior = $mesActual - 1;
            $anioAnterior = $year;


            if ($mesAnterior == 0) {
                $mesAnterior = 12;
                $anioAnterior = $year - 1;
            }

            $datosActual = DB::select("
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
            ", [$anioActual, $mesActual]);

            $datosAnterior = DB::select("
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
            ", [$anioAnterior, $mesAnterior]);

            $clientes = [];
            $viajesActual = [];
            $viajesAnterior = [];
            $produccionActual = [];
            $produccionAnterior = [];

            foreach ($datosActual as $item) {
                $clientes[] = $item->cliente;
                $viajesActual[] = (int) $item->total_viajes;
                $produccionActual[] = (float) $item->total_produccion;
            }

            $mapaAnterior = [];
            foreach ($datosAnterior as $item) {
                $mapaAnterior[$item->cliente_id] = [
                    'viajes' => (int) $item->total_viajes,
                    'produccion' => (float) $item->total_produccion
                ];
            }
            foreach ($datosActual as $item) {
                if (isset($mapaAnterior[$item->cliente_id])) {
                    $viajesAnterior[] = $mapaAnterior[$item->cliente_id]['viajes'];
                    $produccionAnterior[] = $mapaAnterior[$item->cliente_id]['produccion'];
                } else {
                    $viajesAnterior[] = 0;
                    $produccionAnterior[] = 0;
                }
            }
             return [
            'clientes' => $clientes,
            'viajes_actual' => $viajesActual,
            'viajes_anterior' => $viajesAnterior,
            'produccion_actual' => $produccionActual,
            'produccion_anterior' => $produccionAnterior,
            'periodo_actual' => [
                'mes' => $mesActual,
                'anio' => $anioActual,
                'label' => date('F Y', mktime(0, 0, 0, $mesActual, 1, $anioActual))
            ],
            'periodo_anterior' => [
                'mes' => $mesAnterior,
                'anio' => $anioAnterior,
                'label' => date('F Y', mktime(0, 0, 0, $mesAnterior, 1, $anioAnterior))
            ]
        ];
        } catch (Throwable $th) {
            Log::error("Error en el servicio de comparativa mensual: " . $th->getMessage());
            throw new Exception("Error al obtener la comparativa mensual: " . $th->getMessage());
        }
        });
    }

    public function getViajesNoFacturados(int $dias = 4) {
        try{

           DB::statement("SET SESSION group_concat_max_len = 1000000");
            $fechaLimite = date('Y-m-d', strtotime("-{$dias} days"));

            $viajesFacturados = DB::table('fac_viaje_asignada')
                ->where('status_deleted', 1)
                ->pluck('viaje_id')
                ->toArray();

            //si no hay viajes facturados, el array estara vacio
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
                AND od.fecha_viaje <= ?
                AND od.id NOT IN (" . implode(',', $viajesFacturados) . ")
            GROUP BY od.idcliente
            HAVING COUNT(od.id) > 0
            ORDER BY total_viajes DESC
            LIMIT 500
        ", [$fechaLimite]);

        $totalGeneral = DB::selectOne("
            SELECT 
                COUNT(od.id) as total_viajes,
                SUM(od.produccion) as total_produccion
            FROM op_despacho od
            WHERE od.estado <> 10
                AND od.fecha_viaje <= ?
                AND od.id NOT IN (" . implode(',', $viajesFacturados) . ")", [$fechaLimite]);

        return [
            'data' => $resultados,
            'resumen' => [
                'total_viajes' => $totalGeneral ? (int) $totalGeneral->total_viajes : 0,
                'total_produccion' => $totalGeneral ? (float) $totalGeneral->total_produccion : 0,
                'dias_umbral' => $dias,
                'fecha_limite' => $fechaLimite
            ]
        ];


        }catch(Throwable $th){
            Log::error("Error en el servicio de viajes no facturados: " . $th->getMessage());
            throw new Exception("Error al obtener los viajes no facturados: " . $th->getMessage());
        }
    }

    public function getDashboardData($year, $month)
    {
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
    }




    public function getRanking(string $periodo = 'month', int $limit = 10, ?int $year = null, ?int $month = null)
    {

            $year = $year ?? date('Y');
            $month = $month ?? date('m');
            $cacheKey = "ranking_{$periodo}_{$year}_{$month}_{$limit}";
        return Cache::remember($cacheKey, 300, function() use ($periodo, $limit, $year, $month) {
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
        try {
            $year = date('Y');
            $month = date('m');

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
    }
}
