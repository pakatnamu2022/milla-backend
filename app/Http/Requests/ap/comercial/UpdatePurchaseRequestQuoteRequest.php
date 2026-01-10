<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequestQuoteRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'type_document' => [
        'sometimes',
        'string',
        'in:COTIZACION,SOLICITUD_COMPRA'
      ],
      'quote_deadline' => ['nullable', 'date'],
      'base_selling_price' => ['sometimes', 'numeric'],
      'sale_price' => ['sometimes', 'numeric', 'min:0'],
      'doc_sale_price' => ['sometimes', 'numeric', 'min:0'],
      'comment' => ['nullable', 'string', 'max:255'],
      'warranty' => ['nullable', 'string', 'max:100'],
      'opportunity_id' => ['nullable', 'exists:ap_opportunity,id'],
      'holder_id' => ['sometimes', 'exists:business_partners,id'],
      'vehicle_color_id' => ['sometimes', 'exists:ap_masters,id'],
      'ap_models_vn_id' => ['nullable', 'exists:ap_models_vn,id'],
      'doc_type_currency_id' => ['sometimes', 'exists:type_currency,id'],
      'ap_vehicle_id' => ['nullable', 'exists:ap_vehicles,id'],

      // Validaciones para bonus_discounts
      'bonus_discounts' => ['nullable', 'array'],
      'bonus_discounts.*.concept_id' => ['required', 'exists:ap_masters,id'],
      'bonus_discounts.*.description' => ['required', 'string', 'max:255'],
      'bonus_discounts.*.type' => ['required', 'string', 'in:FIJO,PORCENTAJE'],
      'bonus_discounts.*.value' => ['required', 'numeric', 'min:0'],
      'bonus_discounts.*.is_negative' => ['nullable', 'boolean'],

      // Validaciones para accessories
      'accessories' => ['nullable', 'array'],
      'accessories.*.accessory_id' => ['required', 'exists:approved_accessories,id'],
      'accessories.*.quantity' => ['required', 'integer', 'min:1'],

      'type_currency_id' => ['sometimes', 'exists:ap_masters,id'],

      'sede_id' => ['sometimes', 'exists:config_sede,id']
    ];
  }

  public function messages(): array
  {
    return [
      'type_document.string' => 'El campo tipo de documento debe ser una cadena de texto.',
      'type_document.in' => 'El campo tipo de documento debe ser COTIZACION o SOLICITUD_COMPRA.',

      'quote_deadline.date' => 'El campo fecha límite de cotización debe ser una fecha válida.',

      'base_selling_price.numeric' => 'El campo precio de venta base debe ser un número.',

      'sale_price.numeric' => 'El campo precio de venta debe ser un número.',
      'sale_price.min' => 'El campo precio de venta debe ser mayor o igual a 0.',

      'doc_sale_price.numeric' => 'El campo precio de venta en documento debe ser un número.',
      'doc_sale_price.min' => 'El campo precio de venta en documento debe ser mayor o igual a 0.',

      'comment.string' => 'El campo comentario debe ser una cadena de texto.',
      'comment.max' => 'El campo comentario no debe exceder los 255 caracteres.',

      'warranty.string' => 'El campo garantía debe ser una cadena de texto.',
      'warranty.max' => 'El campo garantía no debe exceder los 100 caracteres.',

      'opportunity_id.exists' => 'La oportunidad seleccionada no es válida.',

      'holder_id.exists' => 'El titular seleccionado no es válido.',

      'vehicle_color_id.exists' => 'El color del vehículo seleccionado no es válido.',

      'ap_models_vn_id.exists' => 'El modelo VN seleccionado no es válido.',

      'doc_type_currency_id.exists' => 'El tipo de moneda seleccionado no es válido.',

      'ap_vehicle_id.exists' => 'El vehículo seleccionado no es válida.',

      // Mensajes para bonus_discounts
      'bonus_discounts.array' => 'Los descuentos/bonos deben ser una lista.',
      'bonus_discounts.*.concept_id.required' => 'El concepto es obligatorio para cada descuento/bono.',
      'bonus_discounts.*.concept_id.exists' => 'El concepto seleccionado no es válido.',
      'bonus_discounts.*.description.required' => 'La descripción es obligatoria para cada descuento/bono.',
      'bonus_discounts.*.description.string' => 'La descripción debe ser una cadena de texto.',
      'bonus_discounts.*.description.max' => 'La descripción no debe exceder los 255 caracteres.',
      'bonus_discounts.*.type.required' => 'El tipo es obligatorio para cada descuento/bono.',
      'bonus_discounts.*.type.in' => 'El tipo debe ser FIJO o PORCENTAJE.',
      'bonus_discounts.*.value.required' => 'El valor es obligatorio para cada descuento/bono.',
      'bonus_discounts.*.value.numeric' => 'El valor debe ser un número.',
      'bonus_discounts.*.value.min' => 'El valor debe ser mayor o igual a 0.',
      'bonus_discounts.*.is_negative.boolean' => 'El campo es negativo debe ser verdadero o falso.',

      // Mensajes para accessories
      'accessories.array' => 'Los accesorios deben ser una lista.',
      'accessories.*.accessory_id.required' => 'El accesorio es obligatorio.',
      'accessories.*.accessory_id.exists' => 'El accesorio seleccionado no es válido.',
      'accessories.*.quantity.required' => 'La cantidad es obligatoria para cada accesorio.',
      'accessories.*.quantity.integer' => 'La cantidad debe ser un número entero.',
      'accessories.*.quantity.min' => 'La cantidad debe ser al menos 1.',

      'type_currency_id.exists' => 'El tipo de moneda seleccionado no es válido.',

      'sede_id.exists' => 'La sede seleccionada no es válida.',
    ];
  }
}
