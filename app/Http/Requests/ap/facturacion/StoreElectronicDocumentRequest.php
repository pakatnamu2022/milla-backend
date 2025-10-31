<?php

namespace App\Http\Requests\ap\facturacion;

use App\Http\Requests\StoreRequest;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Validation\Rule;

class StoreElectronicDocumentRequest extends StoreRequest
{
  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    $userId = $this->user()->id;
    return [
      // Tipo de documento y serie
      'sunat_concept_document_type_id' => [
        'required',
        'integer',
        Rule::exists('sunat_concepts', 'id')
          ->where('type', SunatConcepts::BILLING_DOCUMENT_TYPE)
          ->whereNull('deleted_at')->where('active')
      ],
      'series' => [
        'required',
        'integer',
        Rule::exists('assign_sales_series', 'id')
          ->where('status', 1)->whereNull('deleted_at'),
        Rule::exists('user_series_assignment', 'voucher_id')
          ->where('worker_id', $userId)
      ],

      // Tipo de operación
      'sunat_concept_transaction_type_id' => 'required|integer|exists:sunat_concepts,id',

      // Origen del documento
      'origin_module' => ['required', Rule::in(['comercial', 'posventa'])],
      'origin_entity_type' => 'nullable|string|max:100',
      'origin_entity_id' => 'nullable|integer',
      'ap_vehicle_movement_id' => 'nullable|integer|exists:ap_vehicle_movement,id',

      // Datos del cliente
      'sunat_concept_identity_document_type_id' => 'required|integer|exists:sunat_concepts,id',
      'cliente_numero_de_documento' => 'required|string|max:15',
      'cliente_denominacion' => 'required|string|max:100',
      'cliente_direccion' => 'nullable|string|max:250',
      'cliente_email' => 'nullable|email|max:250',
      'cliente_email_1' => 'nullable|email|max:250',
      'cliente_email_2' => 'nullable|email|max:250',

      // Fechas
      'fecha_de_emision' => 'required|date',
      'fecha_de_vencimiento' => 'nullable|date|after:fecha_de_emision',

      // Moneda
      'sunat_concept_currency_id' => 'required|integer|exists:sunat_concepts,id',
      'tipo_de_cambio' => 'nullable|numeric|min:0|max:999.999',
      'porcentaje_de_igv' => 'required|numeric|min:0|max:99.99',

      // Totales
      'descuento_global' => 'nullable|numeric|min:0',
      'total_descuento' => 'nullable|numeric|min:0',
      'total_anticipo' => 'nullable|numeric|min:0',
      'total_gravada' => 'nullable|numeric|min:0',
      'total_inafecta' => 'nullable|numeric|min:0',
      'total_exonerada' => 'nullable|numeric|min:0',
      'total_igv' => 'nullable|numeric|min:0',
      'total_gratuita' => 'nullable|numeric|min:0',
      'total_otros_cargos' => 'nullable|numeric|min:0',
      'total_isc' => 'nullable|numeric|min:0',
      'total' => 'required|numeric|min:0',

      // Percepción
      'percepcion_tipo' => 'nullable|integer|between:1,3',
      'percepcion_base_imponible' => 'nullable|numeric|min:0',
      'total_percepcion' => 'nullable|numeric|min:0',
      'total_incluido_percepcion' => 'nullable|numeric|min:0',

      // Retención
      'retencion_tipo' => 'nullable|integer|between:1,2',
      'retencion_base_imponible' => 'nullable|numeric|min:0',
      'total_retencion' => 'nullable|numeric|min:0',

      // Detracción
      'detraccion' => 'nullable|boolean',
      'sunat_concept_detraction_type_id' => 'nullable|integer|exists:sunat_concepts,id',
      'detraccion_total' => 'nullable|numeric|min:0',
      'detraccion_porcentaje' => 'nullable|numeric|min:0|max:100',
      'medio_de_pago_detraccion' => 'nullable|integer|between:1,12',

      // Notas de crédito/débito
      'documento_que_se_modifica_tipo' => 'nullable|integer|between:1,2',
      'documento_que_se_modifica_serie' => 'nullable|string|size:4',
      'documento_que_se_modifica_numero' => 'nullable|integer|min:1',
      'sunat_concept_credit_note_type_id' => 'nullable|integer|exists:sunat_concepts,id',
      'sunat_concept_debit_note_type_id' => 'nullable|integer|exists:sunat_concepts,id',

      // Campos opcionales
      'observaciones' => 'nullable|string|max:1000',
      'condiciones_de_pago' => 'nullable|string|max:250',
      'medio_de_pago' => 'nullable|string|max:250',
      'placa_vehiculo' => 'nullable|string|max:8',
      'orden_compra_servicio' => 'nullable|string|max:20',
      'codigo_unico' => 'nullable|string|max:20',

      // Configuración
      'enviar_automaticamente_a_la_sunat' => 'nullable|boolean',
      'enviar_automaticamente_al_cliente' => 'nullable|boolean',
      'generado_por_contingencia' => 'nullable|boolean',

      // Items (obligatorios)
      'items' => 'required|array|min:1',
      'items.*.unidad_de_medida' => 'required|string|max:3',
      'items.*.codigo' => 'nullable|string|max:30',
      'items.*.codigo_producto_sunat' => 'nullable|string|max:8',
      'items.*.descripcion' => 'required|string|max:250',
      'items.*.cantidad' => 'required|numeric|min:0.0000000001',
      'items.*.valor_unitario' => 'required|numeric|min:0',
      'items.*.precio_unitario' => 'required|numeric|min:0',
      'items.*.descuento' => 'nullable|numeric|min:0',
      'items.*.subtotal' => 'required|numeric|min:0',
      'items.*.sunat_concept_igv_type_id' => 'required|integer|exists:sunat_concepts,id',
      'items.*.igv' => 'required|numeric|min:0',
      'items.*.total' => 'required|numeric|min:0',
      'items.*.anticipo_regularizacion' => 'nullable|boolean',
      'items.*.anticipo_documento_serie' => 'nullable|string|size:4',
      'items.*.anticipo_documento_numero' => 'nullable|integer|min:1',

      // Guías (opcionales)
      'guias' => 'nullable|array',
      'guias.*.guia_tipo' => 'required|integer|between:1,2',
      'guias.*.guia_serie_numero' => 'required|string|max:20',

      // Cuotas para venta al crédito (opcionales)
      'venta_al_credito' => 'nullable|array',
      'venta_al_credito.*.cuota' => 'required|integer|min:1',
      'venta_al_credito.*.fecha_de_pago' => 'required|date',
      'venta_al_credito.*.importe' => 'required|numeric|min:0',
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'sunat_concept_document_type_id.required' => 'El tipo de documento es obligatorio',
      'sunat_concept_document_type_id.exists' => 'El tipo de documento seleccionado no es válido',
      'serie.required' => 'La serie es obligatoria',
      'serie.size' => 'La serie debe tener exactamente 4 caracteres',
      'cliente_numero_de_documento.required' => 'El número de documento del cliente es obligatorio',
      'cliente_denominacion.required' => 'El nombre o razón social del cliente es obligatorio',
      'fecha_de_emision.required' => 'La fecha de emisión es obligatoria',
      'total.required' => 'El total del documento es obligatorio',
      'items.required' => 'Debe agregar al menos un item al documento',
      'items.min' => 'Debe agregar al menos un item al documento',
      'items.*.descripcion.required' => 'La descripción del item es obligatoria',
      'items.*.cantidad.required' => 'La cantidad del item es obligatoria',
      'items.*.cantidad.min' => 'La cantidad debe ser mayor a 0',
    ];
  }

  /**
   * Configure the validator instance.
   */
  public function withValidator($validator)
  {
    $validator->after(function ($validator) {
      // Validar que la serie corresponda al tipo de documento
      if ($this->has('sunat_concept_document_type_id') && $this->has('serie')) {
        $documentTypeId = $this->input('sunat_concept_document_type_id');
        $serie = $this->input('serie');
        $prefix = substr($serie, 0, 1);

        $validations = [
          SunatConcepts::ID_FACTURA_ELECTRONICA => 'F', // Factura Electrónica
          SunatConcepts::ID_BOLETA_VENTA_ELECTRONICA => 'B', // Boleta de Venta Electrónica
          SunatConcepts::ID_NOTA_CREDITO_ELECTRONICA => ['F', 'B'], // Nota de Crédito Electrónica
          SunatConcepts::ID_NOTA_DEBITO_ELECTRONICA => ['F', 'B'], // Nota de Débito Electrónica
        ];

        if (isset($validations[$documentTypeId])) {
          $validPrefixes = (array)$validations[$documentTypeId];
          if (!in_array($prefix, $validPrefixes)) {
            $validator->errors()->add(
              'serie',
              'La serie no corresponde al tipo de documento seleccionado'
            );
          }
        }
      }

      // Validar que si es nota de crédito/débito, tenga el documento que modifica
      if (in_array($this->input('sunat_concept_document_type_id'), [
        SunatConcepts::ID_NOTA_CREDITO_ELECTRONICA,
        SunatConcepts::ID_NOTA_DEBITO_ELECTRONICA
      ])) {
        if (!$this->has('documento_que_se_modifica_tipo') ||
          !$this->has('documento_que_se_modifica_serie') ||
          !$this->has('documento_que_se_modifica_numero')) {
          $validator->errors()->add(
            'documento_que_se_modifica_tipo',
            'Debe especificar el documento que se modifica'
          );
        }

        // Validar tipo de nota
        if ($this->input('sunat_concept_document_type_id') == SunatConcepts::ID_NOTA_CREDITO_ELECTRONICA && !$this->has('sunat_concept_credit_note_type_id')) {
          $validator->errors()->add(
            'sunat_concept_credit_note_type_id',
            'Debe especificar el tipo de nota de crédito'
          );
        }

        if ($this->input('sunat_concept_document_type_id') == SunatConcepts::ID_NOTA_DEBITO_ELECTRONICA && !$this->has('sunat_concept_debit_note_type_id')) {
          $validator->errors()->add(
            'sunat_concept_debit_note_type_id',
            'Debe especificar el tipo de nota de débito'
          );
        }
      }

      // Validar que si hay detracción, tenga todos los campos necesarios
      if ($this->input('detraccion') === true) {
        if (!$this->has('sunat_concept_detraction_type_id')) {
          $validator->errors()->add(
            'sunat_concept_detraction_type_id',
            'Debe especificar el tipo de detracción'
          );
        }
      }

      // Validar estado del vehículo si se proporciona ap_vehicle_movement_id
      if ($this->has('ap_vehicle_movement_id') && $this->input('ap_vehicle_movement_id')) {
        $vehicleMovement = VehicleMovement::with(['vehicle.vehicleStatus', 'vehicle.model'])
          ->find($this->input('ap_vehicle_movement_id'));

        if ($vehicleMovement && $vehicleMovement->vehicle) {
          $vehicle = $vehicleMovement->vehicle;
          $vehicleStatusId = $vehicle->ap_vehicle_status_id;
          $allowedStatuses = [
            ApVehicleStatus::VEHICULO_EN_TRAVESIA,  // Estado 2
            ApVehicleStatus::INVENTARIO_VN,         // Estado 5
          ];

          // Validar estado del vehículo
          if (!in_array($vehicleStatusId, $allowedStatuses)) {
            $currentStatusName = $vehicle->vehicleStatus->description ?? 'Desconocido';
            $validator->errors()->add(
              'ap_vehicle_movement_id',
              "El vehículo debe estar en estado 'En Travesía' o 'Inventario VN' para poder facturarlo. Estado actual: {$currentStatusName}"
            );
          }

          // Validar monto contra precio del modelo del vehículo
          if ($vehicle->model && $this->has('total')) {
            $totalFactura = (float)$this->input('total');
            $precioVenta = (float)$vehicle->model->sale_price;

            // Obtener suma de anticipos previos para este vehículo
            $sumaAnticipos = \DB::table('ap_billing_electronic_documents')
              ->where('origin_module', 'comercial')
              ->where('origin_entity_id', $vehicle->id)
              ->where('sunat_concept_transaction_type_id', 36) // Tipo operación: Anticipos (ID del seeder)
              ->whereNull('deleted_at')
              ->where('anulado', false)
              ->sum('total');

            $totalConAnticipos = $totalFactura + $sumaAnticipos;

            // Validar que no exceda el precio de venta del vehículo
            if ($totalConAnticipos > $precioVenta) {
              $validator->errors()->add(
                'total',
                sprintf(
                  'El total de la factura ($%.2f) más los anticipos previos ($%.2f) excede el precio de venta del vehículo ($%.2f)',
                  $totalFactura,
                  $sumaAnticipos,
                  $precioVenta
                )
              );
            }
          }
        }
      }
    });
  }
}
