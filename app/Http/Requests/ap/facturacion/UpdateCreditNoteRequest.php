<?php

namespace App\Http\Requests\ap\facturacion;

use App\Http\Requests\StoreRequest;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Exception;
use Illuminate\Validation\Rule;

class UpdateCreditNoteRequest extends StoreRequest
{
  /**
   * Prepare the data for validation.
   * @throws Exception
   */
  protected function prepareForValidation()
  {
    // Convertir strings numéricos a integers para campos ID
    $numericFields = [
      'original_document_id',
      'series',
      'sunat_concept_credit_note_type_id',
    ];

    $dataToMerge = [
      'serie',
    ];

    foreach ($numericFields as $field) {
      if ($this->has($field) && $this->input($field) !== null && $this->input($field) !== '') {
        $dataToMerge[$field] = (int)$this->input($field);
      }
    }

    // Los totales se auto-calculan, no necesitamos convertir decimales aquí

    // Convertir strings booleanos (solo flags de envío)
    $booleanFields = [
      'enviar_automaticamente_a_la_sunat',
      'enviar_automaticamente_al_cliente',
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
        $numericItemFields = ['cantidad', 'valor_unitario', 'precio_unitario', 'descuento', 'subtotal', 'igv', 'total'];
        foreach ($numericItemFields as $field) {
          if (isset($item[$field])) {
            $items[$index][$field] = (float)$item[$field];
          }
        }
        // Convertir anticipo_regularizacion a boolean
        if (isset($item['anticipo_regularizacion'])) {
          $items[$index]['anticipo_regularizacion'] = filter_var($item['anticipo_regularizacion'], FILTER_VALIDATE_BOOLEAN);
        }
        // Convertir anticipo_documento_numero a integer
        if (isset($item['anticipo_documento_numero'])) {
          $items[$index]['anticipo_documento_numero'] = (int)$item['anticipo_documento_numero'];
        }
      }
      $dataToMerge['items'] = $items;
    }

    // Aplicar todas las conversiones
    if (!empty($dataToMerge)) {
      $this->merge($dataToMerge);
    }

    // Convertir el ID de series a la serie real (string)
    if ($this->has('series')) {
      $seriesId = (int)$this->input('series');
      $assignSeries = AssignSalesSeries::find($seriesId);

      if ($assignSeries) {
        $this->merge([
          'series_id' => $seriesId,
          'serie' => $assignSeries->series, // String "F001" o "B001"
        ]);
      } else {
        throw new Exception('La serie seleccionada no es válida');
      }
    }
  }

  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    $userId = $this->user()->id;
    return [
      // Documento original que se modifica (OBLIGATORIO)
      'original_document_id' => [
        'required',
        'integer',
        Rule::exists('ap_billing_electronic_documents', 'id')
          ->whereNull('deleted_at')
          ->where('aceptada_por_sunat', true) // Solo documentos aceptados por SUNAT
          ->where('anulado', false) // No anulados
      ],

      // Tipo de nota de crédito (OBLIGATORIO)
      'sunat_concept_credit_note_type_id' => [
        'required',
        'integer',
        Rule::exists('sunat_concepts', 'id')
          ->where('type', SunatConcepts::BILLING_CREDIT_NOTE_TYPE)
          ->whereNull('deleted_at')
          ->where('status', 1)
      ],

      // Serie para la nota de crédito
      'series_id' => [
        'required',
        'integer',
        Rule::exists('assign_sales_series', 'id')
          ->where('status', 1)
          ->whereNull('deleted_at'),
        Rule::exists('user_series_assignment', 'voucher_id')
          ->where('worker_id', $userId)
      ],
      'serie' => 'required|string|max:4',

      // Fechas
      'fecha_de_emision' => 'required|date',

      // Campos opcionales
      'observaciones' => 'nullable|string|max:1000',

      // Configuración
      'enviar_automaticamente_a_la_sunat' => 'nullable|boolean',
      'enviar_automaticamente_al_cliente' => 'nullable|boolean',

      // Items de la nota de crédito (OBLIGATORIOS)
      'items' => 'required|array|min:1',
      'items.*.reference_document_id' => [
        'nullable',
        'required_if:items.*.anticipo_regularizacion,true',
        'integer',
        Rule::exists('ap_billing_electronic_documents', 'id')
          ->whereNull('deleted_at')
          ->where('aceptada_por_sunat', true)
          ->where('anulado', false)
      ],
      'items.*.account_plan_id' => [
        'required',
        'integer',
        Rule::exists('ap_accounting_account_plan', 'id')
          ->whereNull('deleted_at')
          ->where('status', 1)
      ],
      'items.*.unidad_de_medida' => 'required|string|max:3',
      'items.*.codigo' => 'nullable|string|max:30',
      'items.*.codigo_producto_sunat' => 'nullable|string|max:8',
      'items.*.descripcion' => 'required|string',
      'items.*.cantidad' => 'required|numeric|min:0.0000000001',
      'items.*.valor_unitario' => 'required|numeric|min:0',
      'items.*.precio_unitario' => 'required|numeric|min:0',
      'items.*.descuento' => 'nullable|numeric|min:0',
      'items.*.subtotal' => 'required|numeric|min:0',
      'items.*.sunat_concept_igv_type_id' => 'required|integer|exists:sunat_concepts,id',
      'items.*.igv' => 'required|numeric|min:0',
      'items.*.total' => 'required|numeric|min:0',
      'items.*.anticipo_regularizacion' => 'nullable|boolean',
      'items.*.anticipo_documento_serie' => 'nullable|string|size:4',
      'items.*.anticipo_documento_numero' => 'nullable|integer|min:1',
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'original_document_id.required' => 'El documento original es obligatorio',
      'original_document_id.exists' => 'El documento original no existe, no está aceptado por SUNAT o está anulado',
      'sunat_concept_credit_note_type_id.required' => 'El tipo de nota de crédito es obligatorio',
      'sunat_concept_credit_note_type_id.exists' => 'El tipo de nota de crédito seleccionado no es válido',
      'series.required' => 'La serie es obligatoria',
      'fecha_de_emision.required' => 'La fecha de emisión es obligatoria',
      'items.required' => 'Debe agregar al menos un item a la nota de crédito',
      'items.min' => 'Debe agregar al menos un item a la nota de crédito',
      'items.*.descripcion.required' => 'La descripción del item es obligatoria',
      'items.*.cantidad.required' => 'La cantidad del item es obligatoria',
      'items.*.cantidad.min' => 'La cantidad debe ser mayor a 0',
    ];
  }

  /**
   * Configure the validator instance.
   */
  public function withValidator($validator)
  {
    $validator->after(function ($validator) {
      // Obtener el documento original
      $originalDocumentId = $this->input('original_document_id');
      if (!$originalDocumentId) {
        return;
      }

      $originalDocument = ElectronicDocument::with(['items'])->find($originalDocumentId);
      if (!$originalDocument) {
        return;
      }

      // VALIDACIÓN 2: Verificar que los items de la nota de crédito sean válidos respecto al documento original
      if ($this->has('items') && is_array($this->input('items'))) {
        $creditNoteItems = $this->input('items');
        $originalItems = $originalDocument->items->keyBy('id')->toArray();

        // Crear un mapa de items originales por código/descripción para validación flexible
        $originalItemsMap = [];
        foreach ($originalItems as $origItem) {
          $key = $origItem['codigo'] ?? $origItem['descripcion'];
          if (!isset($originalItemsMap[$key])) {
            $originalItemsMap[$key] = [
              'cantidad' => 0,
              'total' => 0
            ];
          }
          $originalItemsMap[$key]['cantidad'] += $origItem['cantidad'];
          $originalItemsMap[$key]['total'] += $origItem['total'];
        }

        // Obtener items ya acreditados
        $previousCreditNotes = ElectronicDocument::with('items')
          ->where('documento_que_se_modifica_tipo', $originalDocument->sunat_concept_document_type_id)
          ->where('documento_que_se_modifica_serie', $originalDocument->serie)
          ->where('documento_que_se_modifica_numero', $originalDocument->numero)
          ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_CREDITO)
          ->where('aceptada_por_sunat', true)
          ->where('anulado', false)
          ->whereNull('deleted_at')
          ->get();

        $creditedItemsMap = [];
        foreach ($previousCreditNotes as $creditNote) {
          foreach ($creditNote->items as $item) {
            $key = $item->codigo ?? $item->descripcion;
            if (!isset($creditedItemsMap[$key])) {
              $creditedItemsMap[$key] = [
                'cantidad' => 0,
                'total' => 0
              ];
            }
            $creditedItemsMap[$key]['cantidad'] += $item->cantidad;
            $creditedItemsMap[$key]['total'] += $item->total;
          }
        }

        // Validar cada item de la nota de crédito
        foreach ($creditNoteItems as $index => $item) {
          $itemKey = $item['codigo'] ?? $item['descripcion'];

          // Verificar que el item exista en el documento original
          if (!isset($originalItemsMap[$itemKey])) {
            $validator->errors()->add(
              "items.{$index}.descripcion",
              "El item '{$itemKey}' no existe en el documento original"
            );
            continue;
          }

          $originalItemData = $originalItemsMap[$itemKey];
          $creditedItemData = $creditedItemsMap[$itemKey] ?? ['cantidad' => 0, 'total' => 0];

          // Validar cantidad
          $availableQuantity = $originalItemData['cantidad'] - $creditedItemData['cantidad'];
          if ($item['cantidad'] > $availableQuantity) {
            $validator->errors()->add(
              "items.{$index}.cantidad",
              sprintf(
                "La cantidad del item (%.4f) excede la cantidad disponible para acreditar (%.4f). Original: %.4f, Ya acreditado: %.4f",
                $item['cantidad'],
                $availableQuantity,
                $originalItemData['cantidad'],
                $creditedItemData['cantidad']
              )
            );
          }

          // Validar total del item
          $availableTotal = $originalItemData['total'] - $creditedItemData['total'];
          if ($item['total'] > $availableTotal + 0.01) { // Tolerancia de 1 centavo por redondeos
            $validator->errors()->add(
              "items.{$index}.total",
              sprintf(
                "El total del item (%.2f) excede el total disponible para acreditar (%.2f). Original: %.2f, Ya acreditado: %.2f",
                $item['total'],
                $availableTotal,
                $originalItemData['total'],
                $creditedItemData['total']
              )
            );
          }
        }
      }

      // VALIDACIÓN 3: Verificar que el documento original no haya sido regularizado por anticipo
      if ($originalDocument->sunat_concept_transaction_type_id == 36) { // Tipo operación: Anticipos
        // Buscar si existe una factura que regulariza este anticipo
        $hasRegularization = ElectronicDocument::whereHas('items', function ($query) use ($originalDocument) {
          $query->where('anticipo_regularizacion', true)
            ->where('anticipo_documento_serie', $originalDocument->serie)
            ->where('anticipo_documento_numero', $originalDocument->numero);
        })
          ->where('aceptada_por_sunat', true)
          ->where('anulado', false)
          ->whereNull('deleted_at')
          ->exists();

        if ($hasRegularization) {
          $validator->errors()->add(
            'original_document_id',
            'No se puede crear una nota de crédito para un anticipo que ya ha sido regularizado. Debe crear la nota de crédito sobre la factura de regularización.'
          );
        }
      }
    });
  }
}
