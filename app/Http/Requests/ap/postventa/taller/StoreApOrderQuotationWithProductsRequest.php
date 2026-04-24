<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;

class StoreApOrderQuotationWithProductsRequest extends StoreRequest
{
  protected function prepareForValidation(): void
  {
    if ($this->has('vehicle_id') && $this->vehicle_id === '') {
      $this->merge(['vehicle_id' => null]);
    }
  }

  public function rules(): array
  {
    return [
      // Quotation fields
      'currency_id' => ['required', 'integer', 'exists:type_currency,id'],
      'area_id' => ['required', 'integer', 'exists:ap_masters,id'],
      'client_id' => ['required', 'integer', 'exists:business_partners,id'],
      'vehicle_id' => ['nullable', 'integer', 'exists:ap_vehicles,id'],
      'sede_id' => ['required', 'integer', 'exists:config_sede,id'],
      'quotation_date' => ['required', 'date'],
      'expiration_date' => ['nullable', 'date', 'after_or_equal:quotation_date'],
      'collection_date' => ['nullable', 'date'],
      'observations' => ['nullable', 'string'],

      // Details array
      'details' => ['required', 'array', 'min:1'],
      'details.*.product_id' => [
        'required',
        'integer',
        'exists:products,id',
      ],
      'details.*.description' => [
        'required',
        'string',
        'max:255',
      ],
      'details.*.quantity' => [
        'required',
        'numeric',
        'min:0.01',
      ],
      'details.*.unit_measure' => [
        'required',
        'string',
        'max:50',
      ],
      'details.*.unit_price' => [
        'required',
        'numeric',
        'min:0',
      ],
      'details.*.discount_percentage' => [
        'nullable',
        'numeric',
        'min:0',
        'max:100',
      ],
      'details.*.total_amount' => [
        'required',
        'numeric',
        'min:1',
      ],
      'details.*.observations' => [
        'nullable',
        'string',
      ],
      'details.*.retail_price_external' => [
        'nullable',
        'numeric',
        'min:0',
      ],
      'details.*.exchange_rate' => [
        'nullable',
        'numeric',
        'min:0',
      ],
      'details.*.freight_commission' => [
        'nullable',
        'numeric',
        'min:0',
        'max:100',
      ],
      'details.*.supply_type' => [
        'required',
        'string',
        'in:STOCK,TRASLADO,LOCAL,CENTRAL,IMPORTACION',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      // Quotation messages
      'currency_id.required' => 'Moneda es obligatoria.',
      'currency_id.exists' => 'La moneda no existe.',
      'area_id.required' => 'Área de postventa es obligatoria.',
      'area_id.exists' => 'El área de postventa no existe.',
      'client_id.required' => 'El cliente es obligatorio.',
      'client_id.exists' => 'El cliente no existe.',
      'vehicle_id.exists' => 'El vehículo no existe.',
      'sede_id.required' => 'La sede es obligatoria.',
      'sede_id.exists' => 'La sede no existe.',
      'quotation_date.required' => 'La fecha de cotización es obligatoria.',
      'expiration_date.after_or_equal' => 'La fecha de expiración debe ser posterior o igual a la fecha de cotización.',

      // Details array messages
      'details.required' => 'Debe incluir al menos un producto en la cotización.',
      'details.array' => 'Los detalles deben ser un array.',
      'details.min' => 'Debe incluir al menos un producto en la cotización.',

      // Product details messages
      'details.*.product_id.required' => 'El producto es obligatorio en todos los detalles.',
      'details.*.product_id.integer' => 'El producto debe ser un entero.',
      'details.*.product_id.exists' => 'El producto seleccionado no es válido.',

      'details.*.description.required' => 'La descripción es obligatoria en todos los detalles.',
      'details.*.description.string' => 'La descripción debe ser una cadena de texto.',
      'details.*.description.max' => 'La descripción no puede exceder 255 caracteres.',

      'details.*.quantity.required' => 'La cantidad es obligatoria en todos los detalles.',
      'details.*.quantity.numeric' => 'La cantidad debe ser un número.',
      'details.*.quantity.min' => 'La cantidad debe ser mayor a 0.',

      'details.*.unit_measure.required' => 'La unidad de medida es obligatoria en todos los detalles.',
      'details.*.unit_measure.string' => 'La unidad de medida debe ser una cadena de texto.',
      'details.*.unit_measure.max' => 'La unidad de medida no puede exceder 50 caracteres.',

      'details.*.unit_price.required' => 'El precio unitario es obligatorio en todos los detalles.',
      'details.*.unit_price.numeric' => 'El precio unitario debe ser un número.',
      'details.*.unit_price.min' => 'El precio unitario no puede ser negativo.',

      'details.*.discount_percentage.numeric' => 'El porcentaje de descuento debe ser un número.',
      'details.*.discount_percentage.min' => 'El porcentaje de descuento no puede ser negativo.',
      'details.*.discount_percentage.max' => 'El porcentaje de descuento no puede ser mayor a 100.',

      'details.*.total_amount.required' => 'El monto total es obligatorio en todos los detalles.',
      'details.*.total_amount.numeric' => 'El monto total debe ser un número.',
      'details.*.total_amount.min' => 'El monto total no puede ser menor a 1.',

      'details.*.observations.string' => 'Las observaciones deben ser una cadena de texto.',

      'details.*.retail_price_external.numeric' => 'El precio externo debe ser un número.',
      'details.*.retail_price_external.min' => 'El precio externo no puede ser negativo.',

      'details.*.exchange_rate.numeric' => 'El tipo de cambio debe ser un número.',
      'details.*.exchange_rate.min' => 'El tipo de cambio no puede ser negativo.',

      'details.*.freight_commission.numeric' => 'La comisión de flete debe ser un número.',
      'details.*.freight_commission.min' => 'La comisión de flete no puede ser negativa.',
      'details.*.freight_commission.max' => 'La comisión de flete no puede ser mayor a 100.',

      'details.*.supply_type.required' => 'El tipo de suministro es obligatorio en todos los detalles.',
      'details.*.supply_type.string' => 'El tipo de suministro debe ser una cadena de texto.',
      'details.*.supply_type.in' => 'El tipo de suministro debe ser STOCK, TRASLADO, LOCAL, CENTRAL o IMPORTACION.',
    ];
  }

  public function withValidator($validator): void
  {
    $validator->after(function ($validator) {
      $details = $this->input('details', []);

      if (empty($details)) {
        return;
      }

      $productIds = collect($details)->pluck('product_id')->filter();
      $duplicates = $productIds->duplicates()->values();

      if ($duplicates->isNotEmpty()) {
        $productDescriptions = collect($details)
          ->whereIn('product_id', $duplicates->toArray())
          ->unique('product_id')
          ->pluck('description', 'product_id');

        $duplicateNames = $duplicates->map(fn($id) => $productDescriptions[$id] ?? "ID: {$id}");

        $validator->errors()->add(
          'details',
          'Se han detectado productos duplicados. Los siguientes productos deben ser consolidados en un solo item: ' . $duplicateNames->implode(', ') . '.'
        );
      }

      // Validar stock según supply_type de cada detalle
      $sedeId = $this->input('sede_id');
      $warehouseId = Warehouse::getPhysicalWarehouseForPostsale($sedeId)?->id;

      if (!$warehouseId) {
        $validator->errors()->add(
          'sede_id',
          'No se encontró un almacén físico asociado a esta sede para postventa. No se puede validar el stock de los productos.'
        );
        return;
      }

      foreach ($details as $index => $detail) {
        $productId = $detail['product_id'] ?? null;
        $supplyType = $detail['supply_type'] ?? null;
        $quantity = $detail['quantity'] ?? 0;

        if (!$productId || !$supplyType || !$sedeId) {
          continue;
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
              "details.{$index}.supply_type",
              "No puede usar tipo STOCK. El producto solo tiene {$stockInCurrentSede} unidades disponibles en esta sede pero solicita {$quantity}. Debe usar TRASLADO, LOCAL, CENTRAL o IMPORTACION."
            );
          }
        }

        // Validación para TRASLADO: solo si NO hay suficiente en sede actual PERO sí en otras sedes
        if ($supplyType === 'TRASLADO') {
          if ($stockInCurrentSede >= $quantity) {
            $validator->errors()->add(
              "details.{$index}.supply_type",
              "No puede usar tipo TRASLADO porque dispone de {$stockInCurrentSede} unidades en stock de su sede, lo cual es suficiente para las {$quantity} solicitadas. Por favor, use tipo STOCK en su lugar."
            );
          } elseif ($stockInCurrentSede > 0 && $stockInCurrentSede < $quantity) {
            $quantityNeeded = $quantity - $stockInCurrentSede;
            $validator->errors()->add(
              "details.{$index}.supply_type",
              "No puede solicitar {$quantity} unidades con TRASLADO porque tiene {$stockInCurrentSede} unidades disponibles en stock de su sede. Por favor, genere dos cotizaciones separadas: una con {$stockInCurrentSede} unidades usando tipo STOCK y otra con {$quantityNeeded} unidades usando tipo TRASLADO."
            );
          } elseif ($stockInOtherSedes <= 0) {
            $validator->errors()->add(
              "details.{$index}.supply_type",
              "No puede usar tipo TRASLADO porque no hay stock disponible en otras sedes. Debe usar LOCAL, CENTRAL o IMPORTACION."
            );
          } elseif ($stockInOtherSedes < $quantity) {
            $validator->errors()->add(
              "details.{$index}.supply_type",
              "No puede usar tipo TRASLADO para {$quantity} unidades porque solo hay {$stockInOtherSedes} unidades disponibles en otras sedes. Debe usar LOCAL, CENTRAL o IMPORTACION para las unidades faltantes."
            );
          }
        }

        // Validación para LOCAL, CENTRAL, IMPORTACION: solo si la cantidad excede el stock de la sede actual
        if (in_array($supplyType, ['LOCAL', 'CENTRAL', 'IMPORTACION'])) {
          if ($stockInCurrentSede >= $quantity) {
            $validator->errors()->add(
              "details.{$index}.supply_type",
              "No puede usar tipo {$supplyType}. El producto tiene {$stockInCurrentSede} unidades en esta sede, suficientes para las {$quantity} solicitadas. Debe usar tipo STOCK."
            );
          }
        }
      }
    });
  }
}
