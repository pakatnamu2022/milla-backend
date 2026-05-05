<?php

namespace App\Http\Resources\common;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $pivot = $this->whenLoaded('users', fn() => $this->users->first()?->pivot);

    return [
      'id' => $this->id,
      'title' => $this->title,
      'body' => $this->body,
      'type' => $this->type,
      'route' => $this->route,
      'data' => $this->data,
      'read_at' => $pivot?->read_at,
      'is_read' => $pivot?->read_at !== null,
      'created_at' => $this->created_at,
    ];
  }
}
