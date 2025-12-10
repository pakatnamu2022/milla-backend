<?php

namespace App\Http\Requests\ap\postventa\taller;

use Illuminate\Foundation\Http\FormRequest;

class IndexApVehicleInspectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string',
            'work_order_id' => 'nullable|integer|exists:ap_work_orders,id',
            'mileage_from' => 'nullable|numeric|min:0',
            'mileage_to' => 'nullable|numeric|min:0',
            'fuel_level_from' => 'nullable|numeric|min:0|max:100',
            'fuel_level_to' => 'nullable|numeric|min:0|max:100',
            'inspected_by' => 'nullable|integer|exists:users,id',
            'sort' => 'nullable|string|in:id,mileage,fuel_level,created_at',
            'order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}