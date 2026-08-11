<?php

namespace App\Http\Resources\ap\comercial;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApFamiliesResource;
use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityResource extends JsonResource
{
  protected bool $showExtra = false;

  public function showExtra($show = true): static
  {
    $this->showExtra = $show;
    return $this;
  }

  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    $response = [
      'id'                         => $this->id,
      'worker_id'                  => $this->worker_id,
      'client_id'                  => $this->client_id,
      'family_id'                  => $this->family_id,
      'opportunity_type_id'        => $this->opportunity_type_id,
      'client_status_id'           => $this->client_status_id,
      'opportunity_status_id'      => $this->opportunity_status_id,
      'is_closed'                  => $this->is_closed,
      'comment'                    => $this->comment,
      'has_purchase_request_quote' => $this->has_purchase_request_quote,

      // Relaciones
      'worker'                     => new WorkerResource($this->worker),
      'client'                     => new BusinessPartnersResource($this->client),
      'family'                     => new ApFamiliesResource($this->family),
      'opportunity_type'           => $this->opportunityType?->description,
      'client_status'              => $this->clientStatus?->description,
      'opportunity_status'         => $this->opportunityStatus?->description,
      'actions'                    => OpportunityActionResource::collection($this->actions),
      'lead'                       => new PotentialBuyersResource($this->lead),
      'created_at'                 => $this->created_at,
    ];

    if ($this->showExtra) {
      $response['purchaseRequestsQuote'] = $this->purchaseRequestsQuote ? new PurchaseRequestQuoteResource($this->purchaseRequestsQuote) : null;
    }
    return $response;
  }
}
