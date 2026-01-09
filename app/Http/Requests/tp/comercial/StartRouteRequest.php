<?php

namespace App\Http\Requests\tp\comercial;

use Illuminate\Foundation\Http\FormRequest;

class StartRouteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'mileage' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500'
        ];
    }
}