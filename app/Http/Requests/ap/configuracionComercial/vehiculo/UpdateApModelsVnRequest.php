<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateApModelsVnRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'nullable',
        'max:50',
        Rule::unique('ap_models_vn', 'code')
          ->where('type_operation_id', $this->input('type_operation_id'))
          ->ignore($this->route('modelsVn'))
          ->whereNull('deleted_at'),
      ],
      'version' => [
        'nullable',
        'max:255',
      ],
      'power' => [
        'nullable',
        'max:50',
      ],
      'model_year' => [
        'nullable',
        'integer',
        'min:1900',
        'max:' . (date('Y') + 5),
      ],
      'wheelbase' => [
        'nullable',
        'max:50',
      ],
      'axles_number' => [
        'nullable',
        'max:50',
      ],
      'width' => [
        'nullable',
        'max:50',
      ],
      'length' => [
        'nullable',
        'max:50',
      ],
      'height' => [
        'nullable',
        'max:50',
      ],
      'seats_number' => [
        'nullable',
        'max:50',
      ],
      'doors_number' => [
        'nullable',
        'max:50',
      ],
      'net_weight' => [
        'nullable',
        'max:50',
      ],
      'gross_weight' => [
        'nullable',
        'max:50',
      ],
      'payload' => [
        'nullable',
        'max:50',
      ],
      'displacement' => [
        'nullable',
        'max:50',
      ],
      'cylinders_number' => [
        'nullable',
        'max:50',
      ],
      'passengers_number' => [
        'nullable',
        'max:50',
      ],
      'wheels_number' => [
        'nullable',
        'max:50',
      ],
      'distributor_price' => [
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'transport_cost' => [
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'other_amounts' => [
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'purchase_discount' => [
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'igv_amount' => [
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'total_purchase_excl_igv' => [
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'total_purchase_incl_igv' => [
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'sale_price' => [
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'margin' => [
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'family_id' => [
        'nullable',
        'integer',
        'exists:ap_families,id',
      ],
      'class_id' => [
        'nullable',
        'integer',
        'exists:ap_class_article,id',
      ],
      'fuel_id' => [
        'nullable',
        'integer',
        'exists:ap_fuel_type,id',
      ],
      'vehicle_type_id' => [
        'nullable',
        'integer',
        'exists:ap_masters,id',
      ],
      'body_type_id' => [
        'nullable',
        'integer',
        'exists:ap_masters,id',
      ],
      'traction_type_id' => [
        'nullable',
        'integer',
        'exists:ap_masters,id',
      ],
      'transmission_id' => [
        'nullable',
        'integer',
        'exists:ap_masters,id',
      ],
      'currency_type_id' => [
        'nullable',
        'integer',
        'exists:type_currency,id',
      ],
      'type_operation_id' => [
        'nullable',
        'integer',
        'exists:ap_masters,id',
      ],
      'status' => ['nullable', 'boolean']
    ];
  }

  public function attributes()
  {
    return [
      'code' => 'código',
      'version' => 'versión',
      'power' => 'potencia',
      'model_year' => 'año del modelo',
      'wheelbase' => 'distancia entre ejes',
      'axles_number' => 'número de ejes',
      'width' => 'ancho',
      'length' => 'largo',
      'height' => 'alto',
      'seats_number' => 'número de asientos',
      'doors_number' => 'número de puertas',
      'net_weight' => 'peso neto',
      'gross_weight' => 'peso bruto',
      'payload' => 'capacidad de carga',
      'displacement' => 'cilindrada',
      'cylinders_number' => 'número de cilindros',
      'passengers_number' => 'número de pasajeros',
      'wheels_number' => 'número de ruedas',
      'distributor_price' => 'precio distribuidor',
      'transport_cost' => 'costo de transporte',
      'other_amounts' => 'otros montos',
      'purchase_discount' => 'descuento de compra',
      'igv_amount' => 'monto del IGV',
      'total_purchase_excl_igv' => 'total compra sin IGV',
      'total_purchase_incl_igv' => 'total compra con IGV',
      'sale_price' => 'precio de venta',
      'margin' => 'margen',
      // Relaciones
      'family_id' => 'familia',
      'class_id' => 'clase',
      'fuel_id' => 'combustible',
      'vehicle_type_id' => 'tipo de vehículo',
      'body_type_id' => 'tipo de carrocería',
      'traction_type_id' => 'tipo de tracción',
      'transmission_id' => 'transmisión',
      'currency_type_id' => 'tipo de moneda',
      'type_operation_id' => 'tipo de operación',
    ];
  }
}
