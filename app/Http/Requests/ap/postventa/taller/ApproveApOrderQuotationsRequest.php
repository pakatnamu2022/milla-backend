<?php

namespace App\Http\Requests\ap\postventa\taller;

use Illuminate\Foundation\Http\FormRequest;

class ApproveApOrderQuotationsRequest extends FormRequest
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
            'manager_approval_by' => 'nullable|string|in:Aprobado',
            'chief_approval_by' => 'nullable|string|in:Aprobado',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $managerApproval = $this->input('manager_approval_by');
            $chiefApproval = $this->input('chief_approval_by');

            if ($managerApproval && $chiefApproval) {
                $validator->errors()->add('manager_approval_by', 'Solo puede enviar un tipo de aprobación a la vez.');
            }

            if (!$managerApproval && !$chiefApproval) {
                $validator->errors()->add('manager_approval_by', 'Debe enviar manager_approval_by o chief_approval_by.');
            }
        });
    }
}
