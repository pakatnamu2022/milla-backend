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
      'amount_pen'        => $this->amount_pen,
      'balance_pen'       => $this->balance_pen,
      'branch'            => $this->branch,
      'observations'      => $this->observations,
      'collection_date'   => $this->collection_date?->format('Y-m-d'),
      'synced_at'              => $this->synced_at?->format('Y-m-d H:i:s'),
      'electronic_document_id' => $this->electronic_document_id,
      'area_id'                => $this->area_id,
      'electronic_document'    => $this->whenLoaded('electronicDocument', fn() => $this->electronicDocument ? [
        'id'                   => $this->electronicDocument->id,
        'full_number'          => $this->electronicDocument->full_number,
        'status'               => $this->electronicDocument->status,
        'fecha_de_emision'     => $this->electronicDocument->fecha_de_emision?->format('Y-m-d'),
        'fecha_de_vencimiento' => $this->electronicDocument->fecha_de_vencimiento?->format('Y-m-d'),
        'total'                => (float)$this->electronicDocument->total,
        'enlace_del_pdf'       => $this->electronicDocument->enlace_del_pdf,
        'aceptada_por_sunat'   => $this->electronicDocument->aceptada_por_sunat,
        'anulado'              => $this->electronicDocument->anulado,
        'items'                => $this->electronicDocument->relationLoaded('items')
          ? $this->electronicDocument->items->map(fn($item) => [
              'descripcion'     => $item->descripcion,
              'cantidad'        => (float)$item->cantidad,
              'precio_unitario' => (float)$item->precio_unitario,
              'total'           => (float)$item->total,
            ])->values()
          : [],
        'installments'         => $this->electronicDocument->relationLoaded('installments')
          ? $this->electronicDocument->installments->map(fn($inst) => [
              'cuota'         => $inst->cuota,
              'fecha_de_pago' => $inst->fecha_de_pago?->format('Y-m-d'),
              'importe'       => (float)$inst->importe,
            ])->values()
          : [],
      ] : null),
      'comments_count'    => $this->whenCounted('comments'),
      'comments'          => AccountReceivableCommentResource::collection($this->whenLoaded('comments')),
      'created_at'        => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'        => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
