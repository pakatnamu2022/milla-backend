<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequestQuoteRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'type_document' => [
        'required',
        'string',
        'in:COTIZACION,SOLICITUD_COMPRA'
      ],
      'quote_deadline' => ['nullable', 'date'],
      'base_selling_price' => ['required', 'numeric'],
      'sale_price' => ['required', 'numeric', 'min:0'],
      'doc_sale_price' => ['required', 'numeric', 'min:0'],
      'down_payment' => ['nullable', 'numeric', 'min:0'],
      'comment' => ['nullable', 'string', 'max:255'],
      'warranty_years' => ['required', 'integer', 'min:1'],
      'warranty_km' => ['required', 'integer', 'min:1'],
      'opportunity_id' => ['nullable', 'exists:ap_opportunity,id', Rule::unique('purchase_request_quote', 'opportunity_id')->whereNull('deleted_at')],
      'holder_id' => ['required', 'exists:business_partners,id'],
      'vehicle_color_id' => ['required', 'exists:ap_masters,id'],
      'ap_models_vn_id' => ['nullable', 'exists:ap_models_vn,id'],
      'doc_type_currency_id' => ['required', 'exists:type_currency,id'],
      'ap_vehicle_id' => ['nullable', 'exists:ap_vehicles,id', Rule::unique('purchase_request_quote', 'ap_vehicle_id')->whereNull('deleted_at')],
      'with_vin' => ['nullable', 'boolean'],

      // Validaciones para bonus_discounts
      'bonus_discounts' => ['nullable', 'array'],
      'bonus_discounts.*.concept_id' => ['required', 'exists:ap_masters,id'],
      'bonus_discounts.*.description' => ['required', 'string', 'max:255'],
      'bonus_discounts.*.type' => ['required', 'string', 'in:FIJO,PORCENTAJE'],
      'bonus_discounts.*.value' => ['required', 'numeric', 'min:0'],
      'bonus_discounts.*.is_negative' => ['nullable', 'boolean'],

      // Validaciones para accessories
      'accessories' => ['nullable', 'array'],
      'accessories.*.accessory_id' => ['required', 'exists:approved_accessories,id'],
      'accessories.*.quantity' => ['required', 'integer', 'min:1'],
      'accessories.*.additional_price' => ['nullable', 'numeric', 'min:0'],

      'type_currency_id' => ['required', 'exists:ap_masters,id'],

      // Sede
      'sede_id' => ['required', 'exists:config_sede,id']
    ];
  }

  public function attributes()
  {
    return [
      'type_document' => 'Tipo de Documento',
      'quote_deadline' => 'Fecha Límite de Cotización',
      'base_selling_price' => 'Precio Base',
      'sale_price' => 'Precio Venta',
      'doc_sale_price' => 'Precio Venta',
      'down_payment' => 'A Cuenta',
      'comment' => 'Comentario',
      'warranty_years' => 'Años de Garantía',
      'warranty_km' => 'Kilometraje de Garantía',
      'opportunity_id' => 'Oportunidad',
      'holder_id' => 'Titular',
      'vehicle_color_id' => 'Color del Vehículo',
      'ap_models_vn_id' => 'Modelo del Vehículo',
      'doc_type_currency_id' => 'Moneda',
      'ap_vehicle_id' => 'Vehículo',
      'with_vin' => 'Con VIN',
      'bonus_discounts' => 'Descuentos',
      'bonus_discounts.*.concept_id' => 'Concepto',
      'bonus_discounts.*.description' => 'Descripción del Descuento',
      'bonus_discounts.*.type' => 'Tipo de Descuento',
      'bonus_discounts.*.value' => 'Valor del Descuento',
      'bonus_discounts.*.is_negative' => '¿Es un descuento negativo?',
      'accessories' => 'Accesorios',
      'accessories.*.accessory_id' => 'Accesorio',
      'accessories.*.quantity' => 'Cantidad del Accesorio',
      'accessories.*.additional_price' => 'Precio Adicional del Accesorio',
      'type_currency_id' => 'Tipo de Moneda',
      'sede_id' => 'Sede'
    ];
  }
}
