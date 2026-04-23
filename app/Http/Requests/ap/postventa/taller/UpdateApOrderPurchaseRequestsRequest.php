<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use App\Models\ap\ApMasters;
use Illuminate\Validation\Rule;

class UpdateApOrderPurchaseRequestsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'ap_order_quotation_id' => [
        'sometimes',
        'nullable',
        'integer',
        'exists:ap_order_quotations,id',
      ],
      'purchase_order_id' => [
        'sometimes',
        'nullable',
        'integer',
        'exists:ap_purchase_order,id',
      ],
      'warehouse_id' => [
        'sometimes',
        'required',
        'integer',
        'exists:warehouse,id',
      ],
      'currency_id' => [
        'sometimes',
        'required',
        'integer',
        'exists:type_currency,id',
      ],
      'requested_date' => [
        'sometimes',
        'required',
        'date',
      ],
      'ordered_date' => [
        'sometimes',
        'nullable',
        'date',
      ],
      'received_date' => [
        'sometimes',
        'nullable',
        'date',
      ],
      'advisor_notified' => [
        'sometimes',
        'boolean',
      ],
      'notified_at' => [
        'sometimes',
        'nullable',
        'date',
      ],
      'observations' => [
        'sometimes',
        'nullable',
        'string',
      ],
      'status' => [
        'sometimes',
        'required',
        'in:pending,approved,rejected',
      ],

      // Details validation (optional on update)
      'details' => [
        'sometimes',
        'array',
        'min:1',
      ],
      'details.*.product_id' => [
        'required',
        'integer',
        'exists:products,id',
      ],
      'details.*.quantity' => [
        'required',
        'numeric',
        'min:0.01',
      ],
      'details.*.unit_price' => [
        'required',
        'numeric',
        'min:0.01',
      ],
      'details.*.discount_percentage' => [
        'required',
        'numeric',
      ],
      'details.*.total_amount' => [
        'required',
        'numeric',
        'min:0.01',
      ],
      'details.*.notes' => [
        'nullable',
        'string',
      ],
      'details.*.requested_delivery_date' => [
        'nullable',
        'date',
      ],
      'details.*.supply_type' => [
        'required',
        'string',
        'in:LOCAL,CENTRAL,IMPORTACION', //no se toma en cuenta stock porque se considera que si una solicitud de compra no tiene cotizacion es por stock
      ]
    ];
  }

  public function messages(): array
  {
    return [
      'ap_order_quotation_id.integer' => 'La cotización debe ser un entero.',
      'ap_order_quotation_id.exists' => 'La cotización seleccionada no es válida.',

      'purchase_order_id.integer' => 'La orden de compra debe ser un entero.',
      'purchase_order_id.exists' => 'La orden de compra seleccionada no es válida.',

      'warehouse_id.required' => 'El almacén es obligatorio.',
      'warehouse_id.integer' => 'El almacén debe ser un entero.',
      'warehouse_id.exists' => 'El almacén seleccionado no es válido.',

      'currency_id.required' => 'La moneda es obligatoria.',
      'currency_id.integer' => 'La moneda debe ser un entero.',
      'currency_id.exists' => 'La moneda seleccionada no es válida.',

      'requested_date.required' => 'La fecha de solicitud es obligatoria.',
      'requested_date.date' => 'La fecha de solicitud debe ser una fecha válida.',

      'ordered_date.date' => 'La fecha de orden debe ser una fecha válida.',

      'received_date.date' => 'La fecha de recepción debe ser una fecha válida.',

      'advisor_notified.boolean' => 'El campo notificado debe ser verdadero o falso.',

      'notified_at.date' => 'La fecha de notificación debe ser una fecha válida.',

      'observations.string' => 'Las observaciones deben ser una cadena de texto.',

      'status.required' => 'El estado es obligatorio.',
      'status.in' => 'El estado debe ser: pending, approved o rejected.',
      'supply_type.required' => 'El tipo de suministro es obligatorio.',
      'supply_type.in' => 'El tipo de suministro debe ser: STOCK, LOCAL, CENTRAL o IMPORTACION.',

      // Details messages
      'details.array' => 'Los detalles deben ser un arreglo.',
      'details.min' => 'Debe incluir al menos un detalle en la solicitud.',

      'details.*.product_id.required' => 'El producto es obligatorio en cada detalle.',
      'details.*.product_id.integer' => 'El producto debe ser un entero.',
      'details.*.product_id.exists' => 'El producto seleccionado no es válido.',

      'details.*.quantity.required' => 'La cantidad es obligatoria en cada detalle.',
      'details.*.quantity.numeric' => 'La cantidad debe ser un número.',
      'details.*.quantity.min' => 'La cantidad debe ser mayor a 0.',

      'details.*.unit_price.required' => 'El precio unitario es obligatorio en cada detalle.',
      'details.*.unit_price.numeric' => 'El precio unitario debe ser un número.',
      'details.*.unit_price.min' => 'El precio unitario debe ser mayor a 0.',

      'details.*.discount_percentage.required' => 'El porcentaje de descuento es obligatorio en cada detalle.',
      'details.*.discount_percentage.numeric' => 'El porcentaje de descuento debe ser un número.',

      'details.*.total_amount.required' => 'El monto total es obligatorio en cada detalle.',
      'details.*.total_amount.numeric' => 'El monto total debe ser un número.',
      'details.*.total_amount.min' => 'El monto total debe ser mayor a 0.',

      'details.*.notes.string' => 'Las notas deben ser una cadena de texto.',

      'details.*.requested_delivery_date.date' => 'La fecha de entrega solicitada debe ser una fecha válida.',
    ];
  }
}
