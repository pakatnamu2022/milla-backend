<?php

namespace App\Http\Requests\gp\tics;

use Illuminate\Foundation\Http\FormRequest;

class StorePhoneLineRequest extends FormRequest
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
            'telephone_account_id' => 'required|exists:telephone_account,id',
            'telephone_plan_id' => 'required|exists:telephone_plan,id',
            'line_number' => 'required|string|max:255',
            'status' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ];
    }
}
