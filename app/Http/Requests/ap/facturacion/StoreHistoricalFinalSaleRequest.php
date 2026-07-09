<?php

namespace App\Http\Requests\ap\facturacion;

use App\Http\Requests\StoreRequest;

class StoreHistoricalFinalSaleRequest extends StoreRequest
{
  protected function prepareForValidation()
  {
    $intFields = [
      'sunat_concept_document_type_id',
      'numero',
      'area_id',
      'client_id',
      'sede_id',
      'sunat_concept_currency_id',
      'doc_type_currency_id',
      'type_currency_id',
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
      'vin'                            => ['required', 'string', 'exists:ap_vehicles,vin'],
      'client_id'                      => ['required', 'integer', 'exists:business_partners,id'],
      'sunat_concept_document_type_id' => ['required', 'integer', 'exists:sunat_concepts,id'],
      'serie'                          => ['required', 'string', 'max:20'],
      'numero'                         => ['required', 'integer', 'min:1'],
      'area_id'                        => ['required', 'integer', 'exists:ap_masters,id'],
      'sede_id'                        => ['required', 'integer', 'exists:config_sede,id'],
      'sunat_concept_currency_id'      => ['required', 'integer', 'exists:sunat_concepts,id'],
      'doc_type_currency_id'           => ['required', 'integer', 'exists:type_currency,id'],
      'type_currency_id'               => ['required', 'integer', 'exists:ap_masters,id'],
      'total'                          => ['required', 'numeric', 'gt:0'],
      'descripcion'                    => ['required', 'string', 'max:500'],
    ];
  }

  public function messages(): array
  {
    return [
      'vin.required'                            => 'El VIN del vehículo es obligatorio',
      'vin.exists'                              => 'El VIN no corresponde a ningún vehículo registrado',
      'client_id.required'                      => 'El cliente es obligatorio',
      'client_id.exists'                        => 'El cliente no existe',
      'sunat_concept_document_type_id.required' => 'El tipo de documento es obligatorio',
      'serie.required'                          => 'La serie es obligatoria',
      'serie.max'                               => 'La serie no puede exceder 20 caracteres',
      'numero.required'                         => 'El número de comprobante es obligatorio',
      'numero.integer'                          => 'El número de comprobante debe ser entero',
      'numero.min'                              => 'El número de comprobante debe ser mayor a 0',
      'area_id.required'                        => 'El área es obligatoria',
      'sede_id.required'                        => 'La sede es obligatoria',
      'sede_id.exists'                          => 'La sede no existe',
      'sunat_concept_currency_id.required'      => 'La moneda SUNAT es obligatoria',
      'doc_type_currency_id.required'           => 'La moneda del documento es obligatoria',
      'type_currency_id.required'               => 'El tipo de moneda es obligatorio',
      'total.required'                          => 'El monto total de la venta es obligatorio',
      'total.gt'                                => 'El total debe ser mayor a 0',
      'descripcion.required'                    => 'La descripción del ítem es obligatoria',
    ];
  }
}
