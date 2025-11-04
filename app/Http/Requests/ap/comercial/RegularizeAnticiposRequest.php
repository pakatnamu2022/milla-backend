<?php

namespace App\Http\Requests\ap\comercial;

use App\Models\ap\comercial\ApVehicle;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RegularizeAnticiposRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    return [
      'anticipo_ids' => 'required|array|min:1',
      'anticipo_ids.*' => 'required|integer|exists:ap_billing_electronic_documents,id',
      'serie' => 'required|string|size:4',
      'sunat_concept_document_type_id' => 'required|integer|exists:sunat_concepts,id',
      'sunat_concept_identity_document_type_id' => 'required|integer|exists:sunat_concepts,id',
      'cliente_numero_de_documento' => 'required|string',
      'cliente_denominacion' => 'required|string',
      'cliente_direccion' => 'required|string',
      'cliente_email' => 'nullable|email',
      'cliente_email_1' => 'nullable|email',
      'cliente_email_2' => 'nullable|email',
      'fecha_de_emision' => 'required|date_format:Y-m-d',
      'fecha_de_vencimiento' => 'nullable|date_format:Y-m-d',
      'sunat_concept_currency_id' => 'required|integer|exists:sunat_concepts,id',
      'tipo_de_cambio' => 'nullable|numeric|min:0',
      'observaciones' => 'nullable|string|max:1000',
      'condiciones_de_pago' => 'nullable|string|max:250',
      'medio_de_pago' => 'nullable|string|max:250',
      'orden_compra_servicio' => 'nullable|string|max:250',
      'enviar_automaticamente_a_la_sunat' => 'required|boolean',
      'enviar_automaticamente_al_cliente' => 'required|boolean',
    ];
  }

  /**
   * Get custom validation messages
   */
  public function messages(): array
  {
    return [
      'anticipo_ids.required' => 'Debe seleccionar al menos un anticipo para regularizar',
      'anticipo_ids.min' => 'Debe seleccionar al menos un anticipo',
      'anticipo_ids.*.exists' => 'Uno o más anticipos no existen',
      'serie.required' => 'La serie es requerida',
      'serie.size' => 'La serie debe tener exactamente 4 caracteres',
    ];
  }

  /**
   * Configure el validador
   */
  public function withValidator(Validator $validator): void
  {
    $validator->after(function (Validator $validator) {
      $vehicleId = $this->route('id');
      $vehicle = ApVehicle::with('model')->find($vehicleId);

      if (!$vehicle) {
        $validator->errors()->add('vehicle_id', 'El vehículo no existe');
        return;
      }

      if (!$vehicle->model || !$vehicle->model->sale_price) {
        $validator->errors()->add('vehicle', 'El vehículo no tiene un precio de venta definido');
        return;
      }

      // Validar que los anticipos pertenecen al vehículo
      $anticipos = ElectronicDocument::whereIn('id', $this->input('anticipo_ids', []))
        ->get();

      foreach ($anticipos as $anticipo) {
        // Verificar que es un anticipo
        if (!$anticipo->isAnticipo()) {
          $validator->errors()->add('anticipo_ids', "El documento {$anticipo->serie}-{$anticipo->numero} no es un anticipo");
          continue;
        }

        // Verificar que pertenece al vehículo
        if ($anticipo->origin_entity_id != $vehicleId || $anticipo->origin_entity_type != 'vehicle_order') {
          $validator->errors()->add('anticipo_ids', "El anticipo {$anticipo->serie}-{$anticipo->numero} no pertenece a este vehículo");
          continue;
        }

        // Verificar que está aceptado por SUNAT
        if (!$anticipo->aceptada_por_sunat) {
          $validator->errors()->add('anticipo_ids', "El anticipo {$anticipo->serie}-{$anticipo->numero} no ha sido aceptado por SUNAT");
          continue;
        }

        // Verificar que no está anulado
        if ($anticipo->anulado) {
          $validator->errors()->add('anticipo_ids', "El anticipo {$anticipo->serie}-{$anticipo->numero} está anulado");
          continue;
        }

        // Verificar que no ha sido regularizado
        if ($anticipo->isRegularized()) {
          $validator->errors()->add('anticipo_ids', "El anticipo {$anticipo->serie}-{$anticipo->numero} ya ha sido regularizado");
          continue;
        }
      }

      // Validar que la suma de anticipos no excede el precio del vehículo
      $totalAnticipos = $anticipos->sum('total');
      $vehiclePrice = (float) $vehicle->model->sale_price;

      if ($totalAnticipos > $vehiclePrice) {
        $validator->errors()->add('anticipo_ids', sprintf(
          'La suma de los anticipos (S/. %s) excede el precio del vehículo (S/. %s)',
          number_format($totalAnticipos, 2),
          number_format($vehiclePrice, 2)
        ));
      }

      // Validar la serie según el tipo de documento
      $documentTypeId = $this->input('sunat_concept_document_type_id');
      $serie = $this->input('serie');

      if ($documentTypeId && $serie) {
        if (!ElectronicDocument::validateSerie($documentTypeId, $serie)) {
          $prefix = substr($serie, 0, 1);
          $expectedPrefix = match($documentTypeId) {
            SunatConcepts::ID_FACTURA_ELECTRONICA => 'F',
            SunatConcepts::ID_BOLETA_VENTA_ELECTRONICA => 'B',
            default => 'F o B'
          };

          $validator->errors()->add('serie', "La serie '$serie' no es válida para el tipo de documento seleccionado. Debe empezar con '$expectedPrefix'");
        }
      }
    });
  }
}
