<?php

namespace App\Http\Resources\dp\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountReceivableCommentResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'         => $this->id,
      'comment'    => $this->comment,
      'sede_id'    => $this->sede_id,
      'sede'       => $this->whenLoaded('sede', fn() => [
        'id'          => $this->sede->id,
        'localidad'   => $this->sede->localidad,
        'abreviatura' => $this->sede->abreviatura,
      ]),
      'user_id'    => $this->user_id,
      'user'       => $this->whenLoaded('user', fn() => [
        'id'   => $this->user->id,
        'name' => $this->user->name,
        'sede' => $this->user->person->sede->abreviatura,
      ]),
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
    ];
  }
}
