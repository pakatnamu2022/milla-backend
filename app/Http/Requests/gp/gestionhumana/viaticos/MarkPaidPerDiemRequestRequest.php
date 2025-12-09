<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use Illuminate\Foundation\Http\FormRequest;

class MarkPaidPerDiemRequestRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'in:cash,transfer,check'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'payment_date.required' => 'La fecha de pago es requerida.',
            'payment_date.date' => 'La fecha de pago debe ser una fecha válida.',
            'payment_method.required' => 'El método de pago es requerido.',
            'payment_method.in' => 'El método de pago debe ser: efectivo, transferencia o cheque.',
        ];
    }

    /**
     * Get the validated data with additional computed fields
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        // Set paid status and update request status
        $data['paid'] = true;
        $data['status'] = 'in_progress';

        return $data;
    }
}
