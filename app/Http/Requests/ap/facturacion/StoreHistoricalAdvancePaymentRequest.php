<?php

namespace App\Http\Requests\ap\facturacion;

use App\Http\Requests\StoreRequest;

class StoreHistoricalAdvancePaymentRequest extends StoreRequest
{
  protected function prepareForValidation()
  {
    $intFields = [
      'sunat_concept_document_type_id',
      'numero',
      'area_id',
      'client_id',
      'purchase_request_quote_id',
      'sunat_concept_currency_id',
    ];

    $dataToMerge = [];
    foreach ($intFields as $field) {
      if ($this->has($field) && $this->input($field) !== null && $this->input($field) !== '') {
        $dataToMerge[$field] = (int)$this->input($field);
      }
    }

    if ($this->has('total') && $this->input('total') !== null) {
      $dataToMerge['total'] = abs((float)$this->input('total'));
    }

    if (!empty($dataToMerge)) {
      $this->merge($dataToMerge);
    }
  }

  public function rules(): array
  {
    return [
      'purchase_request_quote_id'      => ['required', 'integer', 'exists:purchase_request_quote,id'],
      'sunat_concept_document_type_id' => ['required', 'integer', 'exists:sunat_concepts,id'],
      'serie'                          => ['required', 'string', 'max:20'],
      'numero'                         => ['required', 'integer', 'min:1'],
      'area_id'                        => ['required', 'integer', 'exists:ap_masters,id'],
      'client_id'                      => ['required', 'integer', 'exists:business_partners,id'],
      'fecha_de_emision'               => ['required', 'date'],
      'sunat_concept_currency_id'      => ['required', 'integer', 'exists:sunat_concepts,id'],
      'total'                          => ['required', 'numeric', 'gt:0'],
    ];
  }

  public function messages(): array
  {
    return [
      'purchase_request_quote_id.required'      => 'La cotización de solicitud de compra es obligatoria',
      'purchase_request_quote_id.exists'        => 'La cotización de solicitud de compra no existe',
      'sunat_concept_document_type_id.required' => 'El tipo de documento es obligatorio',
      'serie.required'                          => 'La serie es obligatoria',
      'serie.max'                               => 'La serie no puede exceder 20 caracteres',
      'numero.required'                         => 'El número de comprobante es obligatorio',
      'numero.integer'                          => 'El número de comprobante debe ser entero',
      'numero.min'                              => 'El número de comprobante debe ser mayor a 0',
      'area_id.required'                        => 'El área es obligatoria',
      'client_id.required'                      => 'El cliente es obligatorio',
      'fecha_de_emision.required'               => 'La fecha de emisión es obligatoria',
      'sunat_concept_currency_id.required'      => 'La moneda es obligatoria',
      'total.required'                          => 'El monto total del anticipo es obligatorio',
      'total.gt'                                => 'El total del anticipo debe ser mayor a 0',
    ];
  }
}
