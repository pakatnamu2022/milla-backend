<?php

namespace App\Http\Resources\tp\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Traits\HandlesMissingValue;
use App\Models\tp\comercial\DispatchStatus;

class TravelControlResource extends JsonResource
{

  use HandlesMissingValue;

  public function toArray(Request $request): array
  {

    if ($this->resource instanceof \Illuminate\Http\Resources\MissingValue) {
      return ['error' => 'missing_value'];
    }

    if (!$this->resource) {
      return ['error' => 'null_resource'];
    }


    $tractLoaded = $this->relationLoaded('tract');
    $cartLoaded = $this->relationLoaded('cart');
    $customerLoaded = $this->relationLoaded('customer');
    $statusTripLoaded = $this->relationLoaded('statusTrip');
    $driverLoaded = $this->relationLoaded('driver');

    //inicializamos variables para el calculo de kmFactor
    $kmFactor = null;
    $fuelAmount = $this->convertToNumber($this->fuel_data['fuelAmount'] ?? null);
    $totalKm = $this->total_km;

    if ($fuelAmount !== null && $totalKm !== null && $totalKm > 0) {
      $kmFactor = $fuelAmount / $totalKm;
    }

    return [
      'id' => $this->id,
      'codigo' => $this->trip_number,
      'tripNumber' => $this->trip_number,
      'estado' => $this->estado,
      'idestado' => $this->estado,
      'status' => $this->mapped_status,
      'conductor_id' => $this->conductor_id,
      'tracto_id' => $this->tracto_id,
      'carreta_id' => $this->carreta_id,
      'cliente_id' => $this->idcliente,
      'plate' => $tractLoaded && $this->tract ? $this->tract->placa : null,
      'tracto_marca' => $tractLoaded && $this->tract ? $this->tract->marca : null,
      'tracto_modelo' => $tractLoaded && $this->tract ? $this->tract->modelo : null,
      'carreta_placa' => $cartLoaded && $this->cart ? $this->cart->placa : null,
      'cliente_nombre' => $customerLoaded && $this->customer ? $this->customer->nombre_completo : null,
      'cliente_ruc' => $customerLoaded && $this->customer ? $this->customer->vat : null,
      'client' => $customerLoaded && $this->customer ? $this->customer->nombre_completo : null,
      'estado_descripcion' => $statusTripLoaded && $this->statusTrip ? $this->statusTrip->descripcion : null,
      'estado_color' => $statusTripLoaded && $this->statusTrip ? $this->statusTrip->color2 : null,
      'estado_porcentaje' => $statusTripLoaded && $this->statusTrip ? $this->statusTrip->porcentaje : null,
      'estado_norden' => $statusTripLoaded && $this->statusTrip ? $this->statusTrip->norden : null,
      'ruta' => $this->getRouteFromItems(),
      'route' => $this->getRouteFromItems(),
      'producto' => $this->getProductFromItems(),
      'ubic_cabecera' => $this->ubicacion,
      'ubicacion' => $this->ubicacion,
      'km_inicio' => $this->km_inicio,
      'km_fin' => $this->km_fin,
      'initialKm' => $this->km_inicio,
      'finalKm' => $this->km_fin,
      'totalKm' => $totalKm,
      'totalHours' => $this->total_hours,
      'tonnage' => $this->tonnage,
      'fuelAmount' => $fuelAmount,
      'fuelGallons' => $this->convertToNumber($this->fuel_data['fuelGallons'] ?? null),
      'factorKm' => $kmFactor,
      'fecha_viaje' => $this->fecha_viaje?->format('Y-m-d H:i:s'),
      'fecha_estado' => $this->fecha_viaje?->format('Y-m-d H:i:s'),
      'startTime' => $this->fecha_viaje?->format('Y-m-d H:i:s'),
      'endTime' => $this->fecha_viaje?->format('Y-m-d H:i:s'),
      'observacion_comercial' => $this->observacion_comercial,
      'proxima_prog' => $this->proxima_prog,
      'produccion' => $this->produccion,
      'condiciones' => $this->condiciones,
      'nliquidacion' => $this->nliquidacion,
      'cantidad' => $this->tonnage,

      // Relaciones
      'items' => $this->whenLoaded('items', function () {
        return DispatchItemResource::collection($this->items);
      }, []),

      'driver_records' => $this->whenLoaded('recordsDriver', function () {
        return DriverTravelRecordResource::collection($this->recordsDriver);
      }, []),
      'gastos' => $this->whenLoaded('expenses', function () {
        return TravelExpenseResource::collection($this->expenses);
      }, []),

      'proximocod' => '-',
      'proximoruta' => '-',
      'pendientecond' => 0,
      'pendientetracto' => 0,
      'pendientecarreta' => 0,

      // Observaciones
      'obs_cistas' => $this->obs_cistas,
      'obs_combustible' => $this->obs_combustible,
      'obs_adic_1' => $this->obs_adic_1,
      'obs_adic_2' => $this->obs_adic_2,
      'obs_adic_3' => $this->obs_adic_3,
      'obs_adic_4' => $this->obs_adic_4,
      'obs_adic_5' => $this->obs_adic_5,
      'obs_adic_6' => $this->obs_adic_6,
      'obs_adic_7' => $this->obs_adic_7,
      'obs_adic_8' => $this->obs_adic_8,

      // Timestamps
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,

      'driver' => [
        'id' => $this->conductor_id ? (string)$this->conductor_id : null,
        'name' => $driverLoaded && $this->driver ? $this->driver->nombre_completo : null,
        'phone' => $driverLoaded && $this->driver ? $this->driver->telefono : null,
        'email' => $driverLoaded && $this->driver ? $this->driver->email : null
      ],

      'vehicle' => [
        'id' => $this->tracto_id ? (string)$this->tracto_id : null,
        'placa' => $tractLoaded && $this->tract ? $this->tract->placa : null,
        'marca' => $tractLoaded && $this->tract ? $this->tract->marca : null,
        'modelo' => $tractLoaded && $this->tract ? $this->tract->modelo : null
      ],

      'estado_info' => [
        'descripcion' => $statusTripLoaded && $this->statusTrip ? $this->statusTrip->descripcion : null,
        'color' => $statusTripLoaded && $this->statusTrip ? $this->statusTrip->color2 : null,
        'porcentaje' => $statusTripLoaded && $this->statusTrip ? $this->statusTrip->porcentaje : null,
        'norden' => $statusTripLoaded && $this->statusTrip ? $this->statusTrip->norden : null
      ],
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
      'user_id' => $this->conductor_id ? (string)$this->conductor_id : null,

      // Metadata
      'metadata' => [
        'is_active' => DispatchStatus::isActiveStatus($this->estado),
        'can_start' => $this->estado == DispatchStatus::STATUS_PENDING,
        'can_end' => in_array($this->estado, DispatchStatus::getInProgressStatuses()),
        'can_add_fuel' => in_array($this->estado, [
          DispatchStatus::STATUS_FUEL_PENDING,
          DispatchStatus::STATUS_EN_ROUTE,
          DispatchStatus::STATUS_AT_ORIGIN,
          DispatchStatus::STATUS_LOADING,
          DispatchStatus::STATUS_IN_TRANSIT,
          DispatchStatus::STATUS_UNLOADING
        ]),
        'can_upload_photos' => !in_array($this->estado, [
          DispatchStatus::STATUS_COMPLETED,
          DispatchStatus::STATUS_CANCELLED
        ])
      ]
    ];
  }

  // Agrega estos métodos al Resource
  private function getRouteFromItems(): string
  {
    if (!$this->relationLoaded('items') || $this->items->isEmpty()) {
      return 'Sin ruta';
    }

    $item = $this->items->first();
    $origin = $item->origin->descripcion ?? 'Sin origen';
    $destination = $item->destination->descripcion ?? 'Sin destino';

    return $origin . ' - ' . $destination;
  }

  private function getProductFromItems(): string
  {
    if (!$this->relationLoaded('items') || $this->items->isEmpty()) {
      return 'Sin producto';
    }

    $item = $this->items->first();
    return $item->product->descripcion ?? 'Sin producto';
  }

  private function convertToNumber($value): ?float
  {
    if ($value === null || $value === '' || $value === false) {
      return null;
    }

    if (is_numeric($value)) {
      return (float)$value;
    }

    $num = floatval($value);
    return is_nan($num) ? null : $num;
  }

}
