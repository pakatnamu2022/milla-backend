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
      'type_vehicle' => [
        'sometimes',
        'string',
        'in:NUEVO,USADO'
      ],
      'quote_deadline' => ['nullable', 'date'],
      'subtotal' => ['sometimes', 'numeric'],
      'total' => ['sometimes', 'numeric'],
      'comment' => ['nullable', 'string', 'max:255'],
      'exchange_rate_id' => ['sometimes', 'exists:exchange_rate,id'],
      'opportunity_id' => ['nullable', 'exists:ap_opportunity,id'],
      'holder_id' => ['sometimes', 'exists:business_partners,id'],
      'vehicle_color_id' => ['sometimes', 'exists:ap_commercial_masters,id'],
      'ap_models_vn_id' => ['nullable', 'exists:ap_models_vn,id'],
      'vehicle_vn_id' => ['nullable', 'exists:vehicle_vn,id'],
      'doc_type_currency_id' => ['sometimes', 'exists:ap_commercial_masters,id']
    ];
  }

  public function messages(): array
  {
    return [
      'type_document.string' => 'El campo tipo de documento debe ser una cadena de texto.',
      'type_document.in' => 'El campo tipo de documento debe ser COTIZACION o SOLICITUD_COMPRA.',

      'type_vehicle.string' => 'El campo tipo de vehículo debe ser una cadena de texto.',
      'type_vehicle.in' => 'El campo tipo de vehículo debe ser NUEVO o USADO.',

      'quote_deadline.date' => 'El campo fecha límite de cotización debe ser una fecha válida.',

      'subtotal.numeric' => 'El campo subtotal debe ser un número.',

      'total.numeric' => 'El campo total debe ser un número.',

      'comment.string' => 'El campo comentario debe ser una cadena de texto.',
      'comment.max' => 'El campo comentario no debe exceder los 255 caracteres.',

      'exchange_rate_id.exists' => 'El tipo de cambio seleccionado no es válido.',

      'opportunity_id.exists' => 'La oportunidad seleccionada no es válida.',

      'holder_id.exists' => 'El titular seleccionado no es válido.',

      'vehicle_color_id.exists' => 'El color del vehículo seleccionado no es válido.',

      'ap_models_vn_id.exists' => 'El modelo VN seleccionado no es válido.',

      'vehicle_vn_id.exists' => 'El vehículo VN seleccionado no es válido.',

      'doc_type_currency_id.exists' => 'El tipo de moneda seleccionado no es válido.'
    ];
  }
}
