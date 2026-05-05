<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRuleResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'code' => $this->code,
      'description' => $this->description,
      'hour_type' => $this->hour_type,
      'hours' => $this->hours,
      'multiplier' => (float)$this->multiplier,
      'pay' => (bool)$this->pay,
      'use_shift' => (bool)$this->use_shift,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
