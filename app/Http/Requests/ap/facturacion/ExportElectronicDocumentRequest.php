<?php

namespace App\Http\Requests\ap\facturacion;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExportElectronicDocumentRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'title'                            => 'nullable|string|max:100',

      // Tipo de documento
      'sunat_concept_document_type_id'   => 'nullable|integer',

      // Serie y número
      'serie'                            => 'nullable|string|max:4',
      'numero'                           => 'nullable|integer|min:1',

      // Área
      'area_id'                          => 'nullable|integer',

      // Fecha de emisión (rango)
      'fecha_de_emision'                 => 'nullable|array|size:2',
      'fecha_de_emision.0'               => 'nullable|date',
      'fecha_de_emision.1'               => 'nullable|date|after_or_equal:fecha_de_emision.0',

      // Estado SUNAT
      'status'                           => [
        'nullable',
        'string',
        Rule::in([
          ElectronicDocument::STATUS_DRAFT,
          ElectronicDocument::STATUS_SENT,
          ElectronicDocument::STATUS_ACCEPTED,
          ElectronicDocument::STATUS_REJECTED,
          ElectronicDocument::STATUS_CANCELLED,
        ]),
      ],
      'aceptada_por_sunat'               => 'nullable|boolean',
      'anulado'                          => 'nullable|boolean',

      // Cliente
      'cliente_numero_de_documento'      => 'nullable|string|max:15',

      // Moneda
      'sunat_concept_currency_id'        => 'nullable|integer',

      // Sede
      'seriesModel$sede_id'              => 'nullable|integer',

      // Origen
      'origin_entity_type'               => 'nullable|string|max:100',
      'origin_entity_id'                 => 'nullable|integer',
      'ap_vehicle_movement_id'           => 'nullable|integer',
      'purchase_request_quote_id'        => 'nullable|integer',
      'order_quotation_id'               => 'nullable|integer',
      'work_order_id'                    => 'nullable|integer',

      // Nota de crédito / documento original
      'original_document_id'             => 'nullable|integer',
      'is_advance_payment'               => 'nullable|boolean',

      // Tipo de consolidación
      'consolidation_type'               => [
        'nullable',
        'string',
        Rule::in([ElectronicDocument::CONSOLIDATION_SIMPLE, ElectronicDocument::CONSOLIDATION_MASSIVE]),
      ],

      // Placa OT
      'workOrder$vehicle_plate'          => 'nullable|string|max:10',

      // Estado de migración
      'migration_status'                 => 'nullable|string',

      // Creador
      'created_by'                       => 'nullable|integer|exists:users,id',
    ];
  }

  public function attributes(): array
  {
    return [
      'sunat_concept_document_type_id' => 'tipo de documento',
      'serie'                          => 'serie',
      'numero'                         => 'número',
      'area_id'                        => 'área',
      'fecha_de_emision'               => 'fecha de emisión',
      'fecha_de_emision.0'             => 'fecha de emisión desde',
      'fecha_de_emision.1'             => 'fecha de emisión hasta',
      'status'                         => 'estado',
      'aceptada_por_sunat'             => 'aceptada por SUNAT',
      'anulado'                        => 'anulado',
      'cliente_numero_de_documento'    => 'número de documento del cliente',
      'sunat_concept_currency_id'      => 'moneda',
      'seriesModel$sede_id'            => 'sede',
      'origin_entity_type'             => 'tipo de entidad de origen',
      'origin_entity_id'               => 'entidad de origen',
      'ap_vehicle_movement_id'         => 'movimiento de vehículo',
      'purchase_request_quote_id'      => 'cotización de compra',
      'order_quotation_id'             => 'cotización de OT',
      'work_order_id'                  => 'orden de trabajo',
      'original_document_id'           => 'documento original',
      'is_advance_payment'             => 'es anticipo',
      'consolidation_type'             => 'tipo de consolidación',
      'workOrder$vehicle_plate'        => 'placa del vehículo',
      'migration_status'               => 'estado de migración',
      'created_by'                     => 'creado por',
    ];
  }

  public function failedValidation(Validator $validator)
  {
    throw new ValidationException($validator, response()->json([
      'message' => $validator->errors()->first(),
      'errors'  => $validator->errors(),
    ], 422));
  }
}
