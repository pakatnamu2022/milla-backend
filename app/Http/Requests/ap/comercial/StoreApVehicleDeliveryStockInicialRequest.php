<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use App\Models\ap\comercial\ApVehicleDelivery;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;

class StoreApVehicleDeliveryStockInicialRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'advisor_id' => [
        'required',
        'integer',
        'exists:rrhh_persona,id',
      ],
      'vehicle_id' => [
        'required',
        'integer',
        'exists:ap_vehicles,id',
        function ($attribute, $value, $fail) {
          $vehicle = Vehicles::find($value);

          if (!$vehicle || $vehicle->ap_vehicle_status_id !== ApVehicleStatus::VENDIDO_NO_ENTREGADO) {
            $fail('El vehículo no está en estado VENDIDO NO ENTREGADO.');
            return;
          }

          $isInitialStock = PurchaseOrder::whereHas('vehicleMovement', function ($q) use ($value) {
            $q->where('ap_vehicle_id', $value);
          })->whereNull('deleted_at')->where('number', 'like', '%SI-%')->exists();

          if (!$isInitialStock) {
            $fail('El vehículo no corresponde a un stock inicial (SI).');
            return;
          }

          $existingDelivery = ApVehicleDelivery::where('vehicle_id', $value)
            ->whereNull('deleted_at')
            ->exists();

          if ($existingDelivery) {
            $fail('Ya existe una entrega registrada para este vehículo.');
          }
        },
      ],
      'scheduled_delivery_date' => [
        'required',
        'date',
      ],
      'sede_id' => [
        'required',
        'integer',
        'exists:config_sede,id',
      ],
      'ap_class_article_id' => [
        'required',
        'integer',
        'exists:ap_class_article,id',
      ],
      'observations' => [
        'nullable',
        'string',
        'max:500',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'advisor_id.required'          => 'El asesor es obligatorio.',
      'advisor_id.integer'           => 'El asesor debe ser un número entero.',
      'advisor_id.exists'            => 'El asesor no existe.',
      'vehicle_id.required'          => 'El vehículo es obligatorio.',
      'vehicle_id.integer'           => 'El vehículo debe ser un número entero.',
      'vehicle_id.exists'            => 'El vehículo no existe.',
      'scheduled_delivery_date.required' => 'La fecha de entrega es obligatoria.',
      'scheduled_delivery_date.date'     => 'La fecha de entrega no es una fecha válida.',
      'sede_id.required'             => 'La sede es obligatoria.',
      'sede_id.integer'              => 'La sede debe ser un número entero.',
      'sede_id.exists'               => 'La sede no existe.',
      'ap_class_article_id.required' => 'La clase de artículo es obligatoria.',
      'ap_class_article_id.integer'  => 'La clase de artículo debe ser un número entero.',
      'ap_class_article_id.exists'   => 'La clase de artículo no existe.',
      'observations.string'          => 'Las observaciones deben ser texto.',
      'observations.max'             => 'Las observaciones no deben exceder los 500 caracteres.',
    ];
  }
}