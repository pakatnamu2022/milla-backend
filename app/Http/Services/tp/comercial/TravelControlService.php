<?php

namespace App\Http\Services\tp\comercial;

use App\Http\Resources\tp\comercial\DriverTravelRecordResource;
use App\Http\Resources\tp\comercial\TravelControlResource;
use App\Http\Services\BaseService;
use App\Models\tp\comercial\TravelControl;
use App\Models\tp\comercial\DriverTravelRecord;
use App\Models\tp\comercial\TravelExpense;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TravelControlService extends BaseService
{
    public function list(Request $request)
    {
        
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');

        $user = auth()->user();
        $userRole = $user->roles()->first();
        $userName = $user->username;

        // Construir query principal con subqueries (MANTENER IGUAL AL CONTROLLER ORIGINAL)
        $query = DB::table('op_despacho')
            ->selectRaw($this->getViajeQuery())
            ->addSelect([
                'conductor_nombre' => DB::table('rrhh_persona')
                    ->select('nombre_completo')
                    ->whereColumn('id', 'op_despacho.conductor_id')
                    ->limit(1),
                'tracto_placa' => DB::table('op_vehiculo')
                    ->select('placa')
                    ->whereColumn('id', 'op_despacho.tracto_id')
                    ->limit(1),
                'carreta_placa' => DB::table('op_vehiculo')
                    ->select('placa')
                    ->whereColumn('id', 'op_despacho.carreta_id')
                    ->limit(1),
                'cliente_nombre' => DB::table('rrhh_persona')
                    ->select('nombre_completo')
                    ->whereColumn('id', 'op_despacho.idcliente')
                    ->limit(1),
                'estado_descripcion' => DB::table('op_despacho_estados')
                    ->select('descripcion')
                    ->whereColumn('id', 'op_despacho.estado')
                    ->limit(1),
                'estado_color' => DB::table('op_despacho_estados')
                    ->select('color2')
                    ->whereColumn('id', 'op_despacho.estado')
                    ->limit(1),
                'estado_porcentaje' => DB::table('op_despacho_estados')
                    ->select('porcentaje')
                    ->whereColumn('id', 'op_despacho.estado')
                    ->limit(1),
                'estado_norden' => DB::table('op_despacho_estados')
                    ->select('norden')
                    ->whereColumn('id', 'op_despacho.estado')
                    ->limit(1)
            ])
            ->whereNotIn('op_despacho.estado', [10])
            ->orderBy('op_despacho.fecha_viaje', 'DESC');

        // Filtro por rol de conductor
        if ($userRole && $userRole->id == 106) {
            $conductorId = DB::table('rrhh_persona')
                ->where('vat', $userName)
                ->value('id');
            if ($conductorId) {
                $query->where('op_despacho.conductor_id', $conductorId);
            }
        }

        // Filtro por estado
        if ($status !== 'all') {
            $status = (int) $status;
            $camposObligatoriosCompletado = ['op_despacho.km_inicio', 'op_despacho.km_fin'];

            switch ($status) {
                case 3:
                    $query->whereIn('op_despacho.estado', [3, 4, 5, 6, 7])
                        ->whereNotNull('op_despacho.km_inicio');
                    break;
                case 9:
                    $query->where('op_despacho.estado', $status);
                    foreach ($camposObligatoriosCompletado as $campo) {
                        $query->whereNotNull($campo);
                    }
                    break;
                default:
                    $query->where('op_despacho.estado', $status);
            }
        }

        // Búsqueda
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('op_despacho.id', 'LIKE', "%{$search}%")
                    ->orWhereExists(function ($subQuery) use ($search) {
                        $subQuery->select(DB::raw(1))
                            ->from('rrhh_persona')
                            ->whereColumn('rrhh_persona.id', 'op_despacho.conductor_id')
                            ->where('rrhh_persona.nombre_completo', 'LIKE', "%{$search}%");
                    })
                    ->orWhereExists(function ($subQuery) use ($search) {
                        $subQuery->select(DB::raw(1))
                            ->from('op_vehiculo')
                            ->whereColumn('op_vehiculo.id', 'op_despacho.tracto_id')
                            ->where('op_vehiculo.placa', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Paginación
        $travels = $query->paginate($perPage);

        // Cargar relaciones adicionales para cada viaje (IGUAL AL CONTROLLER ORIGINAL)
        $travelRelations = [];
        foreach ($travels->items() as $travel) {
            $travelArray = (array) $travel;
            
            // Items del despacho
            $travelArray['items'] = DB::table('op_despacho_item')
                ->select('id', 'despacho_id', 'cantidad', 'idproducto', 'idorigen', 'iddestino')
                ->where('despacho_id', $travel->id)
                ->get()
                ->toArray();
            
            // Registros del conductor
            $travelArray['driver_records'] = DB::table('driver_travel_record')
                ->select('id', 'dispatch_id', 'record_type', 'recorded_at', 
                        'recorded_mileage', 'notes', 'device_id')
                ->where('dispatch_id', $travel->id)
                ->orderBy('recorded_at')
                ->get()
                ->toArray();
            
            // Gastos de combustible
            $travelArray['gastos'] = DB::table('op_gastos_viaje')
                ->select('id', 'viaje_id', 'monto', 'km_tanqueo', 'created_at')
                ->where('viaje_id', $travel->id)
                ->where('concepto_id', 25)
                ->get()
                ->toArray();

            $travelArray['photos'] = DB::table('tp_travel_photo')
                ->where('dispatch_id', $travel->id)
                ->get()
                ->toArray();
            
            // Campos adicionales para frontend (MANTENER IGUAL)
            $travelArray['estado'] = $travel->idestado;
            $travelArray['tripNumber'] = $travel->codigo;
            $travelArray['plate'] = $travel->tracto_placa;
            $travelArray['route'] = $travel->ruta;
            $travelArray['client'] = $travel->cliente_nombre;
            
            $travelRelations[] = $travelArray;
        }

        $transformedData = TravelControlResource::collection(
            collect($travelRelations)
        );

        return [
            'data' => $transformedData,
            'links' => [
                'first' => $travels->url(1),
                'last' => $travels->url($travels->lastPage()),
                'prev' => $travels->previousPageUrl(),
                'next' => $travels->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $travels->currentPage(),
                'from' => $travels->firstItem(),
                'last_page' => $travels->lastPage(),
                'links' => $travels->linkCollection()->toArray(),
                'path' => $travels->path(),
                'per_page' => $travels->perPage(),
                'to' => $travels->lastItem(),
                'total' => $travels->total(),
            ]
        ];
    }

    public function store($data)
    {
        try {
            DB::beginTransaction();

            $viaje = TravelControl::create([
                'conductor_id' => $data['conductor_id'],
                'tracto_id' => $data['tracto_id'],
                'carreta_id' => $data['carreta_id'] ?? null,
                'idcliente' => $data['idcliente'],
                'estado' => 1,
                'fecha_viaje' => $data['fecha_viaje'],
                'observacion_comercial' => $data['observacion_comercial'] ?? null,
                'proxima_prog' => $data['proxima_prog'] ?? null,
            ]);

            DB::commit();

            return [
                'message' => 'Viaje creado correctamente',
                'data' => new TravelControlResource($viaje)
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error al crear el viaje: ' . $e->getMessage());
        }
    }

    public function find($id)
    {
        $viaje = DB::table('op_despacho')
            ->selectRaw($this->getViajeQuery())
            ->addSelect([
                'conductor_nombre' => DB::table('rrhh_persona')
                    ->select('nombre_completo')
                    ->whereColumn('id', 'op_despacho.conductor_id')
                    ->limit(1),
                'conductor_documento' => DB::table('rrhh_persona')
                    ->select('documento')
                    ->whereColumn('id', 'op_despacho.conductor_id')
                    ->limit(1),
                'conductor_telefono' => DB::table('rrhh_persona')
                    ->select('telefono')
                    ->whereColumn('id', 'op_despacho.conductor_id')
                    ->limit(1),
                'conductor_email' => DB::table('rrhh_persona')
                    ->select('email')
                    ->whereColumn('id', 'op_despacho.conductor_id')
                    ->limit(1),
                'tracto_placa' => DB::table('op_vehiculo')
                    ->select('placa')
                    ->whereColumn('id', 'op_despacho.tracto_id')
                    ->limit(1),
                'tracto_marca' => DB::table('op_vehiculo')
                    ->select('marca')
                    ->whereColumn('id', 'op_despacho.tracto_id')
                    ->limit(1),
                'tracto_modelo' => DB::table('op_vehiculo')
                    ->select('modelo')
                    ->whereColumn('id', 'op_despacho.tracto_id')
                    ->limit(1),
                'carreta_placa' => DB::table('op_vehiculo')
                    ->select('placa')
                    ->whereColumn('id', 'op_despacho.carreta_id')
                    ->limit(1),
                'cliente_nombre' => DB::table('rrhh_persona')
                    ->select('nombre_completo')
                    ->whereColumn('id', 'op_despacho.idcliente')
                    ->limit(1),
                'cliente_ruc' => DB::table('rrhh_persona')
                    ->select('ruc')
                    ->whereColumn('id', 'op_despacho.idcliente')
                    ->limit(1),
                'estado_descripcion' => DB::table('op_despacho_estados')
                    ->select('descripcion')
                    ->whereColumn('id', 'op_despacho.estado')
                    ->limit(1),
                'estado_color' => DB::table('op_despacho_estados')
                    ->select('color2')
                    ->whereColumn('id', 'op_despacho.estado')
                    ->limit(1),
                'estado_porcentaje' => DB::table('op_despacho_estados')
                    ->select('porcentaje')
                    ->whereColumn('id', 'op_despacho.estado')
                    ->limit(1),
                'estado_norden' => DB::table('op_despacho_estados')
                    ->select('norden')
                    ->whereColumn('id', 'op_despacho.estado')
                    ->limit(1)
            ])
            ->where('op_despacho.id', $id)
            ->whereNotIn('op_despacho.estado', [9, 10])
            ->first();

        if (!$viaje) {
            throw new Exception('Viaje no encontrado');
        }

        return $viaje;
    }

    public function show($id)
    {
        $travelData = $this->find($id);
        
        $travelArray = (array) $travelData;
        
        $travelArray['items'] = DB::table('op_despacho_item as odi')
            ->select(
                'odi.id',
                'odi.despacho_id',
                'odi.cantidad',
                'odi.idproducto',
                'odi.idorigen',
                'odi.iddestino',
                'pp.descripcion as producto_descripcion',
                'rco.descripcion as origen_descripcion',
                'rci.descripcion as destino_descripcion'
            )
            ->leftJoin('fac_producto_sales as pp', 'pp.id', '=', 'odi.idproducto')
            ->leftJoin('fac_ciudades_sales as rco', 'rco.id', '=', 'odi.idorigen')
            ->leftJoin('fac_ciudades_sales as rci', 'rci.id', '=', 'odi.iddestino')
            ->where('odi.despacho_id', $id)
            ->get()
            ->toArray();
        
        $travelArray['driver_records'] = DB::table('driver_travel_record')
            ->select('*')
            ->where('dispatch_id', $id)
            ->orderBy('recorded_at')
            ->get()
            ->toArray();
        
        $travelArray['gastos'] = DB::table('op_gastos_viaje')
            ->select('op_gastos_viaje.*', 'gc.nombre as concepto_nombre')
            ->leftJoin('gasto_concepto as gc', 'gc.id', '=', 'op_gastos_viaje.concepto_id')
            ->where('viaje_id', $id)
            ->get()
            ->toArray();

        $travelArray['photos'] = DB::table('tp_travel_photo')
            ->where('dispatch_id', $id)
            ->get()
            ->toArray();
        
        $travelArray['driver'] = [
            'id' => $travelData->conductor_id,
            'name' => $travelData->conductor_nombre,
            'documento' => $travelData->conductor_documento,
            'telefono' => $travelData->conductor_telefono,
            'email' => $travelData->conductor_email
        ];
        
        $travelArray['vehicle'] = [
            'placa' => $travelData->tracto_placa,
            'marca' => $travelData->tracto_marca,
            'modelo' => $travelData->tracto_modelo
        ];
        
        $viajeArray['estado_info'] = [
            'descripcion' => $travelData->estado_descripcion,
            'color' => $travelData->estado_color,
            'porcentaje' => $travelData->estado_porcentaje,
            'norden' => $travelData->estado_norden
        ];

        // Retornar datos crudos, NO usar Resource
        return new TravelControlResource((object) $travelArray);
    }

    public function update($data)
    {
        $travel = TravelControl::activos()->find($data['id']);
        
        if (!$travel) {
            throw new Exception('Viaje no encontrado');
        }

        $updateData = [];
        $fields = ['conductor_id', 'tracto_id', 'carreta_id', 'idcliente', 'observacion_comercial', 'proxima_prog'];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        $travel->update($updateData);
        
        $travel->load(['items', 'driver_records', 'photos']);

        return [
            'message' => 'Viaje actualizado correctamente',
            'data' => new TravelControlResource($travel)
        ];
    }

    public function destroy($id)
    {
        $viaje = TravelControl::find($id);

        if (!$viaje) {
            throw new Exception('Viaje no encontrado');
        }

        if ($viaje->estado != 1) {
            throw new Exception('Solo se pueden eliminar viajes en estado pendiente');
        }

        $viaje->delete();

        return [
            'message' => 'Viaje eliminado correctamente',
            'data' => [
                'id' => $id,
                'deleted_at' => now()->format('Y-m-d H:i:s')
            ]
        ];
    }

    public function startRoute($data)
    {
        try {
            DB::beginTransaction();

            $travel = TravelControl::activos()->find($data['id']);
            
            if (!$travel) {
                throw new Exception('Viaje no encontrado');
            }

            if ($travel->estado != 1) {
                throw new Exception('El viaje no se puede iniciar en su estado actual');
            }

            $latestKm = $this->getLatestMileage($travel->tracto_id);
            if ($data['mileage'] < $latestKm) {
                throw new Exception("El kilometraje no puede ser menor al último registro ({$latestKm} km)");
            }

            DriverTravelRecord::create([
                'dispatch_id' => $travel->id,
                'driver_id' => $travel->conductor_id,
                'record_type' => 'start',
                'recorded_at' => now(),
                'recorded_mileage' => $data['mileage'],
                'notes' => $data['notes'] ?? null,
                'device_id' => null,
                'sync_status' => 'completed'
            ]);

            $travel->update([
                'estado' => 3,
                'km_inicio' => $data['mileage']
            ]);

            $travel->load(['recordsDriver', 'items']);

            DB::commit();

            return [
                'message' => 'Ruta iniciada correctamente',
                'data' => new TravelControlResource($travel)
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error al iniciar la ruta: ' . $e->getMessage());
        }
    }

    public function endRoute($data)
    {
        try {
            DB::beginTransaction();

            $travel = TravelControl::activos()->find($data['id']);
            
            if (!$travel) {
                throw new Exception('Viaje no encontrado');
            }

            if (!in_array($travel->estado, [3, 4, 5, 6, 7])) {
                throw new Exception('El viaje no se puede finalizar en su estado actual');
            }

            if ($data['mileage'] <= $travel->km_inicio) {
                throw new Exception("El kilometraje final debe ser mayor al inicial ({$travel->km_inicio} km)");
            }

            DriverTravelRecord::create([
                'dispatch_id' => $travel->id,
                'driver_id' => $travel->conductor_id,
                'record_type' => 'end',
                'recorded_at' => now(),
                'recorded_mileage' => $data['mileage'],
                'notes' => $data['notes'] ?? null,
                'sync_status' => 'completed'
            ]);

            $travel->update([
                'estado' => 8,
                'km_fin' => $data['mileage']
            ]);

            if (isset($data['tonnage'])) {
                $travel->items()->update(['cantidad' => $data['tonnage']]);
            }

            $travel->tract()->update(['kilometraje' => $data['mileage']]);

            $travel->load(['recordsDriver', 'items']);

            DB::commit();

            return [
                'message' => 'Ruta finalizada correctamente',
                'data' => new TravelControlResource($travel)
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error al finalizar la ruta: ' . $e->getMessage());
        }
    }

    public function fuelRecord($data)
    {
        try {
            DB::beginTransaction();

            $travel = TravelControl::activos()->find($data['id']);
            
            if (!$travel) {
                throw new Exception('Viaje no encontrado');
            }

            if (!in_array($travel->estado, [8, 3, 4, 5, 6, 7])) {
                throw new Exception('El viaje no está en un estado válido para registro de combustible');
            }

            $cliente = DB::table('rrhh_persona')
                ->where('id', $travel->idcliente)
                ->select('vat', 'nombre_completo')
                ->first();

            $totalKm = $travel->km_fin - $travel->km_inicio;
            $monto = $data['kmFactor'] * $totalKm;

            $gastoData = [
                'viaje_id' => $travel->id,
                'concepto_id' => 25,
                'monto' => $monto,
                'km_tanqueo' =>  null,
                'numero_doc' => $data['documentNumber'] ?? 'SIN-' . time(),
                'fecha_emision' => now()->format('Y-m-d'),
                'ruc' => $cliente->vat ?? ($data['vatNumber'] ?? '00000000000'),
                'status_aprobacion' => 'PENDING',
                'status_observacion' => sprintf(
                    'Factor: %s soles/km | Galones: %s | Km recorridos: %s | Observaciones: %s',
                    $data['kmFactor'],
                     0,
                    $totalKm,
                    $data['notes'] ?? ''
                ),
                'status_deleted' => 1,
                'aprobado' => 0,
            ];

            $travelExpense = TravelExpense::create($gastoData);

            if ($travel->estado == 8) {
                $travel->update(['estado' => 9]);
            }

            $travel->load(['recordsDriver', 'items', 'expenses']);
            DB::commit();

            return [
                'message' => 'Combustible registrado correctamente',
                'data' => [
                    'travel' => new TravelControlResource($travel),
                    'fuel' => [
                        'id' => $travelExpense->id,
                        'kmFactor' => $data['kmFactor'],
                        'fuelGallons' =>  null,
                        'fuelAmount' => $monto,
                        'totalKm' => $totalKm,
                        'numero_doc' => $travelExpense->numero_doc,
                        'fecha_emision' => $travelExpense->fecha_emision
                    ]
                ]
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error al registrar combustible: ' . $e->getMessage());
        }
    }

    public function changeState($data)
    {
        $travel = TravelControl::activos()->find($data['id']);
        
        if (!$travel) {
            throw new Exception('Viaje no encontrado');
        }

        $mapStates = [
            'pending' => 1,
            'in_progress' => 3,
            'fuel_pending' => 8,
            'completed' => 9
        ];

        $travel->update(['estado' => $mapStates[$data['state']]]);

        $travel->load(['driver_records', 'items', 'photos']);

        return [
            'message' => 'Estado actualizado correctamente',
            'data' => new TravelControlResource($travel)
        ];
    }

    public function driverRecords($id)
    {
        $viaje = TravelControl::activos()->find($id);
        
        if (!$viaje) {
            throw new Exception('Viaje no encontrado');
        }

        $registros = DriverTravelRecord::where('dispatch_id', $id)
            ->orderBy('recorded_at')
            ->get();

       return DriverTravelRecordResource::collection($registros);
    }

    public function validateMileage($vehiculo_id)
    {
        $ultimoKm = $this->getLatestMileage($vehiculo_id);
        return [
            'ultimo_kilometraje' => $ultimoKm,
            'mensaje' => $ultimoKm > 0
                ? "Último kilometraje registrado: {$ultimoKm} km"
                : "No hay registros previos para este vehículo"
        ];
    }

    public function availableStates()
    {
        return DB::table('op_despacho_estados')
            ->whereNotIn('id', [9, 10])
            ->select('id', 'descripcion', 'color2', 'porcentaje')
            ->get();
    }

    private function getLatestMileage($vehicleId)
    {
        //ultimo registro del kilometraje del conductor/vehiculo
        $latestMileage = DB::table('driver_travel_record as rvc')
                        ->join('op_despacho as d', 'd.id', '=', 'rvc.dispatch_id')
                        ->where('d.tracto_id', $vehicleId)
                        ->where('rvc.record_type', 'end')
                        ->whereNotNull('rvc.recorded_mileage')
                        ->orderBy('rvc.recorded_at', 'desc')
                        ->value('rvc.recorded_mileage');

        if(!$latestMileage){
            $latestMileage = DB::table('op_vehiculo')
                                ->where('id', $vehicleId)
                                ->value('kilometraje') ?? 0;
        }

        return $latestMileage;
    }

    private function getViajeQuery()
    {
        return "
            op_despacho.id,
            CONCAT('TPV', LPAD(op_despacho.id, 8, '0')) as codigo,
            op_despacho.estado as idestado,
            op_despacho.km_inicio,
            op_despacho.km_fin,
            op_despacho.produccion,
            op_despacho.condiciones,
            
            op_despacho.nliquidacion,
            op_despacho.conductor_id,
            op_despacho.tracto_id,
            op_despacho.carreta_id,
            op_despacho.idcliente,
            op_despacho.ubicacion as ubic_cabecera,
            op_despacho.observacion_comercial,
            op_despacho.obs_cistas,
            op_despacho.obs_combustible,
            op_despacho.obs_adic_1,
            op_despacho.obs_adic_2,
            op_despacho.obs_adic_3,
            op_despacho.obs_adic_4,
            op_despacho.obs_adic_5,
            op_despacho.obs_adic_6,
            op_despacho.obs_adic_7,
            op_despacho.obs_adic_8,
            op_despacho.proxima_prog,
            
            -- Cálculos adicionales
            (op_despacho.km_fin - op_despacho.km_inicio) as totalKm,
            
            -- Horas totales calculadas
            (
                SELECT TIMESTAMPDIFF(MINUTE, 
                    MIN(CASE WHEN record_type = 'start' THEN recorded_at END),
                    MAX(CASE WHEN record_type = 'end' THEN recorded_at END)
                ) / 60.0
                FROM driver_travel_record 
                WHERE dispatch_id = op_despacho.id
                AND record_type IN ('start', 'end')
            ) as totalHours,
            
            -- Toneladas
            (
                SELECT SUM(cantidad) 
                FROM op_despacho_item 
                WHERE despacho_id = op_despacho.id
            ) as tonnage,
            
            -- Combustible
            (
                SELECT monto 
                FROM op_gastos_viaje 
                WHERE viaje_id = op_despacho.id 
                AND concepto_id = 25 
                LIMIT 1
            ) as fuelAmount,
            
            (
                SELECT km_tanqueo 
                FROM op_gastos_viaje 
                WHERE viaje_id = op_despacho.id 
                AND concepto_id = 25 
                LIMIT 1
            ) as fuelGallons,
            
            -- Ruta y producto (importantes para tu frontend)
            COALESCE((
                SELECT CONCAT(
                    COALESCE(rco.descripcion, 'Sin origen'), 
                    ' - ', 
                    COALESCE(rci.descripcion, 'Sin destino')
                )
                FROM op_despacho_item odt
                LEFT JOIN fac_ciudades_sales rci ON rci.id = odt.iddestino
                LEFT JOIN fac_ciudades_sales rco ON rco.id = odt.idorigen
                WHERE odt.despacho_id = op_despacho.id 
                LIMIT 1
            ), 'Sin ruta') as ruta,
            
            COALESCE((
                SELECT pp.descripcion
                FROM op_despacho_item odi
                LEFT JOIN fac_producto_sales pp ON pp.id = odi.idproducto
                WHERE odi.despacho_id = op_despacho.id 
                LIMIT 1
            ), 'Sin producto') as producto,
            
            -- Próximo viaje
            COALESCE((
                SELECT CONCAT('TPV', LPAD(next_d.id, 8, '0'))
                FROM op_despacho next_d
                WHERE next_d.conductor_id = op_despacho.conductor_id
                AND next_d.id > op_despacho.id
                AND next_d.estado NOT IN (9,10)
                ORDER BY next_d.id ASC
                LIMIT 1
            ), '-') as proximocod,
            
            -- Próxima ruta
            COALESCE((
                SELECT CONCAT(
                    COALESCE(rco_next.descripcion, ''), 
                    '-', 
                    COALESCE(rci_next.descripcion, '')
                )
                FROM op_despacho next_d2
                LEFT JOIN op_despacho_item odi_next ON odi_next.despacho_id = next_d2.id
                LEFT JOIN fac_ciudades_sales rci_next ON rci_next.id = odi_next.iddestino
                LEFT JOIN fac_ciudades_sales rco_next ON rco_next.id = odi_next.idorigen
                WHERE next_d2.id = (
                    SELECT next_d3.id
                    FROM op_despacho next_d3
                    WHERE next_d3.conductor_id = op_despacho.conductor_id
                    AND next_d3.id > op_despacho.id
                    AND next_d3.estado NOT IN (9,10)
                    ORDER BY next_d3.id ASC
                    LIMIT 1
                )
                LIMIT 1
            ), '-') as proximoruta,
            
            -- Pendientes
            COALESCE((
                SELECT COUNT(p1.id)
                FROM pendientes p1
                WHERE p1.conductor_id = op_despacho.conductor_id
                AND p1.status_deleted = 1
                AND p1.status_id = 4
            ), 0) as pendientecond,
            
            COALESCE((
                SELECT COUNT(p2.id)
                FROM pendientes p2
                WHERE p2.tracto_id = op_despacho.tracto_id
                AND p2.status_deleted = 1
                AND p2.status_id = 4
            ), 0) as pendientetracto,
            
            -- Fecha del último estado
            COALESCE(
                (SELECT ole2.fecha
                FROM op_despacho_log_estados ole2
                WHERE ole2.despacho_id = op_despacho.id
                ORDER BY ole2.id DESC
                LIMIT 1),
                op_despacho.fecha_viaje
            ) as fecha_estado
        ";
    }
}