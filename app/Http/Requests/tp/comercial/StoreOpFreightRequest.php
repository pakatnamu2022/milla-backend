<?php

namespace App\Http\Requests\tp\comercial;

use Illuminate\Foundation\Http\FormRequest;

class StoreOpFreightRequest extends FormRequest
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
    public function messages()
    {
        return [
            'customer.required' => 'El cliente es requerido',
            'customer.exists' => 'El cliente seleccionado no existe',
            'startPoint.required' => 'El origen es requerido',
            'startPoint.exists' => 'El origen seleccionado no existe',
            'endPoint.required' => 'El destino es requerido',
            'endPoint.exists' => 'El destino seleccionado no existe',
            'tipo_flete.required' => 'El tipo de flete es requerido',
            'tipo_flete.in' => 'El tipo de flete no es válido',
            'freight.required' => 'El flete es requerido',
            'freight.numeric' => 'El flete debe ser un número',
            'freight.min' => 'El flete debe ser mayor o igual a 0',
        ];
    }
        

        

}
