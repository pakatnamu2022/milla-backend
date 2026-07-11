<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use App\Models\ap\comercial\ApVehicleDelivery;
use Carbon\Carbon;

class RescheduleApVehicleDeliveryRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'scheduled_delivery_date' => [
        'required',
        'date',
        'after_or_equal:' . now()->addDay()->format('Y-m-d'),
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
          }
        },
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
      'scheduled_delivery_date.required'        => 'La nueva fecha y hora de entrega es obligatoria.',
      'scheduled_delivery_date.date'            => 'La fecha de entrega no es válida.',
      'scheduled_delivery_date.after_or_equal'  => 'La entrega debe reprogramarse con al menos 24 horas de anticipación.',
    ];
  }
}
