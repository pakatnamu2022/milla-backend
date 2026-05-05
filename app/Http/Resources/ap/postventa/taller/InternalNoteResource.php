<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Http\Resources\ap\facturacion\ElectronicDocumentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InternalNoteResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'number' => $this->number,
      'work_order_id' => $this->work_order_id,
      'created_date' => $this->created_date?->format('Y-m-d'),
      'closed_date' => $this->closed_date?->format('Y-m-d'),
      'status' => $this->status,
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

      // Loaded Relationships
      //'work_order' => new WorkOrderResource($this->whenLoaded('workOrder')),
      //'electronic_documents' => ElectronicDocumentResource::collection($this->whenLoaded('electronicDocuments')),
    ];
  }
}

