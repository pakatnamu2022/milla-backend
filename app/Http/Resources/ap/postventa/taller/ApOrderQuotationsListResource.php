<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Models\ap\postventa\DiscountRequestsOrderQuotation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ApOrderQuotationsListResource - Resource optimizado para listados
 *
 * Contiene SOLO los campos necesarios para la vista de lista.
 * Reducción estimada: ~85-90% comparado con ApOrderQuotationsResource completo.
 *
 * Basado en el patrón de optimización documentado en docs/OPTIMIZATION_PATTERN.md
 */
class ApOrderQuotationsListResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      // Campos principales
      'id' => $this->id,
      'quotation_number' => $this->quotation_number,
      'parent_quotation_id' => $this->parent_quotation_id,

      // Flags para badges
      'was_segmented' => $this->whenLoaded('segmentedQuotations', function () {
        return $this->segmentedQuotations->count() > 0;
      }, false),
      'deductible_amount' => (float)$this->deductible_amount,

      // Fechas
      'quotation_date' => $this->quotation_date,
      'expiration_date' => $this->expiration_date,
      'collection_date' => $this->collection_date,

      // Cliente (solo full_name)
      'client' => [
        'full_name' => $this->client?->full_name,
      ],

      // Vehículo (solo plate)
      'vehicle' => [
        'plate' => $this->vehicle?->plate,
      ],

      // Moneda (solo name y symbol)
      'type_currency' => [
        'name' => $this->typeCurrency?->name,
        'symbol' => $this->typeCurrency?->symbol,
      ],

      // Montos
      'total_amount' => (float)$this->total_amount,

      // Observaciones y descarte
      'observations' => $this->observations,
      'discard_reason' => $this->discardReason?->description,
      'discarded_note' => $this->discarded_note,
      'discarded_by_name' => $this->discardedBy?->name,
      'discarded_at' => $this->discarded_at,

      // Flags booleanos
      'is_fully_paid' => (bool)$this->is_fully_paid,
      'has_management_discount' => $this->whenLoaded('discountRequests', function () {
        return $this->discountRequests &&
          $this->discountRequests->where('status', DiscountRequestsOrderQuotation::STATUS_APPROVED)->isNotEmpty();
      }, false),

      // Estado (solo id y description)
      'status' => [
        'id' => $this->status_id,
        'description' => $this->status?->description,
      ],

      // Para acciones
      'delivery_document_number' => $this->delivery_document_number,
    ];
  }
}