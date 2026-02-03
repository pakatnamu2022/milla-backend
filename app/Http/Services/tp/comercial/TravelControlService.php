<?php

namespace App\Http\Services\tp\comercial;

use App\Http\Resources\tp\comercial\DriverTravelRecordResource;
use App\Http\Resources\tp\comercial\TravelControlResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\tp\Customer;
use App\Models\tp\comercial\DispatchStatus;
use App\Models\tp\comercial\TravelControl;
use App\Models\tp\comercial\DriverTravelRecord;
use App\Models\tp\comercial\TravelExpense;
use App\Models\tp\comercial\Vehicle;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TravelControlService extends BaseService
{
  public function list(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search', '');
        $status = $request->get('status', DispatchStatus::FILTER_ALL);

        $user = auth()->user();

        if($user){
            $workerId = $user->partner_id;
            $worker = Worker::find($workerId);
            if($worker){
                $positionWorkerName = $worker->position?->name;
                $positionWorkerId = $worker->position?->id;

                    $query = TravelControl::with([
                    'driver:id,nombre_completo,vat,email',
                    'tract:id,placa,marca,modelo',
                    'cart:id,placa',
                    'customer:id,nombre_completo,vat as ruc',
                    'statusTrip:id,descripcion,color2,porcentaje,norden',
                    'items' => function($q) {
                        $q->select('id', 'despacho_id', 'cantidad', 'idproducto', 'idorigen', 'iddestino')
                        ->with([
                            'product:id,descripcion',
                            'origin:id,descripcion',
                            'destination:id,descripcion'
                        ]);
                    },
                    'recordsDriver:id,dispatch_id,driver_id,record_type,recorded_at,recorded_mileage,notes,device_id',
                    'expenses' => function($q) {
                        $q->where('concepto_id', 25)
                        ->select('id', 'viaje_id', 'monto', 'km_tanqueo', 'created_at');
                    }
                ])
                ->select([
                    'id',
                    'conductor_id',
                    'tracto_id',
                    'carreta_id',
                    'idcliente',
                    'estado',
                    'km_inicio',
                    'km_fin',
                    'fecha_viaje',
                    'observacion_comercial',
                    'proxima_prog',
                    'ubicacion',
                    'produccion',
                    'condiciones',
                    'nliquidacion',
                    'created_at',
                    'updated_at'
                ])
                ->whereNotIn('estado', [10])
                ->orderBy('fecha_viaje', 'DESC');

                if($positionWorkerId == 11 || $positionWorkerName == 'CONDUCTOR DE TRACTO CAMION'){
                    $query->where('conductor_id', $workerId);
                }
                //filtro por estado

                if($status !== DispatchStatus::FILTER_ALL){
                    switch($status){
                        case DispatchStatus::FILTER_IN_PROGRESS:
                            $query->whereIn('estado', DispatchStatus::getInProgressStatuses())
                                    ->whereNotNull('km_inicio');
                            break;
                        case DispatchStatus::FILTER_COMPLETED:
                            $query->whereIn('estado', [DispatchStatus::STATUS_COMPLETED, 
                            DispatchStatus::STATUS_LIQUIDATED])
                            ->whereNotNull('km_inicio')
                            ->whereNotNull('km_fin');
                            break;
                        default:
                            $dbStatuses = DispatchStatus::fromTripStatus($status);
                                if (!empty($dbStatuses)) {
                                    $query->whereIn('estado', $dbStatuses);
                                }
                            break;
                    }
                }


                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('id', 'LIKE', "%{$search}%")
                            ->orWhereHas('driver', function ($q2) use ($search) {
                                $q2->where('nombre_completo', 'LIKE', "%{$search}%");
                            })
                            ->orWhereHas('tract', function ($q3) use ($search) {
                                $q3->where('placa', 'LIKE', "%{$search}%");
                            });
                    });
                }

                // Paginación
                $travels = $query->paginate($perPage);

                // Transformar datos
                $transformedData = TravelControlResource::collection(
                    $travels->getCollection()->filter()
                );

                return [
                    'data' => $transformedData,
                    'links' => $travels->linkCollection()->toArray(),
                    'meta' => [
                        'current_page' => $travels->currentPage(),
                        'from' => $travels->firstItem(),
                        'last_page' => $travels->lastPage(),
                        'per_page' => $travels->perPage(),
                        'to' => $travels->lastItem(),
                        'total' => $travels->total(),
                    ]
                ];
            }
        }

        
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
    } catch (Throwable $th) {
      DB::rollBack();
      Log::error("Error al crear el viaje" . $th->getMessage());
      throw new Exception('Error al crear el viaje: ' . $th->getMessage());
    }
  }


  public function show($id)
  {
    $travel = TravelControl::with([
      'driver:id,nombre_completo,vat,email',
      'tract:id,placa,marca,modelo',
      'cart:id,placa',
      'customer:id,nombre_completo,ruc',
      'statusTrip:id,descripcion,color2,porcentaje,norden',
      'items.product:id,descripcion',
      'recordsDriver',
      'expenses' => function ($q) {
        $q->where('concepto_id', 25);
      }
    ])->findOrFail($id);

    return new TravelControlResource($travel);
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

    // $travel->load(['items', 'recordsDriver']);

    $travel->refresh();

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

      // $travel->load(['recordsDriver', 'items', 'tract', 'cart', 'customer',
      //   'statusTrip', 'driver', 'expenses']);

      DB::commit();

      return [
        'message' => 'Ruta iniciada correctamente',
        'data' => new TravelControlResource($travel)
      ];
    } catch (Throwable $th) {
      DB::rollBack();
      Log::error("Error al iniciar la ruta" . $th->getMessage());
      throw new Exception('Error en el servicio de iniciar la ruta: ' . $th->getMessage());
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

      // Actualizar vehículo usando Eloquent
      $travel->tract()->update(['kilometraje' => $data['mileage']]);

      // $travel->load(['recordsDriver', 'items', 'tract', 'cart', 'customer',
      //   'statusTrip', 'driver', 'expenses']);

      DB::commit();

      return [
        'message' => 'Ruta finalizada correctamente',
        'data' => new TravelControlResource($travel)
      ];
    } catch (Throwable $th) {
      DB::rollBack();
      Log::error("Error al finalizar la ruta" . $th->getMessage());
      throw new Exception('Error en el servicio de finalizar ruta: ' . $th->getMessage());
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

      // Usando Eloquent para obtener cliente
      $cliente = Customer::find($travel->idcliente, ['vat', 'nombre_completo']);

      $totalKm = $travel->km_fin - $travel->km_inicio;
      $monto = $data['kmFactor'] * $totalKm;

      $gastoData = [
        'viaje_id' => $travel->id,
        'concepto_id' => 25,
        'monto' => $monto,
        'km_tanqueo' => null,
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

      // $travel->load(['recordsDriver', 'items', 'tract', 'cart', 'customer',
      //   'statusTrip', 'driver', 'expenses']);
      DB::commit();

      return [
        'message' => 'Combustible registrado correctamente',
        'data' => [
          'travel' => new TravelControlResource($travel),
          'fuel' => [
            'id' => $travelExpense->id,
            'kmFactor' => $data['kmFactor'],
            'fuelGallons' => null,
            'fuelAmount' => $monto,
            'totalKm' => $totalKm,
            'numero_doc' => $travelExpense->numero_doc,
            'fecha_emision' => $travelExpense->fecha_emision
          ]
        ]
      ];
    } catch (Throwable $th) {
      DB::rollBack();
      Log::error("Error al registrar combustible" . $th->getMessage());
      throw new Exception('Error al registrar combustible: ' . $th->getMessage());
    }
  }

  public function changeState($data)
  {
    $travel = TravelControl::activos()->find($data['id']);

    if (!$travel) {
      throw new Exception('Viaje no encontrado');
    }

    $mapStates = [
      'pending' => DispatchStatus::STATUS_PENDING,
      'in_progress' => DispatchStatus::STATUS_EN_ROUTE,
      'fuel_pending' => DispatchStatus::STATUS_FUEL_PENDING,
      'completed' => DispatchStatus::STATUS_COMPLETED
    ];

    if (!isset($mapStates[$data['state']])) {
      throw new Exception('Estado invalido');
    }

    $travel->update(['estado' => $mapStates[$data['state']]]);

    // $travel->load(['recordsDriver', 'items']);

    return [
      'message' => 'Estado actualizado correctamente',
      'data' => new TravelControlResource($travel)
    ];
  }

  public function driverRecords($id)
  {
    try {
      $viaje = TravelControl::find($id);

      if (!$viaje) {
        throw new Exception('Viaje no encontrado');
      }

      $registros = $viaje->recordsDriver()
        ->with([
          'driver:id,nombre_completo,vat',
          'dispatch:id,conductor_id,idcliente'
        ])
        ->orderBy('recorded_at')
        ->get();

      return DriverTravelRecordResource::collection($registros);

    } catch (Exception $e) {
      Log::error("Error en driverRecords - Viaje ID: {$id} - " . $e->getMessage());
      throw new Exception('Error al obtener registros del conductor: ' . $e->getMessage());
    }
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
    return DispatchStatus::whereNotIn('id', [9, 10])
      ->select('id', 'descripcion', 'color2', 'porcentaje')
      ->get();
  }

  private function getLatestMileage($vehicleId)
  {
    $latestRecord = DriverTravelRecord::whereHas('dispatch', function ($query) use ($vehicleId) {
      $query->where('tracto_id', $vehicleId);
    })
      ->where('record_type', 'end')
      ->whereNotNull('recorded_mileage')
      ->orderBy('recorded_at', 'desc')
      ->first();

    if ($latestRecord) {
      return $latestRecord->recorded_mileage;
    }

    // Si no hay registros, obtener del vehículo
    $vehicle = Vehicle::find($vehicleId);
    return $vehicle ? ($vehicle->kilometraje ?? 0) : 0;
  }


}
