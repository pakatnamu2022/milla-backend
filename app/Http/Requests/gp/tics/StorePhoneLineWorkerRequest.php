<?php

namespace App\Http\Requests\gp\tics;

use Illuminate\Foundation\Http\FormRequest;

class StorePhoneLineWorkerRequest extends FormRequest
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
            'phone_line_id' => 'required|exists:phone_line,id',
            'worker_id' => 'required|exists:gh_persona,id',
            'assigned_at' => 'nullable|date',
        ];
    }
}
