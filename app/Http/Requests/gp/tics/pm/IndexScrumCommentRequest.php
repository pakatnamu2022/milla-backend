<?php

namespace App\Http\Requests\gp\tics\pm;

use Illuminate\Foundation\Http\FormRequest;

class IndexScrumCommentRequest extends FormRequest
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
            'item_id' => 'required|integer|exists:scrum_items,id',
        ];
    }
}
