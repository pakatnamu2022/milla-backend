<?php

namespace App\Http\Requests\tp\comercial;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOpFreightRequest extends FormRequest
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
            'customer' => 'required|exists:rrhh_persona,id',
            'startPoint' => 'required|exists:fac_ciudades_sales,id',
            'endPoint' => 'required|exists:fac_ciudades_sales,id',
            'tipo_flete' => 'required|in:TONELADAS,VIAJE,CAJA,PALET,BOLSA',
            'freight' => 'required|numeric|min:0'
        ];
    }
}
