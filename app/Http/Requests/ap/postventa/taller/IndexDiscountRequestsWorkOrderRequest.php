<?php

namespace App\Http\Requests\ap\postventa\taller;

use Illuminate\Foundation\Http\FormRequest;

class IndexDiscountRequestsWorkOrderRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'ap_work_order_id' => 'sometimes|integer',
      'manager_id' => 'sometimes|integer',
      'reviewed_by_id' => 'sometimes|integer',
      'request_date' => 'sometimes|array',
      'request_date.*' => 'sometimes|date',
      'review_date' => 'sometimes|array',
      'review_date.*' => 'sometimes|date',
      'type' => 'sometimes|array',
      'type.*' => 'sometimes|in:GLOBAL,PARTIAL',
      'status' => 'sometimes|array',
      'status.*' => 'sometimes|in:pending,approved,rejected',
      'page' => 'sometimes|integer',
      'per_page' => 'sometimes|integer',
      'sort_by' => 'sometimes|string',
      'sort_order' => 'sometimes|in:asc,desc',
    ];
  }
}