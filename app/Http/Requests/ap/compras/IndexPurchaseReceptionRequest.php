<?php

namespace App\Http\Requests\ap\compras;

use App\Http\Requests\IndexRequest;

class IndexPurchaseReceptionRequest extends IndexRequest
{
    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'purchase_order_id' => 'nullable|exists:ap_purchase_order,id',
            'warehouse_id' => 'nullable|exists:warehouse,id',
            'status' => 'nullable|in:PENDING_REVIEW,APPROVED,REJECTED,PARTIAL',
            'reception_type' => 'nullable|in:COMPLETE,PARTIAL',
            'reception_date' => 'nullable|date',
            'received_by' => 'nullable|exists:users,id',
            'reviewed_by' => 'nullable|exists:users,id',
            'sort_by' => 'nullable|in:reception_number,reception_date,total_quantity,created_at',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}