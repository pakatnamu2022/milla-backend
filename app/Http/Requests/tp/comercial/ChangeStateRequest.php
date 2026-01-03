<?php

namespace App\Http\Requests\tp\comercial;

use Illuminate\Foundation\Http\FormRequest;

class ChangeStateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'estado' => 'required|in:pending,in_progress,fuel_pending,completed',
            'observacion' => 'nullable|string|max:500'
        ];
    }
}