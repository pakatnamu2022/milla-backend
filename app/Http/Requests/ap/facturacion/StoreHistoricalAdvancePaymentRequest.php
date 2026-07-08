<?php

namespace App\Http\Requests\ap\facturacion;

use App\Http\Requests\StoreRequest;

class StoreHistoricalAdvancePaymentRequest extends StoreRequest
{
  protected function prepareForValidation()
  {
    $intFields = [
      'sunat_concept_document_type_id',
      'series_id',
      'area_id',
      'client_id',
      'purchase_request_quote_id',
      'sunat_concept_currency_id',
      'account_plan_id',
      'sunat_concept_igv_type_id',
    ];

    $dataToMerge = [];
    foreach ($intFields as $field) {
      if ($this->has($field) && $this->input($field) !== null && $this->input($field) !== '') {
        $dataToMerge[$field] = (int) $this->input($field);
      }
    }

    if ($this->has('total') && $this->input('total') !== null) {
      $dataToMerge['total'] = abs((float) $this->input('total'));
    }

    if (!empty($dataToMerge)) {
      $this->merge($dataToMerge);
    }
  }

  public function rules(): array
  {
    return [
      'purchase_request_quote_id'        => ['required', 'integer', 'exists:purchase_request_quote,id'],
      'sunat_concept_document_type_id'   => ['required', 'integer', 'exists:gp_sunat_concepts,id'],
      'series_id'                        => ['required', 'integer', 'exists:ap_assign_sales_series,id'],
      'area_id'                          => ['required', 'integer', 'exists:ap_masters,id'],
      'client_id'                        => ['required', 'integer', 'exists:business_partners,id'],
      'fecha_de_emision'                 => ['required', 'date'],
      'sunat_concept_currency_id'        => ['required', 'integer', 'exists:gp_sunat_concepts,id'],
      'total'                            => ['required', 'numeric', 'gt:0'],
      'descripcion'                      => ['required', 'string', 'max:500'],
      'account_plan_id'                  => ['nullable', 'integer', 'exists:ap_accounting_account_plan,id'],
      'sunat_concept_igv_type_id'        => ['nullable', 'integer', 'exists:gp_sunat_concepts,id'],
      'observaciones'                    => ['nullable', 'string'],
      'condiciones_de_pago'              => ['nullable', 'string'],
    ];
  }

  public function messages(): array
  {
    return [
      'purchase_request_quote_id.required' => 'La cotización de solicitud de compra es obligatoria',
      'purchase_request_quote_id.exists'   => 'La cotización de solicitud de compra no existe',
      'sunat_concept_document_type_id.required' => 'El tipo de documento es obligatorio',
      'series_id.required'               => 'La serie es obligatoria',
      'area_id.required'                 => 'El área es obligatoria',
      'client_id.required'               => 'El cliente es obligatorio',
      'fecha_de_emision.required'        => 'La fecha de emisión es obligatoria',
      'sunat_concept_currency_id.required' => 'La moneda es obligatoria',
      'total.required'                   => 'El monto total del anticipo es obligatorio',
      'total.gt'                         => 'El total del anticipo debe ser mayor a 0',
      'descripcion.required'             => 'La descripción del anticipo es obligatoria',
    ];
  }
}
