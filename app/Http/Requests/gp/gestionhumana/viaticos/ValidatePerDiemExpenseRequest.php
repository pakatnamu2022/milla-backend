<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use Illuminate\Foundation\Http\FormRequest;

class ValidatePerDiemExpenseRequest extends FormRequest
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
            // No additional fields required
        ];
    }

    /**
     * Get the validated data with additional computed fields
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        // Set validation fields
        $data['validated'] = true;
        $data['validated_by'] = auth()->id();
        $data['validated_at'] = now();

        return $data;
    }
}
