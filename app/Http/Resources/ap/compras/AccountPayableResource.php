<?php

namespace App\Http\Resources\ap\compras;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountPayableResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                  => $this->id,
      'company'             => $this->company,
      'documento'           => $this->documento,
      'proveedor_documento' => $this->proveedor_documento,
      'proveedor_nombre'    => $this->proveedor_nombre,
      'fecha_documento'     => $this->fecha_documento?->format('Y-m-d'),
      'fecha_contable'      => $this->fecha_contable?->format('Y-m-d'),
      'moneda'              => $this->moneda,
      'monto'               => $this->monto,
      'monto_sin_aplicar'   => $this->monto_sin_aplicar,
      'synced_at'           => $this->synced_at?->format('Y-m-d H:i:s'),
      'comments_count'      => $this->whenCounted('comments'),
      'comments'            => AccountPayableCommentResource::collection($this->whenLoaded('comments')),
      'created_at'          => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'          => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
