<?php

namespace App\Http\Resources\dp\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountReceivableResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                => $this->id,
      'company'           => $this->company,
      'sede_id'           => $this->sede_id,
      'sede'              => $this->whenLoaded('sede', fn() => [
        'id'          => $this->sede->id,
        'localidad'   => $this->sede->localidad,
        'abreviatura' => $this->sede->abreviatura,
      ]),
      'seller'            => $this->seller,
      'cashier'           => $this->cashier,
      'document_number'   => $this->document_number,
      'client_id'         => $this->client_id,
      'client_name'       => $this->client_name,
      'client_id_real'    => $this->client_id_real,
      'client_name_real'  => $this->client_name_real,
      'document_date'     => $this->document_date?->format('Y-m-d'),
      'document_due_date' => $this->document_due_date?->format('Y-m-d'),
      'due_year'          => $this->due_year,
      'due_month'         => $this->due_month,
      'overdue_days'      => $this->overdue_days,
      'overdue_status'    => $this->overdue_status,
      'currency'          => $this->currency,
      'exchange_rate'     => $this->exchange_rate,
      'amount'            => $this->amount,
      'balance'           => $this->balance,
      'branch'            => $this->branch,
      'observations'      => $this->observations,
      'collection_date'   => $this->collection_date?->format('Y-m-d'),
      'synced_at'         => $this->synced_at?->format('Y-m-d H:i:s'),
      'comments_count'    => $this->whenCounted('comments'),
      'comments'          => AccountReceivableCommentResource::collection($this->whenLoaded('comments')),
      'created_at'        => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'        => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
