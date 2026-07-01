<?php

namespace App\Http\Resources\gp\gestionsistema;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActiveSessionResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $onlineThreshold = now()->subMinutes(15);
    $lastSeen = $this->last_seen_at ? Carbon::parse($this->last_seen_at) : null;
    $loginAt  = $this->login_at    ? Carbon::parse($this->login_at)     : null;
    $isOnline = $lastSeen && $lastSeen->gte($onlineThreshold);

    return [
      'user_id'        => $this->id,
      'username'       => $this->username,
      'name'           => $this->person?->nombre_completo ?? $this->name,
      'cargo'          => $this->person?->position?->name,
      'sede'           => $this->person?->sede?->suc_abrev,
      'empresa'        => strtolower($this->person?->sede?->company?->abbreviation ?? ''),
      'login_at'       => $loginAt?->toDateTimeString(),
      'last_seen_at'   => $lastSeen?->toDateTimeString(),
      'active_minutes' => $lastSeen && $loginAt ? (int) $loginAt->diffInMinutes($lastSeen) : 0,
      'session_count'  => (int) $this->session_count,
      'status'         => $isOnline ? 'online' : 'inactive',
    ];
  }
}
