<?php

namespace App\Http\Requests\ap\facturacion;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsolidatedInvoiceRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'internal_note_ids' => 'required|array|min:1',
      'internal_note_ids.*' => 'required|integer|exists:ap_internal_notes,id',
      'sunat_concept_document_type_id' => 'required|integer|exists:sunat_concepts,id',
      'sunat_concept_transaction_type_id' => 'required|integer|exists:sunat_concepts,id',
      'serie' => 'required|string|max:10',
      'sunat_concept_currency_id' => 'required|integer|exists:sunat_concepts,id',
      'fecha_de_emision' => 'required|date',
      'fecha_de_vencimiento' => 'nullable|date|after_or_equal:fecha_de_emision',
      'observaciones' => 'nullable|string|max:1000',
    ];
  }

  public function messages(): array
  {
    return [
      'internal_note_ids.required' => 'Debe seleccionar al menos una nota interna',
      'internal_note_ids.array' => 'Las notas internas deben ser un arreglo',
      'internal_note_ids.min' => 'Debe seleccionar al menos una nota interna',
      'internal_note_ids.*.required' => 'Cada nota interna es requerida',
      'internal_note_ids.*.integer' => 'Cada nota interna debe ser un número entero',
      'internal_note_ids.*.exists' => 'Una o más notas internas no existen',
      'sunat_concept_document_type_id.required' => 'El tipo de documento es requerido',
      'sunat_concept_document_type_id.integer' => 'El tipo de documento debe ser un número entero',
      'sunat_concept_document_type_id.exists' => 'El tipo de documento no existe',
      'sunat_concept_transaction_type_id.integer' => 'El tipo de transacción debe ser un número entero',
      'sunat_concept_transaction_type_id.required' => 'El tipo de documento es requerido',
      'sunat_concept_transaction_type_id.exists' => 'El tipo de transacción no existe',
      'serie.required' => 'La serie es requerida',
      'sunat_concept_currency_id.required' => 'La moneda es requerida',
      'fecha_de_emision.required' => 'La fecha de emisión es requerida',
      'fecha_de_vencimiento.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a la fecha de emisión',
    ];
  }
}
