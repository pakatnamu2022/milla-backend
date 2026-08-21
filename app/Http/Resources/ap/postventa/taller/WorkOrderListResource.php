<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Models\ap\postventa\DiscountRequestsWorkOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * WorkOrderListResource - Resource optimizado para listados
 *
 * Este resource contiene SOLO los campos necesarios para la vista de lista.
 * Reducción de datos: ~85-90% comparado con WorkOrderResource completo.
 *
 * Para replicar en otros modelos:
 * 1. Identificar campos usados en el frontend (tabla + acciones)
 * 2. Crear {Model}ListResource con solo esos campos
 * 3. Optimizar query en Service con select() y with() limitados
 * 4. Verificar índices en BD para campos de filtro
 */
class WorkOrderListResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      // Campos principales
      'id' => $this->id,
      'correlative' => $this->correlative,
      'mileage' => $this->mileage,
      'vehicle_vin' => $this->vehicle?->vin,

      // Fechas
      'opening_date' => $this->opening_date?->format('Y-m-d H:i:s'),
      'estimated_delivery_date' => $this->estimated_delivery_date?->format('Y-m-d H:i:s'),

      // Moneda (solo name y symbol)
      'type_currency' => [
        'name' => $this->typeCurrency?->name,
        'symbol' => $this->typeCurrency?->symbol,
      ],

      // Montos
      'final_amount' => (float)$this->final_amount,

      // Flags booleanos
      'is_guarantee' => (bool)$this->is_guarantee,
      'is_recall' => (bool)$this->is_recall,
      'is_invoiced' => (bool)$this->is_invoiced,
      'is_delivery' => (bool)$this->is_delivery,

      // Nombres de usuarios
      'advisor_name' => $this->advisor?->nombre_completo,
      'delivery_by_name' => $this->deliveryBy?->name,
      'sede_name' => $this->sede?->abreviatura,

      // Descuento gerencial (calculado)
      'has_management_discount' => $this->discountRequests &&
        $this->discountRequests->where('status', DiscountRequestsWorkOrder::STATUS_APPROVED)->isNotEmpty(),

      // Estado
      'status_id' => $this->status_id,
      'is_inspection_completed' => $this->getActiveVehicleInspection() !== null,

      // Vehículo (solo plate)
      'vehicle' => [
        'plate' => $this->vehicle?->plate,
      ],

      // Items (solo el primero con campos específicos)
      'items' => $this->whenLoaded('items', function () {
        return $this->items->take(1)->map(function ($item) {
          return [
            'type_planning' => [
              'description' => $item->typePlanning?->description,
              'validate_receipt' => $item->typePlanning?->validate_receipt,
              'type_document' => $item->typePlanning?->type_document,
            ],
            'type_operation_name' => $item->typeOperation?->description,
            'description' => $item->description,
          ];
        });
      }),

      // Estado (solo id y description)
      'status' => [
        'id' => $this->status?->id,
        'description' => $this->status?->description,
      ],

      // Nota interna (solo number)
      'internal_note' => $this->whenLoaded('internalNote', function () {
        return $this->internalNote ? [
          'number' => $this->internalNote->number,
        ] : null;
      }),
    ];
  }
}
