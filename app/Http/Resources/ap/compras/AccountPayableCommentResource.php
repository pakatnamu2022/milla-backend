<?php

namespace App\Http\Resources\ap\compras;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountPayableCommentResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'         => $this->id,
      'comment'    => $this->comment,
      'user_id'    => $this->user_id,
      'user'       => $this->whenLoaded('user', fn() => [
        'id'   => $this->user->id,
        'name' => $this->user->name,
        'sede' => $this->user?->person?->sede?->abreviatura ?? null,
      ]),
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
    ];
  }
}
