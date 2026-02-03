<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class UpdateApOrderQuotationDetailsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'order_quotation_id' => [
        'sometimes',
        'required',
        'integer',
        'exists:ap_order_quotations,id',
      ],
      'item_type' => [
        'sometimes',
        'required',
        'in:PRODUCT,LABOR',
      ],
      'product_id' => [
        'nullable',
        'integer',
        'exists:products,id',
      ],
      'description' => [
        'sometimes',
        'required',
        'string',
        'max:255',
      ],
      'purchase_price' => [
        'sometimes',
        'nullable',
        'numeric',
        'min:0',
      ],
      'quantity' => [
        'sometimes',
        'required',
        'numeric',
        'min:0.01',
      ],
      'unit_measure' => [
        'sometimes',
        'required',
        'string',
        'max:50',
      ],
      'unit_price' => [
        'sometimes',
        'required',
        'numeric',
        'min:0',
      ],
      'discount_percentage' => [
        'sometimes',
        'nullable',
        'numeric',
        'min:0',
        'max:100',
      ],
      'total_amount' => [
        'sometimes',
        'required',
        'numeric',
        'min:0',
      ],
      'observations' => [
        'sometimes',
        'nullable',
        'string',
      ],
      'supply_type' => [
        'sometimes',
        'nullable',
        'in:LIMA,IMPORTACION',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'order_quotation_id.required' => 'La cotización de orden es obligatoria.',
      'order_quotation_id.integer' => 'La cotización de orden debe ser un entero.',
      'order_quotation_id.exists' => 'La cotización de orden seleccionada no es válida.',

      'item_type.required' => 'El tipo de ítem es obligatorio.',
      'item_type.in' => 'El tipo de ítem debe ser PRODUCT o LABOR.',

      'product_id.integer' => 'El producto debe ser un entero.',
      'product_id.exists' => 'El producto seleccionado no es válido.',

      'description.required' => 'La descripción es obligatoria.',
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no puede exceder 255 caracteres.',

      'purchase_price.numeric' => 'El precio de compra debe ser un número.',
      'purchase_price.min' => 'El precio de compra no puede ser negativo.',

      'quantity.required' => 'La cantidad es obligatoria.',
      'quantity.numeric' => 'La cantidad debe ser un número.',
      'quantity.min' => 'La cantidad debe ser mayor a 0.',

      'unit_measure.required' => 'La unidad de medida es obligatoria.',
      'unit_measure.string' => 'La unidad de medida debe ser una cadena de texto.',
      'unit_measure.max' => 'La unidad de medida no puede exceder 50 caracteres.',

      'unit_price.required' => 'El precio unitario es obligatorio.',
      'unit_price.numeric' => 'El precio unitario debe ser un número.',
      'unit_price.min' => 'El precio unitario no puede ser negativo.',

      'discount_percentage.numeric' => 'El porcentaje de descuento debe ser un número.',
      'discount_percentage.min' => 'El porcentaje de descuento no puede ser negativo.',
      'discount_percentage.max' => 'El porcentaje de descuento no puede ser mayor a 100.',

      'total_amount.required' => 'El monto total es obligatorio.',
      'total_amount.numeric' => 'El monto total debe ser un número.',
      'total_amount.min' => 'El monto total no puede ser negativo.',

      'supply_type.in' => 'El tipo de suministro debe ser LIMA o IMPORTADO.',

      'observations.string' => 'Las observaciones deben ser una cadena de texto.',
    ];
  }
}
