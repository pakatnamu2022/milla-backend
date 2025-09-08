<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApModelsVnRequest extends FormRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('ap_models_vn', 'code')
          ->ignore($this->route('modelsVn'))
          ->whereNull('deleted_at'),
      ],
      'version' => [
        'nullable',
        'string',
        'max:255',
      ],
      'power' => [
        'nullable',
        'string',
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
        'string',
        'max:50',
      ],
      'axles_number' => [
        'nullable',
        'string',
        'max:50',
      ],
      'width' => [
        'nullable',
        'string',
        'max:50',
      ],
      'length' => [
        'nullable',
        'string',
        'max:50',
      ],
      'height' => [
        'nullable',
        'string',
        'max:50',
      ],
      'seats_number' => [
        'nullable',
        'string',
        'max:50',
      ],
      'doors_number' => [
        'nullable',
        'string',
        'max:50',
      ],
      'net_weight' => [
        'nullable',
        'string',
        'max:50',
      ],
      'gross_weight' => [
        'nullable',
        'string',
        'max:50',
      ],
      'payload' => [
        'nullable',
        'string',
        'max:50',
      ],
      'displacement' => [
        'nullable',
        'string',
        'max:50',
      ],
      'cylinders_number' => [
        'nullable',
        'string',
        'max:50',
      ],
      'passengers_number' => [
        'nullable',
        'string',
        'max:50',
      ],
      'wheels_number' => [
        'nullable',
        'string',
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
        'exists:ap_commercial_masters,id',
      ],
      'body_type_id' => [
        'nullable',
        'integer',
        'exists:ap_commercial_masters,id',
      ],
      'traction_type_id' => [
        'nullable',
        'integer',
        'exists:ap_commercial_masters,id',
      ],
      'transmission_id' => [
        'nullable',
        'integer',
        'exists:ap_commercial_masters,id',
      ],
      'currency_type_id' => [
        'nullable',
        'integer',
        'exists:type_currency,id',
      ],
      'status' => ['nullable', 'boolean']
    ];
  }

  public function messages(): array
  {
    return [
      // Código
      'code.string' => 'El código debe ser una cadena de texto.',
      'code.max' => 'El código no puede tener más de 50 caracteres.',
      'code.unique' => 'Ya existe un modelo con este código.',

      // Versión
      'version.string' => 'La versión debe ser una cadena de texto.',
      'version.max' => 'La versión no puede tener más de 255 caracteres.',

      // power
      'power.string' => 'La potencia debe ser una cadena de texto.',
      'power.max' => 'La potencia no puede tener más de 50 caracteres.',

      // Año del modelo
      'model_year.integer' => 'El año del modelo debe ser un número entero.',
      'model_year.min' => 'El año del modelo no puede ser menor a 1900.',
      'model_year.max' => 'El año del modelo no puede ser mayor a ' . (date('Y') + 5) . '.',

      // Distancias ejes
      'wheelbase.string' => 'La distancia entre ejes debe ser una cadena de texto.',
      'wheelbase.max' => 'La distancia entre ejes no puede tener más de 50 caracteres.',

      // Número de ejes
      'axles_number.string' => 'El número de ejes debe ser una cadena de texto.',
      'axles_number.max' => 'El número de ejes no puede tener más de 50 caracteres.',

      // width
      'width.string' => 'El ancho debe ser una cadena de texto.',
      'width.max' => 'El ancho no puede tener más de 50 caracteres.',

      // length
      'length.string' => 'El largo debe ser una cadena de texto.',
      'length.max' => 'El largo no puede tener más de 50 caracteres.',

      // height
      'height.string' => 'La height debe ser una cadena de texto.',
      'height.max' => 'La height no puede tener más de 50 caracteres.',

      // Número de asientos
      'seats_number.string' => 'El número de asientos debe ser una cadena de texto.',
      'seats_number.max' => 'El número de asientos no puede tener más de 50 caracteres.',

      // Número de puertas
      'doors_number.string' => 'El número de puertas debe ser una cadena de texto.',
      'doors_number.max' => 'El número de puertas no puede tener más de 50 caracteres.',

      // Peso neto
      'net_weight.string' => 'El peso neto debe ser una cadena de texto.',
      'net_weight.max' => 'El peso neto no puede tener más de 50 caracteres.',

      // Peso bruto
      'gross_weight.string' => 'El peso bruto debe ser una cadena de texto.',
      'gross_weight.max' => 'El peso bruto no puede tener más de 50 caracteres.',

      // Carga útil
      'payload.string' => 'La carga útil debe ser una cadena de texto.',
      'payload.max' => 'La carga útil no puede tener más de 50 caracteres.',

      // displacement
      'displacement.string' => 'La displacement debe ser una cadena de texto.',
      'displacement.max' => 'La displacement no puede tener más de 50 caracteres.',

      // Número de cilindros
      'cylinders_number.string' => 'El número de cilindros debe ser una cadena de texto.',
      'cylinders_number.max' => 'El número de cilindros no puede tener más de 50 caracteres.',

      // Número de pasajeros
      'passengers_number.string' => 'El número de pasajeros debe ser una cadena de texto.',
      'passengers_number.max' => 'El número de pasajeros no puede tener más de 50 caracteres.',

      // Número de ruedas
      'wheels_number.string' => 'El número de ruedas debe ser una cadena de texto.',
      'wheels_number.max' => 'El número de ruedas no puede tener más de 50 caracteres.',

      // Precio distribuidor
      'distributor_price.numeric' => 'El precio del distribuidor debe ser un número.',
      'distributor_price.min' => 'El precio del distribuidor debe ser mayor o igual a 0.',
      'distributor_price.max' => 'El precio del distribuidor no puede exceder 999,999.9999.',

      // Costo de transporte
      'transport_cost.numeric' => 'El costo de transporte debe ser un número.',
      'transport_cost.min' => 'El costo de transporte debe ser mayor o igual a 0.',
      'transport_cost.max' => 'El costo de transporte no puede exceder 999,999.9999.',

      // Otros importes
      'other_amounts.numeric' => 'Los otros importes deben ser un número.',
      'other_amounts.min' => 'Los otros importes deben ser mayor o igual a 0.',
      'other_amounts.max' => 'Los otros importes no pueden exceder 999,999.9999.',

      // Descuento compra
      'purchase_discount.numeric' => 'El descuento de compra debe ser un número.',
      'purchase_discount.min' => 'El descuento de compra debe ser mayor o igual a 0.',
      'purchase_discount.max' => 'El descuento de compra no puede exceder 999,999.9999.',

      // Importe IGV
      'igv_amount.numeric' => 'El importe del IGV debe ser un número.',
      'igv_amount.min' => 'El importe del IGV debe ser mayor o igual a 0.',
      'igv_amount.max' => 'El importe del IGV no puede exceder 999,999.9999.',

      // Total compra sin IGV
      'total_purchase_excl_igv.numeric' => 'El total de compra con IGV debe ser un número.',
      'total_purchase_excl_igv.min' => 'El total de compra con IGV debe ser mayor o igual a 0.',
      'total_purchase_excl_igv.max' => 'El total de compra con IGV no puede exceder 999,999.9999.',

      // Total compra con IGV
      'total_purchase_incl_igv.numeric' => 'El total de compra con IGV debe ser un número.',
      'total_purchase_incl_igv.min' => 'El total de compra con IGV debe ser mayor o igual a 0.',
      'total_purchase_incl_igv.max' => 'El total de compra con IGV no puede exceder 999,999.9999.',

      // Precio venta
      'sale_price.numeric' => 'El precio de venta debe ser un número.',
      'sale_price.min' => 'El precio de venta debe ser mayor o igual a 0.',
      'sale_price.max' => 'El precio de venta no puede exceder 999,999.9999.',

      // margin
      'margin.numeric' => 'El margen debe ser un número.',
      'margin.min' => 'El margen debe ser mayor o igual a 0.',
      'margin.max' => 'El margen no puede exceder 999,999.9999.',

      // Familia
      'family_id.integer' => 'La familia debe ser un número entero.',
      'family_id.exists' => 'La familia seleccionada no existe.',

      // Clase
      'class_id.integer' => 'La clase debe ser un número entero.',
      'class_id.exists' => 'La clase seleccionada no existe.',

      // Combustible
      'fuel_id.integer' => 'El tipo de combustible debe ser un número entero.',
      'fuel_id.exists' => 'El tipo de combustible seleccionado no existe.',

      // Tipo de vehículo
      'vehicle_type_id.integer' => 'El tipo de vehículo debe ser un número entero.',
      'vehicle_type_id.exists' => 'El tipo de vehículo seleccionado no existe.',

      // Tipo de carrocería
      'body_type_id.integer' => 'El tipo de carrocería debe ser un número entero.',
      'body_type_id.exists' => 'El tipo de carrocería seleccionado no existe.',

      // Tipo de tracción
      'traction_type_id.integer' => 'El tipo de tracción debe ser un número entero.',
      'traction_type_id.exists' => 'El tipo de tracción seleccionado no existe.',

      // Transmisión
      'transmission_id.integer' => 'La transmisión debe ser un número entero.',
      'transmission_id.exists' => 'La transmisión seleccionada no existe.',

      // Tipo de moneda
      'currency_type_id.integer' => 'El tipo de moneda debe ser un número entero.',
      'currency_type_id.exists' => 'El tipo de moneda seleccionado no existe.',
    ];
  }
}
