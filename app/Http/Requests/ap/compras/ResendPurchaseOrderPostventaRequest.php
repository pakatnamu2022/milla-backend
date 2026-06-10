<?php

namespace App\Http\Requests\ap\compras;

use App\Models\ap\ApMasters;
use Illuminate\Support\Facades\DB;

class ResendPurchaseOrderPostventaRequest extends StorePurchaseOrderRequest
{
  public function prepareForValidation(): void
  {
    if (!$this->has('type_operation_id')) {
      $this->merge([
        'type_operation_id' => ApMasters::TIPO_OPERACION_POSTVENTA,
      ]);
    }
    $this->merge([
      'created_by' => auth()->id(),
    ]);
  }

  public function rules(): array
  {
    // Obtener las reglas base del request padre
    $rules = parent::rules();

    // El purchase_reception_id es requerido para este request
    $rules['purchase_reception_id'] = ['required', 'integer', 'exists:purchase_receptions,id'];

    return $rules;
  }

  public function withValidator($validator)
  {
    $validator->after(function ($validator) {
      // Validar que la recepción existe y está activa
      if ($this->has('purchase_reception_id')) {
        $reception = DB::table('purchase_receptions')
          ->where('id', $this->purchase_reception_id)
          ->whereNull('deleted_at')
          ->first();

        if (!$reception) {
          $validator->errors()->add('purchase_reception_id', 'La recepción no existe o ha sido eliminada.');
          return;
        }

        if ($reception->status === 'ANNULLED') {
          $validator->errors()->add('purchase_reception_id', 'No se puede reenviar una recepción anulada.');
          return;
        }
      }

      // Validar unicidad de factura (serie + número + proveedor) excluyendo las que tienen asterisco
      // porque el asterisco se agrega automáticamente
      if ($this->has('invoice_series') && $this->has('invoice_number') && $this->has('supplier_id')) {
        $invoiceNumberWithAsterisk = $this->invoice_number . '*';

        $exists = DB::table('ap_purchase_order')
          ->where('invoice_series', $this->invoice_series)
          ->where('invoice_number', $invoiceNumberWithAsterisk)
          ->where('supplier_id', $this->supplier_id)
          ->whereNull('deleted_at')
          ->exists();

        if ($exists) {
          $validator->errors()->add('invoice_number', 'La factura con asterisco (*) ya existe para este proveedor.');
        }
      }
    });
  }

  public function messages()
  {
    return array_merge(parent::messages(), [
      'purchase_reception_id.required' => 'El ID de la recepción es requerido.',
      'purchase_reception_id.exists' => 'La recepción especificada no existe.',
    ]);
  }
}