<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use App\Models\ap\ApMasters;
use Illuminate\Validation\Rule;

class StoreApModelsVnRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('ap_models_vn', 'code')
          ->where('type_operation_id', $this->input('type_operation_id'))
          ->whereNull('deleted_at'),
      ],
      'version' => [
        'required',
        'string',
        'max:255',
      ],
      'power' => [
        'required',
        'string',
        'max:50',
      ],
      'model_year' => [
        'required',
        'integer',
        'min:1900',
        'max:' . (date('Y') + 5),
      ],
      'wheelbase' => [
        'required',
        'string',
        'max:50',
      ],
      'axles_number' => [
        'required',
        'string',
        'max:50',
      ],
      'width' => [
        'required',
        'string',
        'max:50',
      ],
      'length' => [
        'required',
        'string',
        'max:50',
      ],
      'height' => [
        'required',
        'string',
        'max:50',
      ],
      'seats_number' => [
        'required',
        'string',
        'max:50',
      ],
      'doors_number' => [
        'required',
        'string',
        'max:50',
      ],
      'net_weight' => [
        'required',
        'string',
        'max:50',
      ],
      'gross_weight' => [
        'required',
        'string',
        'max:50',
      ],
      'payload' => [
        'required',
        'string',
        'max:50',
      ],
      'displacement' => [
        'required',
        'string',
        'max:50',
      ],
      'cylinders_number' => [
        'required',
        'string',
        'max:50',
      ],
      'passengers_number' => [
        'required',
        'string',
        'max:50',
      ],
      'wheels_number' => [
        'required',
        'string',
        'max:50',
      ],
      'distributor_price' => [
        Rule::requiredIf(fn() => $this->input('type_operation_id') == ApMasters::TIPO_OPERACION_COMERCIAL),
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'transport_cost' => [
        Rule::requiredIf(fn() => $this->input('type_operation_id') == ApMasters::TIPO_OPERACION_COMERCIAL),
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'other_amounts' => [
        Rule::requiredIf(fn() => $this->input('type_operation_id') == ApMasters::TIPO_OPERACION_COMERCIAL),
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'purchase_discount' => [
        Rule::requiredIf(fn() => $this->input('type_operation_id') == ApMasters::TIPO_OPERACION_COMERCIAL),
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'igv_amount' => [
        Rule::requiredIf(fn() => $this->input('type_operation_id') == ApMasters::TIPO_OPERACION_COMERCIAL),
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'total_purchase_excl_igv' => [
        Rule::requiredIf(fn() => $this->input('type_operation_id') == ApMasters::TIPO_OPERACION_COMERCIAL),
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'total_purchase_incl_igv' => [
        Rule::requiredIf(fn() => $this->input('type_operation_id') == ApMasters::TIPO_OPERACION_COMERCIAL),
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'sale_price' => [
        Rule::requiredIf(fn() => $this->input('type_operation_id') == ApMasters::TIPO_OPERACION_COMERCIAL),
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'margin' => [
        Rule::requiredIf(fn() => $this->input('type_operation_id') == ApMasters::TIPO_OPERACION_COMERCIAL),
        'nullable',
        'numeric',
        'min:0',
        'max:9999999999.99',
      ],
      'family_id' => [
        'required',
        'integer',
        'exists:ap_families,id',
      ],
      'class_id' => [
        'required',
        'integer',
        'exists:ap_class_article,id',
      ],
      'fuel_id' => [
        'required',
        'integer',
        'exists:ap_fuel_type,id',
      ],
      'vehicle_type_id' => [
        'required',
        'integer',
        'exists:ap_masters,id',
      ],
      'body_type_id' => [
        'required',
        'integer',
        'exists:ap_masters,id',
      ],
      'traction_type_id' => [
        'required',
        'integer',
        'exists:ap_masters,id',
      ],
      'transmission_id' => [
        'required',
        'integer',
        'exists:ap_masters,id',
      ],
      'currency_type_id' => [
        Rule::requiredIf(fn() => $this->input('currency_type_id') == ApMasters::TIPO_OPERACION_COMERCIAL),
        'nullable',
        'integer',
        'exists:type_currency,id',
      ],
      'type_operation_id' => [
        'required',
        'integer',
        'exists:ap_masters,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      // Código
      'code.required' => 'El código es obligatorio.',
      'code.string' => 'El código debe ser una cadena de texto.',
      'code.max' => 'El código no puede tener más de 50 caracteres.',
      'code.unique' => 'Ya existe un modelo con este código.',

      // Versión
      'version.required' => 'La versión es obligatoria.',
      'version.string' => 'La versión debe ser una cadena de texto.',
      'version.max' => 'La versión no puede tener más de 255 caracteres.',

      // power
      'power.required' => 'La potencia es obligatoria.',
      'power.string' => 'La potencia debe ser una cadena de texto.',
      'power.max' => 'La potencia no puede tener más de 50 caracteres.',

      // Año del modelo
      'model_year.required' => 'El año del modelo es obligatorio.',
      'model_year.integer' => 'El año del modelo debe ser un número entero.',
      'model_year.min' => 'El año del modelo no puede ser menor a 1900.',
      'model_year.max' => 'El año del modelo no puede ser mayor a ' . (date('Y') + 5) . '.',

      // Distancias ejes
      'wheelbase.required' => 'La distancia entre ejes es obligatoria.',
      'wheelbase.string' => 'La distancia entre ejes debe ser una cadena de texto.',
      'wheelbase.max' => 'La distancia entre ejes no puede tener más de 50 caracteres.',

      // Número de ejes
      'axles_number.required' => 'El número de ejes es obligatorio.',
      'axles_number.string' => 'El número de ejes debe ser una cadena de texto.',
      'axles_number.max' => 'El número de ejes no puede tener más de 50 caracteres.',

      // width
      'width.required' => 'El ancho es obligatorio.',
      'width.string' => 'El ancho debe ser una cadena de texto.',
      'width.max' => 'El ancho no puede tener más de 50 caracteres.',

      // length
      'length.required' => 'El largo es obligatorio.',
      'length.string' => 'El largo debe ser una cadena de texto.',
      'length.max' => 'El largo no puede tener más de 50 caracteres.',

      // height
      'height.required' => 'La altura es obligatoria.',
      'height.string' => 'La altura debe ser una cadena de texto.',
      'height.max' => 'La altura no puede tener más de 50 caracteres.',

      // Número de asientos
      'seats_number.required' => 'El número de asientos es obligatorio.',
      'seats_number.string' => 'El número de asientos debe ser una cadena de texto.',
      'seats_number.max' => 'El número de asientos no puede tener más de 50 caracteres.',

      // Número de puertas
      'doors_number.required' => 'El número de puertas es obligatorio.',
      'doors_number.string' => 'El número de puertas debe ser una cadena de texto.',
      'doors_number.max' => 'El número de puertas no puede tener más de 50 caracteres.',

      // Peso neto
      'net_weight.required' => 'El peso neto es obligatorio.',
      'net_weight.string' => 'El peso neto debe ser una cadena de texto.',
      'net_weight.max' => 'El peso neto no puede tener más de 50 caracteres.',

      // Peso bruto
      'gross_weight.required' => 'El peso bruto es obligatorio.',
      'gross_weight.string' => 'El peso bruto debe ser una cadena de texto.',
      'gross_weight.max' => 'El peso bruto no puede tener más de 50 caracteres.',

      // Carga útil
      'payload.required' => 'La carga útil es obligatoria.',
      'payload.string' => 'La carga útil debe ser una cadena de texto.',
      'payload.max' => 'La carga útil no puede tener más de 50 caracteres.',

      // displacement
      'displacement.required' => 'La displacement es obligatoria.',
      'displacement.string' => 'La displacement debe ser una cadena de texto.',
      'displacement.max' => 'La displacement no puede tener más de 50 caracteres.',

      // Número de cilindros
      'cylinders_number.required' => 'El número de cilindros es obligatorio.',
      'cylinders_number.string' => 'El número de cilindros debe ser una cadena de texto.',
      'cylinders_number.max' => 'El número de cilindros no puede tener más de 50 caracteres.',

      // Número de pasajeros
      'passengers_number.required' => 'El número de pasajeros es obligatorio.',
      'passengers_number.string' => 'El número de pasajeros debe ser una cadena de texto.',
      'passengers_number.max' => 'El número de pasajeros no puede tener más de 50 caracteres.',

      // Número de ruedas
      'wheels_number.required' => 'El número de ruedas es obligatorio.',
      'wheels_number.string' => 'El número de ruedas debe ser una cadena de texto.',
      'wheels_number.max' => 'El número de ruedas no puede tener más de 50 caracteres.',

      // Precio distribuidor
      'distributor_price.required' => 'El precio del distribuidor es obligatorio.',
      'distributor_price.numeric' => 'El precio del distribuidor debe ser un número.',
      'distributor_price.min' => 'El precio del distribuidor debe ser mayor o igual a 0.',
      'distributor_price.max' => 'El precio del distribuidor no puede exceder 9999999999.99.',

      // Costo de transporte
      'transport_cost.required' => 'El costo de transporte es obligatorio.',
      'transport_cost.numeric' => 'El costo de transporte debe ser un número.',
      'transport_cost.min' => 'El costo de transporte debe ser mayor o igual a 0.',
      'transport_cost.max' => 'El costo de transporte no puede exceder 9999999999.99.',

      // Otros importes
      'other_amounts.required' => 'Los otros importes son obligatorios.',
      'other_amounts.numeric' => 'Los otros importes deben ser un número.',
      'other_amounts.min' => 'Los otros importes deben ser mayor o igual a 0.',
      'other_amounts.max' => 'Los otros importes no pueden exceder 9999999999.99.',

      // Descuento compra
      'purchase_discount.required' => 'El descuento de compra es obligatorio.',
      'purchase_discount.numeric' => 'El descuento de compra debe ser un número.',
      'purchase_discount.min' => 'El descuento de compra debe ser mayor o igual a 0.',
      'purchase_discount.max' => 'El descuento de compra no puede exceder 9999999999.99.',

      // Importe IGV
      'igv_amount.required' => 'El importe del IGV es obligatorio.',
      'igv_amount.numeric' => 'El importe del IGV debe ser un número.',
      'igv_amount.min' => 'El importe del IGV debe ser mayor o igual a 0.',
      'igv_amount.max' => 'El importe del IGV no puede exceder 9999999999.99.',

      // Total compra sin IGV
      'total_purchase_excl_igv.required' => 'El total de compra con IGV es obligatorio.',
      'total_purchase_excl_igv.numeric' => 'El total de compra con IGV debe ser un número.',
      'total_purchase_excl_igv.min' => 'El total de compra con IGV debe ser mayor o igual a 0.',
      'total_purchase_excl_igv.max' => 'El total de compra con IGV no puede exceder 9999999999.99.',

      // Total compra con IGV
      'total_purchase_incl_igv.required' => 'El total de compra con IGV es obligatorio.',
      'total_purchase_incl_igv.numeric' => 'El total de compra con IGV debe ser un número.',
      'total_purchase_incl_igv.min' => 'El total de compra con IGV debe ser mayor o igual a 0.',
      'total_purchase_incl_igv.max' => 'El total de compra con IGV no puede exceder 9999999999.99.',

      // Precio venta
      'sale_price.required' => 'El precio de venta es obligatorio.',
      'sale_price.numeric' => 'El precio de venta debe ser un número.',
      'sale_price.min' => 'El precio de venta debe ser mayor o igual a 0.',
      'sale_price.max' => 'El precio de venta no puede exceder 9999999999.99.',

      // margin
      'margin.required' => 'El margen es obligatorio.',
      'margin.numeric' => 'El margen debe ser un número.',
      'margin.min' => 'El margen debe ser mayor o igual a 0.',
      'margin.max' => 'El margen no puede exceder 9999999999.99.',

      // Familia
      'family_id.required' => 'La familia es obligatoria.',
      'family_id.integer' => 'La familia debe ser un número entero.',
      'family_id.exists' => 'La familia seleccionada no existe.',

      // Clase
      'class_id.required' => 'La clase es obligatoria.',
      'class_id.integer' => 'La clase debe ser un número entero.',
      'class_id.exists' => 'La clase seleccionada no existe.',

      // Combustible
      'fuel_id.required' => 'El tipo de combustible es obligatorio.',
      'fuel_id.integer' => 'El tipo de combustible debe ser un número entero.',
      'fuel_id.exists' => 'El tipo de combustible seleccionado no existe.',

      // Tipo de vehículo
      'vehicle_type_id.required' => 'El tipo de vehículo es obligatorio.',
      'vehicle_type_id.integer' => 'El tipo de vehículo debe ser un número entero.',
      'vehicle_type_id.exists' => 'El tipo de vehículo seleccionado no existe.',

      // Tipo de carrocería
      'body_type_id.required' => 'El tipo de carrocería es obligatorio.',
      'body_type_id.integer' => 'El tipo de carrocería debe ser un número entero.',
      'body_type_id.exists' => 'El tipo de carrocería seleccionado no existe.',

      // Tipo de tracción
      'traction_type_id.required' => 'El tipo de tracción es obligatorio.',
      'traction_type_id.integer' => 'El tipo de tracción debe ser un número entero.',
      'traction_type_id.exists' => 'El tipo de tracción seleccionado no existe.',

      // Transmisión
      'transmission_id.required' => 'La transmisión es obligatoria.',
      'transmission_id.integer' => 'La transmisión debe ser un número entero.',
      'transmission_id.exists' => 'La transmisión seleccionada no existe.',

      // Tipo de moneda
      'currency_type_id.required' => 'El tipo de moneda es obligatorio.',
      'currency_type_id.integer' => 'El tipo de moneda debe ser un número entero.',
      'currency_type_id.exists' => 'El tipo de moneda seleccionado no existe.',

      // Tipo de operación
      'type_operation_id.required' => 'El tipo de operación es obligatorio.',
      'type_operation_id.integer' => 'El tipo de operación debe ser un número entero.',
      'type_operation_id.exists' => 'El tipo de operación seleccionado no existe.',
    ];
  }
}
