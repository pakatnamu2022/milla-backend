<?php

namespace App\Http\Requests\ap\facturacion;

use App\Http\Requests\StoreRequest;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use Illuminate\Validation\Rule;

class UpdateElectronicDocumentRequest extends StoreRequest
{
  /**
   * Prepare the data for validation.
   */
  protected function prepareForValidation()
  {
    // Compatibilidad de nombres - Convertir 'serie' a 'series' si existe y es numérico
    if ($this->has('serie') && !$this->has('series') && is_numeric($this->input('serie'))) {
      $this->merge(['series' => (int)$this->input('serie')]);
    }

    // Convertir el ID de series a la serie real (string)
    if ($this->has('series')) {
      $seriesId = (int)$this->input('series');
      $assignSeries = AssignSalesSeries::find($seriesId);

      if ($assignSeries) {
        $this->merge([
          'assign_sales_series_id' => $seriesId,
          'serie' => $assignSeries->series, // String "F001"
        ]);
      } else {
        // Si no se encuentra la serie asignada, establecer un valor por defecto
        // para que la validación posterior detecte el error
        $this->merge([
          'serie' => null,
        ]);
      }
    }

    // Convertir strings numéricos a integers/decimals/booleans
    $numericFields = [
      'ap_billing_document_type_id',
      'numero',
      'ap_billing_transaction_type_id',
      'ap_vehicle_movement_id',
      'client_id',
      'purchase_request_quote_id',
      'ap_billing_currency_id',
      'ap_billing_detraction_type_id',
      'documento_que_se_modifica_tipo',
      'documento_que_se_modifica_numero',
      'ap_billing_credit_note_type_id',
      'ap_billing_debit_note_type_id',
      'percepcion_tipo',
      'retencion_tipo',
      'medio_de_pago_detraccion',
    ];

    $dataToMerge = [];
    foreach ($numericFields as $field) {
      if ($this->has($field) && $this->input($field) !== null && $this->input($field) !== '') {
        $dataToMerge[$field] = (int)$this->input($field);
      }
    }

    // Convertir strings numéricos a decimales para totales
    $decimalFields = [
      'tipo_de_cambio',
      'porcentaje_de_igv',
      'descuento_global',
      'total_descuento',
      'total_anticipo',
      'total_gravada',
      'total_inafecta',
      'total_exonerada',
      'total_igv',
      'total_gratuita',
      'total_otros_cargos',
      'total_isc',
      'total',
      'percepcion_base_imponible',
      'total_percepcion',
      'total_incluido_percepcion',
      'retencion_base_imponible',
      'total_retencion',
      'detraccion_total',
      'detraccion_porcentaje',
    ];

    foreach ($decimalFields as $field) {
      if ($this->has($field) && $this->input($field) !== null && $this->input($field) !== '') {
        $dataToMerge[$field] = (float)$this->input($field);
      }
    }

    // Convertir strings booleanos
    $booleanFields = [
      'detraccion',
      'enviar_automaticamente_a_la_sunat',
      'enviar_automaticamente_al_cliente',
      'generado_por_contingencia',
    ];

    foreach ($booleanFields as $field) {
      if ($this->has($field) && $this->input($field) !== null) {
        $value = $this->input($field);
        $dataToMerge[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $value;
      }
    }

    // Convertir items
    if ($this->has('items') && is_array($this->input('items'))) {
      $items = $this->input('items');
      foreach ($items as $index => $item) {
        if (isset($item['sunat_concept_igv_type_id'])) {
          $items[$index]['sunat_concept_igv_type_id'] = (int)$item['sunat_concept_igv_type_id'];
        }
        if (isset($item['account_plan_id'])) {
          $items[$index]['account_plan_id'] = (int)$item['account_plan_id'];
        }
        $numericItemFields = ['cantidad', 'valor_unitario', 'precio_unitario', 'descuento', 'subtotal', 'igv', 'total'];
        foreach ($numericItemFields as $field) {
          if (isset($item[$field])) {
            $items[$index][$field] = (float)$item[$field];
          }
        }
        if (isset($item['anticipo_regularizacion'])) {
          $items[$index]['anticipo_regularizacion'] = filter_var($item['anticipo_regularizacion'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($item['anticipo_documento_numero'])) {
          $items[$index]['anticipo_documento_numero'] = (int)$item['anticipo_documento_numero'];
        }
      }
      $dataToMerge['items'] = $items;
    }

    // Convertir guías
    if ($this->has('guias') && is_array($this->input('guias'))) {
      $guias = $this->input('guias');
      foreach ($guias as $index => $guia) {
        if (isset($guia['guia_tipo'])) {
          $guias[$index]['guia_tipo'] = (int)$guia['guia_tipo'];
        }
      }
      $dataToMerge['guias'] = $guias;
    }

    // Convertir cuotas
    if ($this->has('venta_al_credito') && is_array($this->input('venta_al_credito'))) {
      $cuotas = $this->input('venta_al_credito');
      foreach ($cuotas as $index => $cuota) {
        if (isset($cuota['cuota'])) {
          $cuotas[$index]['cuota'] = (int)$cuota['cuota'];
        }
        if (isset($cuota['importe'])) {
          $cuotas[$index]['importe'] = (float)$cuota['importe'];
        }
      }
      $dataToMerge['venta_al_credito'] = $cuotas;
    }

    // Aplicar todas las conversiones
    if (!empty($dataToMerge)) {
      $this->merge($dataToMerge);
    }
  }

  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    $userId = $this->user()->id;
    return [
      // Tipo de documento y serie (todos opcionales excepto validaciones de existencia)
      'ap_billing_document_type_id' => 'nullable|integer|exists:ap_billing_document_types,id',
      'series' => [
        'nullable',
        'integer',
        Rule::exists('assign_sales_series', 'id')
          ->where('status', 1)->whereNull('deleted_at'),
        Rule::exists('user_series_assignment', 'voucher_id')
          ->where('worker_id', $userId)
      ],
      'serie' => 'nullable|string|size:4', // Campo generado automáticamente desde series
      'numero' => 'nullable|integer|min:1',

      // Tipo de operación
      'ap_billing_transaction_type_id' => 'nullable|integer|exists:ap_billing_transaction_types,id',

      // Origen del documento
      'origin_module' => ['nullable', Rule::in(['comercial', 'posventa'])],
      'origin_entity_type' => 'nullable|string|max:100',
      'origin_entity_id' => 'nullable|integer',
      'ap_vehicle_movement_id' => 'nullable|integer|exists:ap_vehicle_movement,id',

      // Datos del cliente
      'client_id' => [
        'nullable',
        'integer',
        Rule::exists('business_partners', 'id')
          ->whereNull('deleted_at')->where('status_ap', 1)
      ],
      'purchase_request_quote_id' => [
        'nullable',
        'integer',
        Rule::exists('purchase_request_quote', 'id')
          ->whereNull('deleted_at')->where('status', 1)
      ],
      'cliente_email_1' => 'nullable|email|max:250',
      'cliente_email_2' => 'nullable|email|max:250',

      // Fechas
      'fecha_de_emision' => 'nullable|date',
      'fecha_de_vencimiento' => 'nullable|date|after:fecha_de_emision',

      // Moneda
      'ap_billing_currency_id' => 'nullable|integer|exists:ap_billing_currencies,id',
      'tipo_de_cambio' => 'nullable|numeric|min:0|max:999.999',
      'porcentaje_de_igv' => 'nullable|numeric|min:0|max:99.99',

      // Totales
      'descuento_global' => 'nullable|numeric|min:0',
      'total_descuento' => 'nullable|numeric|min:0',
      'total_anticipo' => 'nullable|numeric|min:0',
      'total_gravada' => 'nullable|numeric|min:0',
      'total_inafecta' => 'nullable|numeric|min:0',
      'total_exonerada' => 'nullable|numeric|min:0',
      'total_igv' => 'nullable|numeric|min:0',
      'total_gratuita' => 'nullable|numeric|min:0',
      'total_otros_cargos' => 'nullable|numeric|min:0',
      'total_isc' => 'nullable|numeric|min:0',
      'total' => 'nullable|numeric|min:0',

      // Percepción
      'percepcion_tipo' => 'nullable|integer|between:1,3',
      'percepcion_base_imponible' => 'nullable|numeric|min:0',
      'total_percepcion' => 'nullable|numeric|min:0',
      'total_incluido_percepcion' => 'nullable|numeric|min:0',

      // Retención
      'retencion_tipo' => 'nullable|integer|between:1,2',
      'retencion_base_imponible' => 'nullable|numeric|min:0',
      'total_retencion' => 'nullable|numeric|min:0',

      // Detracción
      'detraccion' => 'nullable|boolean',
      'ap_billing_detraction_type_id' => 'nullable|integer|exists:ap_billing_detraction_types,id',
      'detraccion_total' => 'nullable|numeric|min:0',
      'detraccion_porcentaje' => 'nullable|numeric|min:0|max:100',
      'medio_de_pago_detraccion' => 'nullable|integer|between:1,12',

      // Notas de crédito/débito
      'documento_que_se_modifica_tipo' => 'nullable|integer|between:1,2',
      'documento_que_se_modifica_serie' => 'nullable|string|size:4',
      'documento_que_se_modifica_numero' => 'nullable|integer|min:1',
      'ap_billing_credit_note_type_id' => 'nullable|integer|exists:ap_billing_credit_note_types,id',
      'ap_billing_debit_note_type_id' => 'nullable|integer|exists:ap_billing_debit_note_types,id',

      // Campos opcionales
      'observaciones' => 'nullable|string|max:1000',
      'condiciones_de_pago' => 'nullable|string|max:250',
      'medio_de_pago' => 'nullable|string|max:250',
      'placa_vehiculo' => 'nullable|string|max:8',
      'orden_compra_servicio' => 'nullable|string|max:20',
      'codigo_unico' => 'nullable|string|max:20',

      // Configuración
      'enviar_automaticamente_a_la_sunat' => 'nullable|boolean',
      'enviar_automaticamente_al_cliente' => 'nullable|boolean',
      'generado_por_contingencia' => 'nullable|boolean',

      // Items (si se envían, deben tener al menos 1 item con validaciones completas)
      'items' => 'nullable|array|min:1',
      'items.*.account_plan_id' => [
        'required_with:items',
        'integer',
        Rule::exists('ap_accounting_account_plan', 'id')
          ->whereNull('deleted_at')->where('status', 1),
      ],
      'items.*.unidad_de_medida' => 'required_with:items|string|max:3',
      'items.*.codigo' => 'nullable|string|max:30',
      'items.*.codigo_producto_sunat' => 'nullable|string|max:8',
      'items.*.descripcion' => 'required_with:items|string|max:250',
      'items.*.cantidad' => 'required_with:items|numeric|min:0.0000000001',
      'items.*.valor_unitario' => 'required_with:items|numeric|min:0',
      'items.*.precio_unitario' => 'required_with:items|numeric|min:0',
      'items.*.descuento' => 'nullable|numeric|min:0',
      'items.*.subtotal' => 'required_with:items|numeric|min:0',
      'items.*.sunat_concept_igv_type_id' => 'required_with:items|integer|exists:sunat_concepts,id',
      'items.*.igv' => 'required_with:items|numeric|min:0',
      'items.*.total' => 'required_with:items|numeric|min:0',
      'items.*.anticipo_regularizacion' => 'nullable|boolean',
      'items.*.anticipo_documento_serie' => 'nullable|string|size:4',
      'items.*.anticipo_documento_numero' => 'nullable|integer|min:1',

      // Guías (opcionales)
      'guias' => 'nullable|array',
      'guias.*.guia_tipo' => 'required_with:guias|integer|between:1,2',
      'guias.*.guia_serie_numero' => 'required_with:guias|string|max:20',

      // Cuotas para venta al crédito (opcionales)
      'venta_al_credito' => 'nullable|array',
      'venta_al_credito.*.cuota' => 'required_with:venta_al_credito|integer|min:1',
      'venta_al_credito.*.fecha_de_pago' => 'required_with:venta_al_credito|date',
      'venta_al_credito.*.importe' => 'required_with:venta_al_credito|numeric|min:0',
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'ap_billing_document_type_id.exists' => 'El tipo de documento seleccionado no es válido',
      'serie.size' => 'La serie debe tener exactamente 4 caracteres',
      'items.min' => 'Debe agregar al menos un item al documento',
      'items.*.descripcion.required_with' => 'La descripción del item es obligatoria',
      'items.*.cantidad.required_with' => 'La cantidad del item es obligatoria',
      'items.*.cantidad.min' => 'La cantidad debe ser mayor a 0',
      'document_accepted' => 'No se puede editar un documento que ya fue aceptado por SUNAT',
    ];
  }

  /**
   * Configure the validator instance.
   */
  public function withValidator($validator)
  {
    $validator->after(function ($validator) {
      // Obtener el documento que se está actualizando
      $documentId = $this->route('id');
      $document = ElectronicDocument::find($documentId);

      // Validar que el documento no haya sido aceptado por SUNAT
      if ($document && $document->status === ElectronicDocument::STATUS_ACCEPTED && $document->aceptada_por_sunat) {
        $validator->errors()->add(
          'status',
          'No se puede editar un documento que ya fue aceptado por SUNAT'
        );
      }

      // Validar que la serie corresponda al tipo de documento (solo si ambos están presentes)
      if ($this->has('ap_billing_document_type_id') && $this->has('serie')) {
        $documentTypeId = $this->input('ap_billing_document_type_id');
        $serie = $this->input('serie');
        $prefix = substr($serie, 0, 1);

        $validations = [
          1 => 'F', // Factura
          2 => 'B', // Boleta
          3 => ['F', 'B'], // Nota de Crédito
          4 => ['F', 'B'], // Nota de Débito
        ];

        if (isset($validations[$documentTypeId])) {
          $validPrefixes = (array)$validations[$documentTypeId];
          if (!in_array($prefix, $validPrefixes)) {
            $validator->errors()->add(
              'serie',
              'La serie no corresponde al tipo de documento seleccionado'
            );
          }
        }
      }

      // Validar que si es nota de crédito/débito, tenga el documento que modifica
      // Solo validar si se está actualizando el tipo de documento a nota de crédito/débito
      $documentTypeId = $this->input('ap_billing_document_type_id');
      if ($documentTypeId && in_array($documentTypeId, [3, 4])) {
        // Si se está cambiando a NC o ND, verificar que tenga los datos del documento que modifica
        $hasModifiedDoc = $this->has('documento_que_se_modifica_tipo') &&
          $this->has('documento_que_se_modifica_serie') &&
          $this->has('documento_que_se_modifica_numero');

        // O que el documento existente ya los tenga
        $docHasModifiedDoc = $document &&
          $document->documento_que_se_modifica_tipo &&
          $document->documento_que_se_modifica_serie &&
          $document->documento_que_se_modifica_numero;

        if (!$hasModifiedDoc && !$docHasModifiedDoc) {
          $validator->errors()->add(
            'documento_que_se_modifica_tipo',
            'Debe especificar el documento que se modifica'
          );
        }

        // Validar tipo de nota
        if ($documentTypeId == 3) {
          $hasNoteType = $this->has('ap_billing_credit_note_type_id');
          $docHasNoteType = $document && $document->ap_billing_credit_note_type_id;

          if (!$hasNoteType && !$docHasNoteType) {
            $validator->errors()->add(
              'ap_billing_credit_note_type_id',
              'Debe especificar el tipo de nota de crédito'
            );
          }
        }

        if ($documentTypeId == 4) {
          $hasNoteType = $this->has('ap_billing_debit_note_type_id');
          $docHasNoteType = $document && $document->ap_billing_debit_note_type_id;

          if (!$hasNoteType && !$docHasNoteType) {
            $validator->errors()->add(
              'ap_billing_debit_note_type_id',
              'Debe especificar el tipo de nota de débito'
            );
          }
        }
      }

      // Validar que si hay detracción, tenga todos los campos necesarios
      if ($this->input('detraccion') === true) {
        $hasDetractionType = $this->has('ap_billing_detraction_type_id');
        $docHasDetractionType = $document && $document->ap_billing_detraction_type_id;

        if (!$hasDetractionType && !$docHasDetractionType) {
          $validator->errors()->add(
            'ap_billing_detraction_type_id',
            'Debe especificar el tipo de detracción'
          );
        }
      }
    });
  }
}
