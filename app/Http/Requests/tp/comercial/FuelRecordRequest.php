<?php

namespace App\Http\Requests\tp\comercial;

use Illuminate\Foundation\Http\FormRequest;

class FuelRecordRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'kmFactor' => 'required|numeric|min:0.1|max:10',
            'notes' => 'nullable|string|max:500',
            'invoice_travel' => 'nullable|string|max:500',
            'documentNumber' => 'nullable|string|max:250',
            'vatNumber' => 'nullable|string|size:11',
        ];
    }
}