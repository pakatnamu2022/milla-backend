<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreWorkOrderRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'appointment_planning_id' => [
        'nullable',
        'integer',
        'exists:appointment_planning,id',
      ],
      'vehicle_id' => [
        'required',
        'integer',
        'exists:ap_vehicles,id',
      ],
      'vehicle_plate' => [
        'nullable',
        'string',
        'max:20',
      ],
      'vehicle_vin' => [
        'nullable',
        'string',
        'max:50',
      ],
      'sede_id' => [
        'required',
        'integer',
        'exists:config_sede,id',
      ],
      'opening_date' => [
        'required',
        'date',
      ],
      'estimated_delivery_date' => [
        'required',
        'date',
      ],
      'diagnosis_date' => [
        'required',
        'date',
      ],
      'observations' => [
        'nullable',
        'string',
      ],
      'total_labor_cost' => [
        'nullable',
        'numeric',
        'min:0',
      ],
      'total_parts_cost' => [
        'nullable',
        'numeric',
        'min:0',
      ],
      'subtotal' => [
        'nullable',
        'numeric',
        'min:0',
      ],
      'discount_percentage' => [
        'nullable',
        'numeric',
        'min:0',
        'max:100',
      ],
      'discount_amount' => [
        'nullable',
        'numeric',
        'min:0',
      ],
      'tax_amount' => [
        'nullable',
        'numeric',
        'min:0',
      ],
      'final_amount' => [
        'nullable',
        'numeric',
        'min:0',
      ],
      'is_invoiced' => [
        'nullable',
        'boolean',
      ],
      // Items
      'items' => [
        'nullable',
        'array',
      ],
      'items.*.group_number' => [
        'required_with:items',
        'integer',
        'min:1',
      ],
      'items.*.type_planning_id' => [
        'required_with:items',
        'integer',
        Rule::exists('ap_post_venta_masters', 'id')
          ->where('type', 'TIPO_PLANIFICACION'),
      ],
      'items.*.description' => [
        'required_with:items',
        'string',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'appointment_planning_id.integer' => 'La cita debe ser un entero.',
      'appointment_planning_id.exists' => 'La cita seleccionada no es válida.',

      'vehicle_id.required' => 'El vehículo es obligatorio.',
      'vehicle_id.integer' => 'El vehículo debe ser un entero.',
      'vehicle_id.exists' => 'El vehículo seleccionado no es válido.',

      'vehicle_plate.string' => 'La placa debe ser una cadena de texto.',
      'vehicle_plate.max' => 'La placa no debe exceder los 20 caracteres.',

      'vehicle_vin.string' => 'El VIN debe ser una cadena de texto.',
      'vehicle_vin.max' => 'El VIN no debe exceder los 50 caracteres.',

      'sede_id.required' => 'La sede es obligatoria.',
      'sede_id.integer' => 'La sede debe ser un entero.',
      'sede_id.exists' => 'La sede seleccionada no es válida.',

      'opening_date.required' => 'La fecha de apertura es obligatoria.',
      'opening_date.date' => 'La fecha de apertura debe ser una fecha válida.',

      'estimated_delivery_date.required' => 'La fecha estimada de entrega es obligatoria.',
      'estimated_delivery_date.date' => 'La fecha estimada de entrega debe ser una fecha válida.',

      'diagnosis_date.required' => 'La fecha de diagnóstico es obligatoria.',
      'diagnosis_date.date' => 'La fecha de diagnóstico debe ser una fecha válida.',

      'observations.string' => 'Las observaciones deben ser una cadena de texto.',

      'total_labor_cost.numeric' => 'El total de mano de obra debe ser un número.',
      'total_labor_cost.min' => 'El total de mano de obra no puede ser negativo.',

      'total_parts_cost.numeric' => 'El total de repuestos debe ser un número.',
      'total_parts_cost.min' => 'El total de repuestos no puede ser negativo.',

      'subtotal.numeric' => 'El subtotal debe ser un número.',
      'subtotal.min' => 'El subtotal no puede ser negativo.',

      'discount_percentage.numeric' => 'El porcentaje de descuento debe ser un número.',
      'discount_percentage.min' => 'El porcentaje de descuento no puede ser negativo.',
      'discount_percentage.max' => 'El porcentaje de descuento no puede ser mayor a 100.',

      'discount_amount.numeric' => 'El monto de descuento debe ser un número.',
      'discount_amount.min' => 'El monto de descuento no puede ser negativo.',

      'tax_amount.numeric' => 'El monto de impuestos debe ser un número.',
      'tax_amount.min' => 'El monto de impuestos no puede ser negativo.',

      'final_amount.numeric' => 'El monto final debe ser un número.',
      'final_amount.min' => 'El monto final no puede ser negativo.',

      'is_invoiced.boolean' => 'El campo facturado debe ser verdadero o falso.',

      // Items
      'items.array' => 'Los ítems deben ser un arreglo.',

      'items.*.group_number.required_with' => 'El número de grupo es obligatorio.',
      'items.*.group_number.integer' => 'El número de grupo debe ser un entero.',
      'items.*.group_number.min' => 'El número de grupo debe ser al menos 1.',

      'items.*.type_planning_id.required_with' => 'El tipo de planificación es obligatorio.',
      'items.*.type_planning_id.integer' => 'El tipo de planificación debe ser un entero.',
      'items.*.type_planning_id.exists' => 'El tipo de planificación seleccionado no es válido.',

      'items.*.description.required_with' => 'La descripción del ítem es obligatoria.',
      'items.*.description.string' => 'La descripción del ítem debe ser una cadena de texto.',
    ];
  }
}
