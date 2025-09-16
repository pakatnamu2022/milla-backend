<?php

namespace App\Http\Resources\ap\maestroGeneral;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignSalesSeriesResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'series' => $this->series,
      'correlative_start' => $this->correlative_start,
      'type_receipt_id' => $this->type_receipt_id,
      'type_receipt' => $this->typeReceipt->description,
      'type_operation_id' => $this->type_operation_id,
      'type_operation' => $this->typeOperation?->description,
      'sede_id' => $this->sede_id,
      'sede' => $this->sede->abreviatura,
      'status' => $this->status,
    ];
  }
}
