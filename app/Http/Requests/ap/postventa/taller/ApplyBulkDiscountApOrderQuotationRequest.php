<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\GeneralMaster;
use App\Models\gp\gestionsistema\Position;

class ApplyBulkDiscountApOrderQuotationRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'type' => [
        'required',
        'string',
        'in:labor,product',
      ],
      'discount_percentage' => [
        'required',
        'numeric',
        'min:0',
        'max:100',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'type.required' => 'El tipo de descuento es obligatorio.',
      'type.in' => 'El tipo de descuento debe ser "labor" o "product".',

      'discount_percentage.required' => 'El porcentaje de descuento es obligatorio.',
      'discount_percentage.numeric' => 'El porcentaje de descuento debe ser numérico.',
      'discount_percentage.min' => 'El porcentaje de descuento no puede ser negativo.',
      'discount_percentage.max' => 'El porcentaje de descuento no puede ser mayor a 100.',
    ];
  }

  public function withValidator($validator): void
  {
    $validator->after(function ($validator) {
      $quotationId = $this->route('id');
      $discountPercentage = $this->input('discount_percentage');

      // Verificar que la cotización existe
      $quotation = ApOrderQuotations::find($quotationId);
      if (!$quotation) {
        $validator->errors()->add('quotation', 'La cotización no existe.');
        return;
      }

      // Obtener el usuario autenticado
      $user = auth()->user();
      $positionId = $user->person?->cargo_id;

      if (!$positionId) {
        $validator->errors()->add('discount_percentage', 'No se pudo determinar el cargo del usuario autenticado.');
        return;
      }

      // Calcular el descuento máximo permitido según el cargo
      $maxDiscountPercentage = $this->getMaxDiscountPercentageByPosition($positionId);

      // Validar que el descuento no exceda el permitido
      if ($discountPercentage > $maxDiscountPercentage) {
        $validator->errors()->add(
          'discount_percentage',
          "El descuento solicitado ({$discountPercentage}%) excede el límite permitido para su cargo ({$maxDiscountPercentage}%)."
        );
      }
    });
  }

  /**
   * Obtener porcentaje de descuento máximo según el cargo
   *
   * @param int|null $positionId
   * @return float
   */
  private function getMaxDiscountPercentageByPosition(?int $positionId): float
  {
    if (!$positionId) {
      return 5.0; // Default 5%
    }

    $generalMasterId = null;

    // Determinar qué general master usar según el cargo
    if (in_array($positionId, Position::POSITION_GERENTE_PV_IDS)) {
      $generalMasterId = GeneralMaster::MANAGER_DISCOUNT_PERCENTAGE_PV_ID;
    } elseif (in_array($positionId, Position::AFTER_SALES_COORDINATOR)) {
      $generalMasterId = GeneralMaster::MANAGER_DISCOUNT_PERCENTAGE_PV_ID;
    } elseif (in_array($positionId, Position::POSITION_JEFE_TALLER_PVT_IDS)) {
      $generalMasterId = GeneralMaster::BOSS_DISCOUNT_PERCENTAGE_PVT_ID;
    } elseif (in_array($positionId, Position::ASESOR_SERVICIO_PV_IDS)) {
      $generalMasterId = GeneralMaster::ADVISOR_DISCOUNT_PERCENTAGE_PV_ID;
    } elseif (in_array($positionId, Position::POSITION_JEFE_REPUESTO_PVT_IDS)) {
      $generalMasterId = GeneralMaster::BOSS_DISCOUNT_PERCENTAGE_PVR_ID;
    }

    // Si no corresponde a ningún cargo, retornar 5%
    if (!$generalMasterId) {
      return 5.0;
    }

    // Buscar el porcentaje en GeneralMaster
    $generalMaster = GeneralMaster::find($generalMasterId);
    $discountPercentage = $generalMaster->value ?? 0.05;

    return (float)$discountPercentage * 100;
  }
}