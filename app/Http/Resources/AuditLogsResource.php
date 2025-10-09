<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogsResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'user_id' => $this->user_id,
      'user_name' => $this->user_name,
      'user_email' => $this->user_email,
      'auditable_type' => $this->auditable_type,
      'auditable_id' => $this->auditable_id,
      'model_name' => $this->model_name,
      'action' => $this->action,
      'action_description' => $this->action_description,
      'old_values' => $this->old_values,
      'new_values' => $this->new_values,
      'changed_fields' => $this->changed_fields,
      'changes_summary' => $this->changes_summary,
      'ip_address' => $this->ip_address,
      'user_agent' => $this->user_agent,
      'url' => $this->url,
      'method' => $this->method,
      'request_data' => $this->request_data,
      'description' => $this->description,
      'metadata' => $this->metadata,
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
    ];
  }
}