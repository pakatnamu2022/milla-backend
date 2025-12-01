<?php

namespace App\Http\Requests\ap\postventa\gestionProductos;

use App\Http\Requests\StoreRequest;

class UpdateTransferInventoryRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      // Datos básicos de la transferencia
      'movement_date' => 'nullable|date',
      'notes' => 'nullable|string|max:1000',

      // Datos de la Guía de Remisión (solo metadata, NO productos)
      'driver_name' => 'nullable|string|max:255',
      'driver_doc' => 'nullable|string|max:20',
      'license' => 'nullable|string|max:20',
      'plate' => 'nullable|string|max:20',
      'transfer_reason_id' => 'nullable|integer|exists:sunat_concepts,id',
      'transfer_modality_id' => 'nullable|integer|exists:sunat_concepts,id',
      'transport_company_id' => 'nullable|integer|exists:business_partners,id',
      'total_packages' => 'nullable|integer|min:1',
      'total_weight' => 'nullable|numeric|min:0',
    ];
  }

  public function messages(): array
  {
    return [
      'movement_date.date' => 'La fecha de movimiento debe ser una fecha válida',
      'notes.string' => 'Las notas deben ser texto',
      'notes.max' => 'Las notas no pueden exceder 1000 caracteres',

      // Guía de remisión
      'driver_name.string' => 'El nombre del conductor debe ser texto',
      'driver_name.max' => 'El nombre del conductor no puede exceder 255 caracteres',
      'driver_doc.string' => 'El documento del conductor debe ser texto',
      'driver_doc.max' => 'El documento del conductor no puede exceder 20 caracteres',
      'license.string' => 'La licencia debe ser texto',
      'license.max' => 'La licencia no puede exceder 20 caracteres',
      'plate.string' => 'La placa debe ser texto',
      'plate.max' => 'La placa no puede exceder 20 caracteres',
      'transfer_reason_id.integer' => 'El motivo de traslado debe ser un número entero',
      'transfer_reason_id.exists' => 'El motivo de traslado no es válido',
      'transfer_modality_id.integer' => 'La modalidad de traslado debe ser un número entero',
      'transfer_modality_id.exists' => 'La modalidad de traslado no es válida',
      'transport_company_id.integer' => 'La empresa de transporte debe ser un número entero',
      'transport_company_id.exists' => 'La empresa de transporte no existe',
      'total_packages.integer' => 'El total de bultos debe ser un número entero',
      'total_packages.min' => 'El total de bultos debe ser mayor a 0',
      'total_weight.numeric' => 'El peso total debe ser numérico',
      'total_weight.min' => 'El peso total debe ser mayor o igual a 0',
    ];
  }
}