<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use App\Models\ap\comercial\ApVehicleDelivery;
use Carbon\Carbon;

class StoreApVehicleDeliveryRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'sede_id' => [
        'required',
        'integer',
        'exists:config_sede,id'
      ],
      'vehicle_id' => [
        'required',
        'integer',
        'exists:ap_vehicles,id'
      ],
      'scheduled_delivery_date' => [
        'required',
        'date',
        'after_or_equal:' . now()->addDay()->format('Y-m-d'), // Debe ser al menos 24 horas después
        function ($attribute, $value, $fail) {
          $deliveryDate = Carbon::parse($value);
          $isSaturday = $deliveryDate->isSaturday();
          $maxDeliveriesPerDay = $isSaturday ? 3 : 6;

          // Contar entregas ya programadas para esa fecha
          $deliveriesCount = ApVehicleDelivery::where('scheduled_delivery_date', $deliveryDate->format('Y-m-d'))
            ->whereNull('deleted_at')
            ->count();

          if ($deliveriesCount >= $maxDeliveriesPerDay) {
            $dayType = $isSaturday ? 'sábado' : 'día';
            $fail("Ya se alcanzó el máximo de $maxDeliveriesPerDay entregas permitidas para este $dayType.");
          }
        },
      ],
      'wash_date' => [
        'nullable',
        'date',
      ],
      'ap_class_article_id' => [
        'required',
        'integer',
        'exists:ap_class_article,id'
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
      'sede_id.required' => 'La sede es obligatoria.',
      'sede_id.integer' => 'La sede debe ser un número entero.',
      'sede_id.exists' => 'La sede no existe.',
      'vehicle_id.required' => 'El vehículo es obligatorio.',
      'vehicle_id.integer' => 'El vehículo debe ser un número entero.',
      'vehicle_id.exists' => 'El vehículo no existe.',
      'scheduled_delivery_date.required' => 'La fecha de entrega programada es obligatoria.',
      'scheduled_delivery_date.date' => 'La fecha de entrega programada no es una fecha válida.',
      'scheduled_delivery_date.after_or_equal' => 'La entrega debe programarse con al menos 24 horas de anticipación (un día antes).',
      'wash_date.date' => 'La fecha de lavado no es una fecha válida.',
      'actual_delivery_date.date' => 'La fecha de entrega real no es una fecha válida.',
      'ap_class_article_id.required' => 'La clase de artículo es obligatoria.',
      'ap_class_article_id.integer' => 'La clase de artículo debe ser un número entero.',
      'ap_class_article_id.exists' => 'La clase de artículo no existe.',
      'observations.string' => 'Las observaciones deben ser una cadena de texto.',
      'observations.max' => 'Las observaciones no deben exceder los 500 caracteres.',
    ];
  }
}
