<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use Illuminate\Contracts\Validation\Validator;

class StoreApOrderPurchaseRequestsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'ap_order_quotation_id' => [
        'nullable',
        'integer',
        'exists:ap_order_quotations,id',
      ],
      'purchase_order_id' => [
        'nullable',
        'integer',
        'exists:ap_purchase_order,id',
      ],
      'warehouse_id' => [
        'required',
        'integer',
        'exists:warehouse,id',
      ],
      'requested_date' => [
        'required',
        'date',
      ],
      'observations' => [
        'nullable',
        'string',
      ],
      'status' => [
        'sometimes',
        'required',
        'in:pending,approved,rejected',
      ],
      'supply_type' => [
        'required',
        'string',
        'in:STOCK,LIMA,IMPORTACION',
      ],

      // Details validation
      'details' => [
        'required',
        'array',
        'min:1',
      ],
      'details.*.product_id' => [
        'required',
        'integer',
        'exists:products,id',
      ],
      'details.*.quantity' => [
        'required',
        'numeric',
        'min:0.01',
      ],
      'details.*.notes' => [
        'nullable',
        'string',
      ],
      'details.*.requested_delivery_date' => [
        'nullable',
        'date',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'ap_order_quotation_id.integer' => 'La cotización debe ser un entero.',
      'ap_order_quotation_id.exists' => 'La cotización seleccionada no es válida.',

      'purchase_order_id.integer' => 'La orden de compra debe ser un entero.',
      'purchase_order_id.exists' => 'La orden de compra seleccionada no es válida.',

      'warehouse_id.required' => 'El almacén es obligatorio.',
      'warehouse_id.integer' => 'El almacén debe ser un entero.',
      'warehouse_id.exists' => 'El almacén seleccionado no es válido.',

      'requested_date.required' => 'La fecha de solicitud es obligatoria.',
      'requested_date.date' => 'La fecha de solicitud debe ser una fecha válida.',

      'observations.string' => 'Las observaciones deben ser una cadena de texto.',

      'status.required' => 'El estado es obligatorio.',
      'status.in' => 'El estado debe ser: pending, approved o rejected.',
      'supply_type' => 'El tipo de suministro es obligatorio.',
      'supply_type.in' => 'El tipo de suministro debe ser: STOCK, LIMA o IMPORTACION.',

      // Details messages
      'details.required' => 'Los detalles de la solicitud son obligatorios.',
      'details.array' => 'Los detalles deben ser un arreglo.',
      'details.min' => 'Debe incluir al menos un detalle en la solicitud.',

      'details.*.product_id.required' => 'El producto es obligatorio en cada detalle.',
      'details.*.product_id.integer' => 'El producto debe ser un entero.',
      'details.*.product_id.exists' => 'El producto seleccionado no es válido.',

      'details.*.quantity.required' => 'La cantidad es obligatoria en cada detalle.',
      'details.*.quantity.numeric' => 'La cantidad debe ser un número.',
      'details.*.quantity.min' => 'La cantidad debe ser mayor a 0.',

      'details.*.notes.string' => 'Las notas deben ser una cadena de texto.',

      'details.*.requested_delivery_date.date' => 'La fecha de entrega solicitada debe ser una fecha válida.',
    ];
  }

  protected function withValidator(Validator $validator): void
  {
    $validator->after(function ($validator) {
      $details = $this->input('details', []);

      if (empty($details)) {
        return;
      }

      $productIds = collect($details)->pluck('product_id')->filter();
      $duplicates = $productIds->duplicates()->values();

      if ($duplicates->isNotEmpty()) {
        $validator->errors()->add(
          'details',
          'Se han detectado productos duplicados. Los productos con ID: ' . $duplicates->implode(', ') . ' deben ser consolidados en un solo item.'
        );
      }

      // Validar stock según supply_type
      $supplyType = $this->input('supply_type');

      if ($supplyType === 'STOCK') {
        // Validar que los productos tengan stock disponible en cualquier sede
        foreach ($details as $index => $detail) {
          $productId = $detail['product_id'] ?? null;

          if (!$productId) {
            continue;
          }

          $totalStock = ProductWarehouseStock::where('product_id', $productId)
            ->sum('quantity');

          if ($totalStock <= 0) {
            $validator->errors()->add(
              "details.{$index}.product_id",
              "El producto seleccionado no tiene stock disponible en ninguna sede. Para tipo de suministro STOCK, el producto debe tener stock disponible."
            );
          }
        }
      } elseif (in_array($supplyType, ['LIMA', 'IMPORTACION'])) {
        // Validar que los productos NO tengan stock (debe ser 0)
        foreach ($details as $index => $detail) {
          $productId = $detail['product_id'] ?? null;

          if (!$productId) {
            continue;
          }

          $totalStock = ProductWarehouseStock::where('product_id', $productId)
            ->sum('quantity');

          if ($totalStock > 0) {
            $validator->errors()->add(
              "details.{$index}.product_id",
              "El producto seleccionado tiene stock disponible ({$totalStock} unidades). Para tipo de suministro {$supplyType}, el producto no debe tener stock en ninguna sede."
            );
          }
        }
      }
    });
  }
}
