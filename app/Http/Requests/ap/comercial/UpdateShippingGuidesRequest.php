<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class UpdateShippingGuidesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'document_type' => 'sometimes|required|string|max:100',
      'issuer_type' => 'sometimes|required|string|max:100',
      'issue_date' => 'nullable|date',
      'document_series_id' => 'nullable|integer|exists:assign_sales_series,id',
      'series' => 'nullable|string|max:20',
      'correlative' => 'nullable|string',
      'sede_transmitter_id' => 'sometimes|string|exists:config_sede,id',
      'sede_receiver_id' => 'sometimes|string|exists:config_sede,id',
      'requires_sunat' => 'nullable|boolean',
      'total_packages' => 'nullable|numeric|min:0',
      'total_weight' => 'nullable|numeric|min:0',
      'transmitter_id' => 'sometimes|required|integer|exists:business_partners_establishment,id',
      'receiver_id' => 'sometimes|required|integer|exists:business_partners_establishment,id',
      'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xml|max:10240',
      'transport_company_id' => 'nullable|integer|exists:business_partners,id',
      'driver_doc' => 'nullable|string|max:255',
      'license' => 'nullable|string|max:255',
      'plate' => 'nullable|string|max:255',
      'driver_name' => 'nullable|string|max:255',
      'notes' => 'nullable|string',
      'status' => 'nullable|boolean',
      'transfer_reason_id' => 'nullable|integer|exists:sunat_concepts,id',
      'transfer_modality_id' => 'nullable|integer|exists:sunat_concepts,id',
      'ap_class_article_id' => 'nullable|integer|exists:ap_class_article,id',
      'send_dynamics' => 'nullable|boolean',
      'is_consignment' => 'nullable|boolean',
    ];
  }

  public function messages(): array
  {
    return [
      'document_type.required' => 'El tipo de documento es obligatorio cuando se proporciona.',
      'issuer_type.required' => 'El tipo de emisor es obligatorio cuando se proporciona.',
      'sede_transmitter_id.exists' => 'El sede origen no existe.',
      'sede_receiver_id.exists' => 'El sede destino no existe.',
      'transmitter_id.required' => 'El remitente es obligatorio cuando se proporciona.',
      'receiver_id.required' => 'El destinatario es obligatorio cuando se proporciona.',
      'file.mimes' => 'El archivo debe ser un PDF, JPG, JPEG, PNG o XML.',
      'file.max' => 'El tamaño máximo del archivo es 10MB.',
      'requires_sunat' => 'El campo requiere sunat debe ser verdadero o falso.',
      'total_packages.numeric' => 'El total de bultos debe ser un número.',
      'total_packages.min' => 'El total de bultos no puede ser negativo.',
      'total_weight.numeric' => 'El peso total debe ser un número.',
      'total_weight.min' => 'El peso total no puede ser negativo.',
      'transport_company_id.exists' => 'La empresa de transporte no existe.',
      'transfer_reason_id.exists' => 'La razón de transferencia no existe.',
      'transfer_modality_id.exists' => 'La modalidad de transferencia no existe.',
      'ap_class_article_id.exists' => 'La clase de artículo no existe.',
    ];
  }
}
