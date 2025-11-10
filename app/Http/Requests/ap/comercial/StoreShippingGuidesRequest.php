<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StoreShippingGuidesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'document_type' => 'required|string|max:100',
      'issuer_type' => 'required|string|max:100',
      'document_series_id' => 'nullable|integer|exists:assign_sales_series,id',
      'series' => 'nullable|string|max:20',
      'correlative' => 'nullable|string',
      'issue_date' => 'required|date',
      'sede_transmitter_id' => 'required|integer|exists:config_sede,id',
      'sede_receiver_id' => 'required|integer|exists:config_sede,id',
      'transmitter_id' => 'required|integer|exists:business_partners_establishment,id',
      'receiver_id' => 'required|integer|exists:business_partners_establishment,id',
      'ap_vehicle_id' => 'required|integer|exists:ap_vehicles,id',
      'requires_sunat' => 'nullable|boolean',
      'total_packages' => 'nullable|numeric|min:0',
      'total_weight' => 'nullable|numeric|min:0',
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
      'ap_class_article_id' => 'required|integer|exists:ap_class_article,id',
    ];
  }

  public function messages(): array
  {
    return [
      'document_type.required' => 'El tipo de documento es obligatorio.',
      'issuer_type.required' => 'El tipo de emisor es obligatorio.',
      'document_series_id.integer' => 'El ID de la serie de documentos es inválido.',
      'document_series_id.exists' => 'La serie de documentos no existe.',
      'issue_date.required' => 'La fecha de emisión es obligatoria.',
      'sede_transmitter_id.required' => 'El sede origen es obligatorio.',
      'sede_transmitter_id.integer' => 'El sede origen es invalido.',
      'sede_transmitter_id.exists' => 'El sede origen no existe.',
      'sede_receiver_id.required' => 'El sede destino es obligatorio.',
      'sede_receiver_id.integer' => 'El sede destino es invalido.',
      'sede_receiver_id.exists' => 'El sede destino no existe.',
      'transmitter_id.required' => 'El remitente es obligatorio.',
      'receiver_id.required' => 'El destinatario es obligatorio.',
      'file.mimes' => 'El archivo debe ser un PDF, JPG, JPEG, PNG o XML.',
      'file.max' => 'El tamaño máximo del archivo es 10MB.',
      'requires_sunat' => 'El campo requiere sunat debe ser verdadero o falso.',
      'total_packages.numeric' => 'El total de bultos debe ser un número.',
      'total_packages.min' => 'El total de bultos no puede ser negativo.',
      'total_weight.numeric' => 'El peso total debe ser un número.',
      'total_weight.min' => 'El peso total no puede ser negativo.',
      'ap_vehicle_id.required' => 'El vehículo es obligatorio.',
      'ap_vehicle_id.integer' => 'El ID del vehículo es inválido.',
      'ap_vehicle_id.exists' => 'El vehículo no existe.',
      'transport_company_id.integer' => 'El ID de la empresa de transporte es inválido.',
      'transport_company_id.exists' => 'La empresa de transporte no existe.',
      'transfer_reason_id.integer' => 'El ID del motivo de traslado es inválido.',
      'transfer_reason_id.exists' => 'El motivo de traslado no existe.',
      'transfer_modality_id.integer' => 'El ID de la modalidad de traslado es inválido.',
      'transfer_modality_id.exists' => 'La modalidad de traslado no existe.',
      'ap_class_article_id.required' => 'La clase de artículo es obligatoria.',
      'ap_class_article_id.integer' => 'El ID de la clase de artículo es inválido.',
      'ap_class_article_id.exists' => 'La clase de artículo no existe.',
    ];
  }
}
