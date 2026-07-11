<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use App\Models\ap\comercial\ApVehicleDelivery;
use App\Models\gp\maestroGeneral\Sede;
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
        'after_or_equal:' . now()->addDay()->format('Y-m-d'),
        function ($attribute, $value, $fail) {
          $deliveryDate = Carbon::parse($value);
          $dayOfWeek = $deliveryDate->dayOfWeek; // 0=domingo, 6=sábado

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

          // Obtener sedes del mismo shop para validar el slot agrupado
          $requestedSedeId = $this->input('sede_id');
          $sede = $requestedSedeId ? Sede::find($requestedSedeId) : null;
          $sedeIdsDelShop = $sede && $sede->shop_id
            ? Sede::where('shop_id', $sede->shop_id)->pluck('id')
            : collect(array_filter([$requestedSedeId]));

          if ($this->boolean('is_extraordinary')) {
            return;
          }

          $slotTaken = ApVehicleDelivery::where('scheduled_delivery_date', $deliveryDate->format('Y-m-d H:i:s'))
            ->whereIn('sede_id', $sedeIdsDelShop)
            ->whereNull('deleted_at')
            ->exists();

          if ($slotTaken) {
            $fail("El horario $requestedTime del " . $deliveryDate->format('d/m/Y') . " ya está ocupado en este shop. Elija otro horario.");
          }
        },
      ],
      'is_extraordinary' => [
        'sometimes',
        'boolean',
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
      'scheduled_delivery_date.required' => 'La fecha y hora de entrega programada es obligatoria.',
      'scheduled_delivery_date.date' => 'La fecha y hora de entrega programada no es válida.',
      'scheduled_delivery_date.after_or_equal' => 'La entrega debe programarse con al menos 24 horas de anticipación.',
      'ap_class_article_id.required' => 'La clase de artículo es obligatoria.',
      'ap_class_article_id.integer' => 'La clase de artículo debe ser un número entero.',
      'ap_class_article_id.exists' => 'La clase de artículo no existe.',
      'observations.string' => 'Las observaciones deben ser una cadena de texto.',
      'observations.max' => 'Las observaciones no deben exceder los 500 caracteres.',
    ];
  }
}