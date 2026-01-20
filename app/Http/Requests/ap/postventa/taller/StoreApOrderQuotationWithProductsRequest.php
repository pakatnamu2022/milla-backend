<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use Illuminate\Contracts\Validation\Validator;

class StoreApOrderQuotationWithProductsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      // Quotation fields
      'currency_id' => ['required', 'integer', 'exists:type_currency,id'],
      'area_id' => ['required', 'integer', 'exists:ap_masters,id'],
      'vehicle_id' => ['required', 'integer', 'exists:ap_vehicles,id'],
      'sede_id' => ['required', 'integer', 'exists:config_sede,id'],
      'quotation_date' => ['required', 'date'],
      'expiration_date' => ['nullable', 'date', 'after_or_equal:quotation_date'],
      'collection_date' => ['nullable', 'date'],
      'observations' => ['nullable', 'string'],
      'supply_type' => ['required', 'string', 'in:STOCK,LIMA,IMPORTACION'],

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
      'vehicle_id.required' => 'Vehículo asociado es obligatorio.',
      'vehicle_id.exists' => 'El vehículo asociado no existe.',
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
