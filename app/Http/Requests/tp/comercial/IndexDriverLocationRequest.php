<?php

namespace App\Http\Requests\tp\comercial;

use Illuminate\Foundation\Http\FormRequest;

class IndexDriverLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'driver_id' => 'nullable|integer',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:1|max:100'
        ];
    }
}