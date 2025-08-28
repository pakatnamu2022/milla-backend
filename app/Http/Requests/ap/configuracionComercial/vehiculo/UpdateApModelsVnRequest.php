<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApModelsVnRequest extends FormRequest
{
  public function rules(): array
  {
    return [
      'codigo' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('ap_models_vn', 'codigo')
          ->ignore($this->route('modelsVn'))
          ->whereNull('deleted_at'),
      ],
      'version' => [
        'nullable',
        'string',
        'max:255',
      ],
      'potencia' => [
        'nullable',
        'string',
        'max:50',
      ],
      'anio_modelo' => [
        'nullable',
        'integer',
        'min:1900',
        'max:' . (date('Y') + 5),
      ],
      'distancias_ejes' => [
        'nullable',
        'string',
        'max:50',
      ],
      'num_ejes' => [
        'nullable',
        'string',
        'max:50',
      ],
      'ancho' => [
        'nullable',
        'string',
        'max:50',
      ],
      'largo' => [
        'nullable',
        'string',
        'max:50',
      ],
      'altura' => [
        'nullable',
        'string',
        'max:50',
      ],
      'num_asientos' => [
        'nullable',
        'string',
        'max:50',
      ],
      'num_puertas' => [
        'nullable',
        'string',
        'max:50',
      ],
      'peso_neto' => [
        'nullable',
        'string',
        'max:50',
      ],
      'peso_bruto' => [
        'nullable',
        'string',
        'max:50',
      ],
      'carga_util' => [
        'nullable',
        'string',
        'max:50',
      ],
      'cilindrada' => [
        'nullable',
        'string',
        'max:50',
      ],
      'num_cilindros' => [
        'nullable',
        'string',
        'max:50',
      ],
      'num_pasajeros' => [
        'nullable',
        'string',
        'max:50',
      ],
      'num_ruedas' => [
        'nullable',
        'string',
        'max:50',
      ],
      'precio_distribuidor' => [
        'nullable',
        'numeric',
        'min:0',
        'max:999999.9999',
      ],
      'costo_transporte' => [
        'nullable',
        'numeric',
        'min:0',
        'max:999999.9999',
      ],
      'otros_importes' => [
        'nullable',
        'numeric',
        'min:0',
        'max:999999.9999',
      ],
      'descuento_compra' => [
        'nullable',
        'numeric',
        'min:0',
        'max:999999.9999',
      ],
      'importe_igv' => [
        'nullable',
        'numeric',
        'min:0',
        'max:999999.9999',
      ],
      'total_ctotal_compra_sigvompra_cigv' => [
        'nullable',
        'numeric',
        'min:0',
        'max:999999.9999',
      ],
      'precio_venta' => [
        'nullable',
        'numeric',
        'min:0',
        'max:999999.9999',
      ],
      'margen' => [
        'nullable',
        'numeric',
        'min:0',
        'max:999999.9999',
      ],
      'familia_id' => [
        'nullable',
        'integer',
        'exists:ap_families,id',
      ],
      'clase_id' => [
        'nullable',
        'integer',
        'exists:ap_class_article,id',
      ],
      'combustible_id' => [
        'nullable',
        'integer',
        'exists:ap_fuel_type,id',
      ],
      'tipo_vehiculo_id' => [
        'nullable',
        'integer',
        'exists:ap_commercial_masters,id',
      ],
      'tipo_carroceria_id' => [
        'nullable',
        'integer',
        'exists:ap_commercial_masters,id',
      ],
      'tipo_traccion_id' => [
        'nullable',
        'integer',
        'exists:ap_commercial_masters,id',
      ],
      'transmision_id' => [
        'nullable',
        'integer',
        'exists:ap_commercial_masters,id',
      ],
      'tipo_moneda_id' => [
        'nullable',
        'integer',
        'exists:type_currency,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      // Código
      'codigo.string' => 'El código debe ser una cadena de texto.',
      'codigo.max' => 'El código no puede tener más de 50 caracteres.',
      'codigo.unique' => 'Ya existe un modelo con este código.',

      // Versión
      'version.string' => 'La versión debe ser una cadena de texto.',
      'version.max' => 'La versión no puede tener más de 255 caracteres.',

      // Potencia
      'potencia.string' => 'La potencia debe ser una cadena de texto.',
      'potencia.max' => 'La potencia no puede tener más de 50 caracteres.',

      // Año del modelo
      'anio_modelo.integer' => 'El año del modelo debe ser un número entero.',
      'anio_modelo.min' => 'El año del modelo no puede ser menor a 1900.',
      'anio_modelo.max' => 'El año del modelo no puede ser mayor a ' . (date('Y') + 5) . '.',

      // Distancias ejes
      'distancias_ejes.string' => 'La distancia entre ejes debe ser una cadena de texto.',
      'distancias_ejes.max' => 'La distancia entre ejes no puede tener más de 50 caracteres.',

      // Número de ejes
      'num_ejes.string' => 'El número de ejes debe ser una cadena de texto.',
      'num_ejes.max' => 'El número de ejes no puede tener más de 50 caracteres.',

      // Ancho
      'ancho.string' => 'El ancho debe ser una cadena de texto.',
      'ancho.max' => 'El ancho no puede tener más de 50 caracteres.',

      // Largo
      'largo.string' => 'El largo debe ser una cadena de texto.',
      'largo.max' => 'El largo no puede tener más de 50 caracteres.',

      // Altura
      'altura.string' => 'La altura debe ser una cadena de texto.',
      'altura.max' => 'La altura no puede tener más de 50 caracteres.',

      // Número de asientos
      'num_asientos.string' => 'El número de asientos debe ser una cadena de texto.',
      'num_asientos.max' => 'El número de asientos no puede tener más de 50 caracteres.',

      // Número de puertas
      'num_puertas.string' => 'El número de puertas debe ser una cadena de texto.',
      'num_puertas.max' => 'El número de puertas no puede tener más de 50 caracteres.',

      // Peso neto
      'peso_neto.string' => 'El peso neto debe ser una cadena de texto.',
      'peso_neto.max' => 'El peso neto no puede tener más de 50 caracteres.',

      // Peso bruto
      'peso_bruto.string' => 'El peso bruto debe ser una cadena de texto.',
      'peso_bruto.max' => 'El peso bruto no puede tener más de 50 caracteres.',

      // Carga útil
      'carga_util.string' => 'La carga útil debe ser una cadena de texto.',
      'carga_util.max' => 'La carga útil no puede tener más de 50 caracteres.',

      // Cilindrada
      'cilindrada.string' => 'La cilindrada debe ser una cadena de texto.',
      'cilindrada.max' => 'La cilindrada no puede tener más de 50 caracteres.',

      // Número de cilindros
      'num_cilindros.string' => 'El número de cilindros debe ser una cadena de texto.',
      'num_cilindros.max' => 'El número de cilindros no puede tener más de 50 caracteres.',

      // Número de pasajeros
      'num_pasajeros.string' => 'El número de pasajeros debe ser una cadena de texto.',
      'num_pasajeros.max' => 'El número de pasajeros no puede tener más de 50 caracteres.',

      // Número de ruedas
      'num_ruedas.string' => 'El número de ruedas debe ser una cadena de texto.',
      'num_ruedas.max' => 'El número de ruedas no puede tener más de 50 caracteres.',

      // Precio distribuidor
      'precio_distribuidor.numeric' => 'El precio del distribuidor debe ser un número.',
      'precio_distribuidor.min' => 'El precio del distribuidor debe ser mayor o igual a 0.',
      'precio_distribuidor.max' => 'El precio del distribuidor no puede exceder 999,999.9999.',

      // Costo de transporte
      'costo_transporte.numeric' => 'El costo de transporte debe ser un número.',
      'costo_transporte.min' => 'El costo de transporte debe ser mayor o igual a 0.',
      'costo_transporte.max' => 'El costo de transporte no puede exceder 999,999.9999.',

      // Otros importes
      'otros_importes.numeric' => 'Los otros importes deben ser un número.',
      'otros_importes.min' => 'Los otros importes deben ser mayor o igual a 0.',
      'otros_importes.max' => 'Los otros importes no pueden exceder 999,999.9999.',

      // Descuento compra
      'descuento_compra.numeric' => 'El descuento de compra debe ser un número.',
      'descuento_compra.min' => 'El descuento de compra debe ser mayor o igual a 0.',
      'descuento_compra.max' => 'El descuento de compra no puede exceder 999,999.9999.',

      // Importe IGV
      'importe_igv.numeric' => 'El importe del IGV debe ser un número.',
      'importe_igv.min' => 'El importe del IGV debe ser mayor o igual a 0.',
      'importe_igv.max' => 'El importe del IGV no puede exceder 999,999.9999.',

      // Total compra con IGV
      'total_ctotal_compra_sigvompra_cigv.numeric' => 'El total de compra con IGV debe ser un número.',
      'total_ctotal_compra_sigvompra_cigv.min' => 'El total de compra con IGV debe ser mayor o igual a 0.',
      'total_ctotal_compra_sigvompra_cigv.max' => 'El total de compra con IGV no puede exceder 999,999.9999.',

      // Precio venta
      'precio_venta.numeric' => 'El precio de venta debe ser un número.',
      'precio_venta.min' => 'El precio de venta debe ser mayor o igual a 0.',
      'precio_venta.max' => 'El precio de venta no puede exceder 999,999.9999.',

      // Margen
      'margen.numeric' => 'El margen debe ser un número.',
      'margen.min' => 'El margen debe ser mayor o igual a 0.',
      'margen.max' => 'El margen no puede exceder 999,999.9999.',

      // Familia
      'familia_id.integer' => 'La familia debe ser un número entero.',
      'familia_id.exists' => 'La familia seleccionada no existe.',

      // Clase
      'clase_id.integer' => 'La clase debe ser un número entero.',
      'clase_id.exists' => 'La clase seleccionada no existe.',

      // Combustible
      'combustible_id.integer' => 'El tipo de combustible debe ser un número entero.',
      'combustible_id.exists' => 'El tipo de combustible seleccionado no existe.',

      // Tipo de vehículo
      'tipo_vehiculo_id.integer' => 'El tipo de vehículo debe ser un número entero.',
      'tipo_vehiculo_id.exists' => 'El tipo de vehículo seleccionado no existe.',

      // Tipo de carrocería
      'tipo_carroceria_id.integer' => 'El tipo de carrocería debe ser un número entero.',
      'tipo_carroceria_id.exists' => 'El tipo de carrocería seleccionado no existe.',

      // Tipo de tracción
      'tipo_traccion_id.integer' => 'El tipo de tracción debe ser un número entero.',
      'tipo_traccion_id.exists' => 'El tipo de tracción seleccionado no existe.',

      // Transmisión
      'transmision_id.integer' => 'La transmisión debe ser un número entero.',
      'transmision_id.exists' => 'La transmisión seleccionada no existe.',

      // Tipo de moneda
      'tipo_moneda_id.integer' => 'El tipo de moneda debe ser un número entero.',
      'tipo_moneda_id.exists' => 'El tipo de moneda seleccionado no existe.',
    ];
  }
}
