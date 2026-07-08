<?php

namespace App\Http\Requests\ap\facturacion;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class RegularizeAdvancePaymentRequest extends StoreRequest
{
  /**
   * Prepare the data for validation.
   */
  protected function prepareForValidation()
  {
    // Convertir strings numéricos a integers para campos ID
    $numericFields = [
      'sunat_concept_document_type_id',
      'series_id',
      'area_id',
      'client_id',
      'order_quotation_id',
      'work_order_id',
      'sunat_concept_currency_id',
      'bank_id',
    ];

    $dataToMerge = [];
    foreach ($numericFields as $field) {
      if ($this->has($field) && $this->input($field) !== null && $this->input($field) !== '') {
        $dataToMerge[$field] = (int)$this->input($field);
      }
    }

    // Convertir strings numéricos a decimales para totales
    $decimalFields = [
      'total_gravada',
      'total_inafecta',
      'total_exonerada',
      'total_igv',
      'total',
    ];

    foreach ($decimalFields as $field) {
      if ($this->has($field) && $this->input($field) !== null && $this->input($field) !== '') {
        $dataToMerge[$field] = abs((float)$this->input($field));
      }
    }

    if (!empty($dataToMerge)) {
      $this->merge($dataToMerge);
    }
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      // Datos del documento
      'sunat_concept_document_type_id' => ['required', 'integer', 'exists:gp_sunat_concepts,id'],
      'series_id' => ['required', 'integer', 'exists:ap_assign_sales_series,id'],
      'area_id' => ['required', 'integer', 'exists:ap_masters,id'],

      // Origen (opcional)
      'origin_entity_type' => ['nullable', 'string', Rule::in(['ApOrderQuotations', 'ApWorkOrder'])],
      'origin_entity_id' => ['nullable', 'integer'],
      'order_quotation_id' => ['nullable', 'integer', 'exists:ap_order_quotations,id'],
      'work_order_id' => ['nullable', 'integer', 'exists:ap_work_order,id'],

      // Cliente
      'client_id' => ['required', 'integer', 'exists:business_partners,id'],

      // Fechas
      'fecha_de_emision' => ['required', 'date'],
      'fecha_de_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha_de_emision'],

      // Moneda y tipo de cambio
      'sunat_concept_currency_id' => ['required', 'integer', 'exists:gp_sunat_concepts,id'],

      // Totales
      'total_gravada' => ['required', 'numeric', 'min:0'],
      'total_inafecta' => ['nullable', 'numeric', 'min:0'],
      'total_exonerada' => ['nullable', 'numeric', 'min:0'],
      'total_igv' => ['required', 'numeric', 'min:0'],
      'total' => ['required', 'numeric', 'gt:0'], // Debe ser mayor a 0

      // Items
      'items' => ['required', 'array', 'min:1'],
      'items.*.account_plan_id' => ['required', 'integer', 'exists:ap_accounting_account_plan,id'],
      'items.*.unidad_de_medida' => ['required', 'string'],
      'items.*.codigo' => ['required', 'string'],
      'items.*.descripcion' => ['required', 'string'],
      'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
      'items.*.valor_unitario' => ['required', 'numeric'],
      'items.*.precio_unitario' => ['required', 'numeric'],
      'items.*.descuento' => ['nullable', 'numeric', 'min:0'],
      'items.*.subtotal' => ['required', 'numeric'],
      'items.*.sunat_concept_igv_type_id' => ['required', 'integer', 'exists:gp_sunat_concepts,id'],
      'items.*.igv' => ['required', 'numeric'],
      'items.*.total' => ['required', 'numeric'],

      // Campos opcionales
      'observaciones' => ['nullable', 'string'],
      'condiciones_de_pago' => ['nullable', 'string'],
      'medio_de_pago' => ['nullable', 'string'],
      'bank_id' => ['nullable', 'integer', 'exists:gp_masters,id'],
      'operation_number' => ['nullable', 'string'],
      'orden_compra_servicio' => ['nullable', 'string'],
    ];
  }

  /**
   * Get custom messages for validator errors.
   *
   * @return array<string, string>
   */
  public function messages(): array
  {
    return [
      'sunat_concept_document_type_id.required' => 'El tipo de documento es obligatorio',
      'series_id.required' => 'La serie es obligatoria',
      'area_id.required' => 'El área es obligatoria',
      'client_id.required' => 'El cliente es obligatorio',
      'fecha_de_emision.required' => 'La fecha de emisión es obligatoria',
      'sunat_concept_currency_id.required' => 'La moneda es obligatoria',
      'total.gt' => 'El total del anticipo debe ser mayor a 0',
      'items.required' => 'Debe incluir al menos un item',
      'items.min' => 'Debe incluir al menos un item',
    ];
  }
}