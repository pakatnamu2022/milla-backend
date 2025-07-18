<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateEvaluationPeriodRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('gh_evaluation_periods', 'name')->whereNull('deleted_at')->ignore($this->route('period'))
            ],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }
}
