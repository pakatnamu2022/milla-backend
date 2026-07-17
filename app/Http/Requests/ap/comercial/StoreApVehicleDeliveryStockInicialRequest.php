<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use App\Models\ap\comercial\ApVehicleDelivery;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\gp\maestroGeneral\Sede;
use Carbon\Carbon;

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
        function ($attribute, $value, $fail) {
          $deliveryDate = Carbon::parse($value);
          $dayOfWeek = $deliveryDate->dayOfWeek;

          if ($dayOfWeek === Carbon::SUNDAY) {
            $fail('No se programan entregas los domingos.');
            return;
          }

          $allowedSlots = $dayOfWeek === Carbon::SATURDAY
            ? ApVehicleDelivery::SATURDAY_SLOTS
            : ApVehicleDelivery::WEEKDAY_SLOTS;

          $requestedTime = $deliveryDate->format('H:i');

          if (!in_array($requestedTime, $allowedSlots, true)) {
            $slotsLabel = implode(', ', $allowedSlots);
            $dayLabel = $dayOfWeek === Carbon::SATURDAY ? 'sábado' : 'día de semana';
            $fail("El horario '$requestedTime' no está disponible para un $dayLabel. Horarios permitidos: $slotsLabel.");
            return;
          }

          $requestedSedeId = $this->input('sede_id');
          $sede = $requestedSedeId ? Sede::find($requestedSedeId) : null;
          $sedeIdsDelShop = $sede && $sede->shop_id
            ? Sede::where('shop_id', $sede->shop_id)->pluck('id')
            : collect(array_filter([$requestedSedeId]));

          $slotTaken = ApVehicleDelivery::where('scheduled_delivery_date', $deliveryDate->format('Y-m-d H:i:s'))
            ->whereIn('sede_id', $sedeIdsDelShop)
            ->whereNull('deleted_at')
            ->exists();

          if ($slotTaken) {
            $fail("El horario $requestedTime del " . $deliveryDate->format('d/m/Y') . " ya está ocupado en este shop. Elija otro horario.");
          }
        },
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
      'client_id' => [
        'required',
        'integer',
        'exists:business_partners,id',
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
      'client_id.required'           => 'El cliente es obligatorio.',
      'client_id.integer'            => 'El cliente debe ser un número entero.',
      'client_id.exists'             => 'El cliente no existe.',
      'observations.string'          => 'Las observaciones deben ser texto.',
      'observations.max'             => 'Las observaciones no deben exceder los 500 caracteres.',
    ];
  }
}