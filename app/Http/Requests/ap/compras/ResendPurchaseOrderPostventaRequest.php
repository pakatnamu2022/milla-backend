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

    // Remover ap_supplier_order_id de required porque se obtiene desde la ruta
    if (isset($rules['ap_supplier_order_id'])) {
      unset($rules['ap_supplier_order_id']);
    }

    return $rules;
  }

  public function withValidator($validator)
  {
    $validator->after(function ($validator) {
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
}