<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use Illuminate\Validation\Rule;

class StoreApOrderQuotationDetailsRequest extends StoreRequest
{
  protected function prepareForValidation()
  {
    if (empty($this->supply_type)) {
      $this->merge([
        'supply_type' => 'M.O',
      ]);
    }
  }

  public function rules(): array
  {
    return [
      'order_quotation_id' => [
        'required',
        'integer',
        'exists:ap_order_quotations,id',
      ],
      'item_type' => [
        'required',
        'in:PRODUCT,LABOR',
      ],
      'product_id' => [
        'nullable',
        'required_if:item_type,PRODUCT',
        'integer',
        'exists:products,id',
      ],
      'description' => [
        'required',
        'string',
        'max:255',
        Rule::unique('ap_order_quotation_details', 'description')
          ->where('order_quotation_id', $this->input('order_quotation_id'))
          ->whereNull('deleted_at'),
      ],
      'quantity' => [
        'required',
        'numeric',
        'min:0.01',
      ],
      'unit_measure' => [
        'required',
        'string',
        'max:50',
      ],
      'unit_price' => [
        'required',
        'numeric',
        'min:0.1',
      ],
      'discount_percentage' => [
        'nullable',
        'numeric',
        'min:0',
        'max:100',
      ],
      'total_amount' => [
        'required',
        'numeric',
        'min:0',
      ],
      'observations' => [
        'nullable',
        'string',
      ],
      'retail_price_external' => [
        'nullable',
        'numeric',
        'min:0.1',
      ],
      'exchange_rate' => [
        'nullable',
        'numeric',
        'min:0',
      ],
      'freight_commission' => [
        'nullable',
        'numeric',
        'min:0',
        'max:100',
      ],
      'supply_type' => [
        'nullable',
        'in:STOCK,TRASLADO,LOCAL,CENTRAL,IMPORTACION,M.O',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'order_quotation_id.required' => 'La cotización de orden es obligatoria.',
      'order_quotation_id.integer' => 'La cotización de orden debe ser un entero.',
      'order_quotation_id.exists' => 'La cotización de orden seleccionada no es válida.',

      'item_type.required' => 'El tipo de ítem es obligatorio.',
      'item_type.in' => 'El tipo de ítem debe ser PRODUCT o LABOR.',

      'product_id.required_if' => 'El producto es obligatorio cuando el tipo es PRODUCT.',
      'product_id.integer' => 'El producto debe ser un entero.',
      'product_id.exists' => 'El producto seleccionado no es válido.',

      'description.unique' => 'La descripción ya existe en esta cotización. Por favor, ingrese una descripción diferente.',
      'description.required' => 'La descripción es obligatoria.',
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no puede exceder 255 caracteres.',

      'quantity.required' => 'La cantidad es obligatoria.',
      'quantity.numeric' => 'La cantidad debe ser un número.',
      'quantity.min' => 'La cantidad debe ser mayor a 0.',

      'unit_measure.required' => 'La unidad de medida es obligatoria.',
      'unit_measure.string' => 'La unidad de medida debe ser una cadena de texto.',
      'unit_measure.max' => 'La unidad de medida no puede exceder 50 caracteres.',

      'unit_price.required' => 'El precio unitario es obligatorio.',
      'unit_price.numeric' => 'El precio unitario debe ser un número.',
      'unit_price.min' => 'El precio unitario no puede ser cero o negativo.',

      'discount_percentage.numeric' => 'El porcentaje de descuento debe ser un número.',
      'discount_percentage.min' => 'El porcentaje de descuento no puede ser negativo.',
      'discount_percentage.max' => 'El porcentaje de descuento no puede ser mayor a 100.',

      'total_amount.required' => 'El monto total es obligatorio.',
      'total_amount.numeric' => 'El monto total debe ser un número.',
      'total_amount.min' => 'El monto total no puede ser negativo.',

      'supply_type.in' => 'El tipo de suministro debe ser LOCAL, CENTRAL, IMPORTACION o M.O.',

      'observations.string' => 'Las observaciones deben ser una cadena de texto.',

      'retail_price_external.numeric' => 'El precio minorista externo debe ser un número.',
      'retail_price_external.min' => 'El precio no puede ser diferente de 0 y negativo.',
    ];
  }

  public function withValidator($validator): void
  {
    $validator->after(function ($validator) {
      // Solo validar stock si es un producto (no mano de obra)
      if ($this->input('item_type') !== 'PRODUCT') {
        return;
      }

      $productId = $this->input('product_id');
      $supplyType = $this->input('supply_type');
      $quantity = $this->input('quantity', 0);

      // Si no hay producto o supply_type, no validar
      if (!$productId || !$supplyType) {
        return;
      }

      // No validar stock para M.O (Mano de Obra)
      if ($supplyType === 'M.O') {
        return;
      }

      // Obtener la cotización para obtener el sede_id
      $orderQuotationId = $this->input('order_quotation_id');
      $quotation = ApOrderQuotations::find($orderQuotationId);

      if (!$quotation || !$quotation->sede_id) {
        return;
      }

      $sedeId = $quotation->sede_id;
      $warehouseId = Warehouse::getPhysicalWarehouseForPostsale($sedeId)?->id;

      if (!$warehouseId) {
        $validator->errors()->add(
          'order_quotation_id',
          'No se encontró un almacén físico asociado a esta sede para postventa. No se puede validar el stock de los productos.'
        );
        return;
      }

      // Stock en la sede actual
      $stockInCurrentSede = ProductWarehouseStock::where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->sum('available_quantity');

      // Stock en otras sedes
      $stockInOtherSedes = ProductWarehouseStock::where('product_id', $productId)
        ->where('warehouse_id', '!=', $warehouseId)
        ->sum('available_quantity');

      // Validación para STOCK: solo si hay suficiente stock en la sede actual
      if ($supplyType === 'STOCK') {
        if ($stockInCurrentSede < $quantity) {
          $validator->errors()->add(
            'supply_type',
            "No puede usar tipo STOCK. El producto solo tiene {$stockInCurrentSede} unidades disponibles en esta sede pero solicita {$quantity}. Debe usar TRASLADO, LOCAL, CENTRAL o IMPORTACION."
          );
        }
      }

      // Validación para TRASLADO: solo si NO hay suficiente en sede actual PERO sí en otras sedes
      if ($supplyType === 'TRASLADO') {
        if ($stockInCurrentSede >= $quantity) {
          $validator->errors()->add(
            'supply_type',
            "No puede usar tipo TRASLADO porque dispone de {$stockInCurrentSede} unidades en stock de su sede, lo cual es suficiente para las {$quantity} solicitadas. Por favor, use tipo STOCK en su lugar."
          );
        } elseif ($stockInCurrentSede > 0 && $stockInCurrentSede < $quantity) {
          $quantityNeeded = $quantity - $stockInCurrentSede;
          $validator->errors()->add(
            'supply_type',
            "No puede solicitar {$quantity} unidades con TRASLADO porque tiene {$stockInCurrentSede} unidades disponibles en stock de su sede. Por favor, genere dos cotizaciones separadas: una con {$stockInCurrentSede} unidades usando tipo STOCK y otra con {$quantityNeeded} unidades usando tipo TRASLADO."
          );
        } elseif ($stockInOtherSedes <= 0) {
          $validator->errors()->add(
            'supply_type',
            'No puede usar tipo TRASLADO porque no hay stock disponible en otras sedes. Debe usar LOCAL, CENTRAL o IMPORTACION.'
          );
        } elseif ($stockInOtherSedes < $quantity) {
          $validator->errors()->add(
            'supply_type',
            "No puede usar tipo TRASLADO para {$quantity} unidades porque solo hay {$stockInOtherSedes} unidades disponibles en otras sedes. Debe usar LOCAL, CENTRAL o IMPORTACION para las unidades faltantes."
          );
        }
      }

      // Validación para LOCAL, CENTRAL, IMPORTACION: solo si la cantidad excede el stock de la sede actual
      if (in_array($supplyType, ['LOCAL', 'CENTRAL', 'IMPORTACION'])) {
        if ($stockInCurrentSede >= $quantity) {
          $validator->errors()->add(
            'supply_type',
            "No puede usar tipo {$supplyType}. El producto tiene {$stockInCurrentSede} unidades en esta sede, suficientes para las {$quantity} solicitadas. Debe usar tipo STOCK."
          );
        }
      }
    });
  }
}
