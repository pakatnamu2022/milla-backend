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
      'exchange_rate_id' => ['required', 'exists:exchange_rate,id'],
      'opportunity_id' => ['nullable', 'exists:ap_opportunity,id'],
      'holder_id' => ['required', 'exists:business_partners,id'],
      'vehicle_color_id' => ['required', 'exists:ap_commercial_masters,id'],
      'ap_models_vn_id' => ['nullable', 'exists:ap_models_vn,id'],
      'vehicle_vn_id' => ['nullable', 'exists:vehicle_vn,id'],
      'doc_type_currency_id' => ['required', 'exists:ap_commercial_masters,id']
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

      'exchange_rate_id.required' => 'El campo tipo de cambio es obligatorio.',
      'exchange_rate_id.exists' => 'El tipo de cambio seleccionado no es válido.',

      'opportunity_id.exists' => 'La oportunidad seleccionada no es válida.',

      'holder_id.required' => 'El campo titular es obligatorio.',
      'holder_id.exists' => 'El titular seleccionado no es válido.',

      'vehicle_color_id.required' => 'El campo color del vehículo es obligatorio.',
      'vehicle_color_id.exists' => 'El color del vehículo seleccionado no es válido.',

      'ap_models_vn_id.exists' => 'El modelo VN seleccionado no es válido.',

      'vehicle_vn_id.exists' => 'El vehículo VN seleccionado no es válido.',

      'doc_type_currency_id.required' => 'El campo tipo de moneda es obligatorio.',
      'doc_type_currency_id.exists' => 'El tipo de moneda seleccionado no es válido.'
    ];
  }
}
