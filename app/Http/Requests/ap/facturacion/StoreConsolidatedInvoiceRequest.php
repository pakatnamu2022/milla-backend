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
      'area_id' => 'required|integer|exists:ap_masters,id',
      'client_id' => 'nullable|integer|exists:business_partners,id',
      'is_advance_payment' => 'nullable|boolean',
      'sunat_concept_document_type_id' => 'required|integer|exists:sunat_concepts,id',
      'sunat_concept_transaction_type_id' => 'required|integer|exists:sunat_concepts,id',
      'serie' => 'required|string|max:10',
      'numero' => 'nullable|string|max:20',
      'sunat_concept_currency_id' => 'required|integer|exists:sunat_concepts,id',
      'fecha_de_emision' => 'required|date',
      'fecha_de_vencimiento' => 'nullable|date|after_or_equal:fecha_de_emision',
      'observaciones' => 'nullable|string|max:1000',
      'internal_note' => 'nullable|string|max:1000',
      'orden_compra_servicio' => 'nullable|string|max:20',
      'orden_compra_servicio_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',

      // Campos de pago
      'medio_de_pago' => 'nullable|string|max:50',
      'condiciones_de_pago' => 'nullable|string|max:50',
      'credit_days' => 'nullable|integer|min:0',

      // Totales
      'total_anticipo' => 'nullable|numeric|min:0',
      'total_gravada' => 'nullable|numeric|min:0',
      'total_inafecta' => 'nullable|numeric|min:0',
      'total_exonerada' => 'nullable|numeric|min:0',
      'total_igv' => 'nullable|numeric|min:0',
      'total_gratuita' => 'nullable|numeric|min:0',
      'total' => 'required|numeric|min:0',

      // Opciones de envío
      'enviar_automaticamente_a_la_sunat' => 'nullable|boolean',
      'enviar_automaticamente_al_cliente' => 'nullable|boolean',

      // Items
      'items' => 'required|array|min:1',
      'items.*.unidad_de_medida' => 'required|string|max:10',
      'items.*.codigo' => 'required|string|max:255',
      'items.*.descripcion' => 'required|string',
      'items.*.cantidad' => 'required|numeric|min:0.01',
      'items.*.valor_unitario' => 'required|numeric|min:0',
      'items.*.precio_unitario' => 'required|numeric|min:0',
      'items.*.subtotal' => 'required|numeric|min:0',
      'items.*.sunat_concept_igv_type_id' => 'required|integer|exists:sunat_concepts,id',
      'items.*.igv' => 'required|numeric|min:0',
      'items.*.total' => 'required|numeric|min:0',
      'items.*.account_plan_id' => 'nullable|integer|exists:ap_accounting_account_plan,id',

      // Cuotas para venta al crédito (opcionales)
      'venta_al_credito' => 'nullable|array',
      'venta_al_credito.*.cuota' => 'required|integer|min:1',
      'venta_al_credito.*.fecha_de_pago' => 'required|date',
      'venta_al_credito.*.importe' => 'required|numeric|min:0.01',
    ];
  }

  /**
   * Prepare the data for validation.
   */
  protected function prepareForValidation(): void
  {
    $dataToMerge = [];

    // Convertir strings numéricos a integers
    $numericFields = [
      'sunat_concept_document_type_id',
      'sunat_concept_transaction_type_id',
      'area_id',
      'client_id',
      'sunat_concept_currency_id',
      'credit_days',
    ];

    foreach ($numericFields as $field) {
      if ($this->has($field) && $this->input($field) !== null && $this->input($field) !== '') {
        $dataToMerge[$field] = (int)$this->input($field);
      }
    }

    // Convertir strings numéricos a decimales para totales
    $decimalFields = [
      'total_anticipo',
      'total_gravada',
      'total_inafecta',
      'total_exonerada',
      'total_igv',
      'total_gratuita',
      'total',
    ];

    foreach ($decimalFields as $field) {
      if ($this->has($field) && $this->input($field) !== null && $this->input($field) !== '') {
        $dataToMerge[$field] = abs((float)$this->input($field));
      }
    }

    // Convertir strings booleanos
    $booleanFields = [
      'is_advance_payment',
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
        if (isset($item['account_plan_id'])) {
          $items[$index]['account_plan_id'] = (int)$item['account_plan_id'];
        }

        $numericItemFields = ['cantidad', 'valor_unitario', 'precio_unitario', 'descuento', 'subtotal', 'igv', 'total'];
        foreach ($numericItemFields as $field) {
          if (isset($item[$field])) {
            $items[$index][$field] = abs((float)$item[$field]);
          }
        }
      }
      $dataToMerge['items'] = $items;
    }

    // Convertir cuotas
    if ($this->has('venta_al_credito') && is_array($this->input('venta_al_credito'))) {
      $cuotas = $this->input('venta_al_credito');
      foreach ($cuotas as $index => $cuota) {
        if (isset($cuota['cuota'])) {
          $cuotas[$index]['cuota'] = (int)$cuota['cuota'];
        }
        if (isset($cuota['importe'])) {
          $cuotas[$index]['importe'] = abs((float)$cuota['importe']);
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
   * Configure the validator instance.
   *
   * @param \Illuminate\Validation\Validator $validator
   * @return void
   */
  public function withValidator($validator): void
  {
    $validator->after(function ($validator) {
      // Validar cuotas de venta al crédito
      if ($this->input('medio_de_pago') === 'credito') {
        $cuotas = $this->input('venta_al_credito', []);
        if (empty($cuotas)) {
          $validator->errors()->add(
            'venta_al_credito',
            'Debe especificar las cuotas para una venta al crédito'
          );
        } else {
          $totalCuotas = collect($cuotas)->sum(fn($c) => (float)($c['importe'] ?? 0));
          $totalDocumento = (float)$this->input('total', 0);
          if (round($totalCuotas, 2) !== round($totalDocumento, 2)) {
            $validator->errors()->add(
              'venta_al_credito',
              sprintf(
                'La suma de las cuotas (%.2f) debe ser igual al total del documento (%.2f)',
                $totalCuotas,
                $totalDocumento
              )
            );
          }
        }
      }
    });
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
      'items.required' => 'Debe incluir al menos un item',
      'items.array' => 'Los items deben ser un arreglo',
      'items.min' => 'Debe incluir al menos un item',
      'items.*.unidad_de_medida.required' => 'La unidad de medida es requerida',
      'items.*.codigo.required' => 'El código del item es requerido',
      'items.*.descripcion.required' => 'La descripción del item es requerida',
      'items.*.cantidad.required' => 'La cantidad es requerida',
      'items.*.cantidad.min' => 'La cantidad debe ser mayor a 0',
      'items.*.valor_unitario.required' => 'El valor unitario es requerido',
      'items.*.precio_unitario.required' => 'El precio unitario es requerido',
      'items.*.subtotal.required' => 'El subtotal es requerido',
      'items.*.sunat_concept_igv_type_id.required' => 'El tipo de IGV es requerido',
      'items.*.sunat_concept_igv_type_id.exists' => 'El tipo de IGV no existe',
      'items.*.igv.required' => 'El IGV es requerido',
      'items.*.total.required' => 'El total es requerido',
      'items.*.account_plan_id.exists' => 'El plan de cuenta no existe',
    ];
  }
}
