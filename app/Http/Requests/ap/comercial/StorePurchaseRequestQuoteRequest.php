<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StorePurchaseRequestQuoteRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'type_document' => [
        'required',
        'string',
        'in:COTIZACION,SOLICITUD_COMPRA'
      ],
      'type_vehicle' => [
        'required',
        'string',
        'in:NUEVO,USADO'
      ],
      'quote_deadline' => ['nullable', 'date'],
      'subtotal' => ['required', 'numeric'],
      'total' => ['required', 'numeric'],
      'comment' => ['nullable', 'string', 'max:255'],
      'opportunity_id' => ['nullable', 'exists:ap_opportunity,id'],
      'holder_id' => ['required', 'exists:business_partners,id'],
      'vehicle_color_id' => ['required', 'exists:ap_commercial_masters,id'],
      'ap_models_vn_id' => ['nullable', 'exists:ap_models_vn,id'],
      'vehicle_vn_id' => ['nullable', 'exists:vehicle_vn,id'],
      'doc_type_currency_id' => ['required', 'exists:type_currency,id'],
      'ap_vehicle_purchase_order_id' => ['nullable', 'exists:vehicle_purchase_order,id'],
      'with_vin' => ['nullable', 'boolean'],
      'sale_price' => ['required', 'numeric', 'min:0'],

      // Validaciones para bonus_discounts
      'bonus_discounts' => ['nullable', 'array'],
      'bonus_discounts.*.concept_id' => ['required', 'exists:ap_commercial_masters,id'],
      'bonus_discounts.*.description' => ['required', 'string', 'max:255'],
      'bonus_discounts.*.type' => ['required', 'string', 'in:MONTO_FIJO,PORCENTAJE'],
      'bonus_discounts.*.value' => ['required', 'numeric', 'min:0'],

      // Validaciones para accessories
      'accessories' => ['nullable', 'array'],
      'accessories.*.accessory_id' => ['required', 'exists:approved_accessories,id'],
      'accessories.*.quantity' => ['required', 'integer', 'min:1'],
    ];
  }

  public function messages(): array
  {
    return [
      'type_document.required' => 'El campo tipo de documento es obligatorio.',
      'type_document.string' => 'El campo tipo de documento debe ser una cadena de texto.',
      'type_document.in' => 'El campo tipo de documento debe ser COTIZACION o SOLICITUD_COMPRA.',

      'type_vehicle.required' => 'El campo tipo de vehículo es obligatorio.',
      'type_vehicle.string' => 'El campo tipo de vehículo debe ser una cadena de texto.',
      'type_vehicle.in' => 'El campo tipo de vehículo debe ser NUEVO o USADO.',

      'quote_deadline.date' => 'El campo fecha límite de cotización debe ser una fecha válida.',

      'subtotal.required' => 'El campo subtotal es obligatorio.',
      'subtotal.numeric' => 'El campo subtotal debe ser un número.',

      'total.required' => 'El campo total es obligatorio.',
      'total.numeric' => 'El campo total debe ser un número.',

      'comment.string' => 'El campo comentario debe ser una cadena de texto.',
      'comment.max' => 'El campo comentario no debe exceder los 255 caracteres.',

      'opportunity_id.exists' => 'La oportunidad seleccionada no es válida.',

      'holder_id.required' => 'El campo titular es obligatorio.',
      'holder_id.exists' => 'El titular seleccionado no es válido.',

      'vehicle_color_id.required' => 'El campo color del vehículo es obligatorio.',
      'vehicle_color_id.exists' => 'El color del vehículo seleccionado no es válido.',

      'ap_models_vn_id.exists' => 'El modelo VN seleccionado no es válido.',

      'vehicle_vn_id.exists' => 'El vehículo VN seleccionado no es válido.',

      'doc_type_currency_id.required' => 'El campo tipo de moneda es obligatorio.',
      'doc_type_currency_id.exists' => 'El tipo de moneda seleccionado no es válido.',

      'ap_vehicle_purchase_order_id.exists' => 'La orden de compra de vehículo seleccionada no es válida.',

      'with_vin.boolean' => 'El campo con VIN debe ser verdadero o falso.',

      'sale_price.required' => 'El campo precio de venta es obligatorio.',
      'sale_price.numeric' => 'El campo precio de venta debe ser un número.',
      'sale_price.min' => 'El campo precio de venta debe ser mayor o igual a 0.',

      // Mensajes para bonus_discounts
      'bonus_discounts.array' => 'Los descuentos/bonos deben ser una lista.',
      'bonus_discounts.*.concept_id.required' => 'El concepto es obligatorio para cada descuento/bono.',
      'bonus_discounts.*.concept_id.exists' => 'El concepto seleccionado no es válido.',
      'bonus_discounts.*.description.required' => 'La descripción es obligatoria para cada descuento/bono.',
      'bonus_discounts.*.description.string' => 'La descripción debe ser una cadena de texto.',
      'bonus_discounts.*.description.max' => 'La descripción no debe exceder los 255 caracteres.',
      'bonus_discounts.*.type.required' => 'El tipo es obligatorio para cada descuento/bono.',
      'bonus_discounts.*.type.in' => 'El tipo debe ser MONTO_FIJO o PORCENTAJE.',
      'bonus_discounts.*.value.required' => 'El valor es obligatorio para cada descuento/bono.',
      'bonus_discounts.*.value.numeric' => 'El valor debe ser un número.',
      'bonus_discounts.*.value.min' => 'El valor debe ser mayor o igual a 0.',

      // Mensajes para accessories
      'accessories.array' => 'Los accesorios deben ser una lista.',
      'accessories.*.accessory_id.required' => 'El accesorio es obligatorio.',
      'accessories.*.accessory_id.exists' => 'El accesorio seleccionado no es válido.',
      'accessories.*.quantity.required' => 'La cantidad es obligatoria para cada accesorio.',
      'accessories.*.quantity.integer' => 'La cantidad debe ser un número entero.',
      'accessories.*.quantity.min' => 'La cantidad debe ser al menos 1.',
    ];
  }
}
