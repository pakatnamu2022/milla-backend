<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vista_id' => 'nullable|integer|exists:config_vista,id',
            'title'    => 'nullable|string|max:255',
            'description'  => 'nullable|string|max:500',
            'order'        => 'nullable|integer|min:0',
            'file'         => 'nullable|file|mimetypes:text/plain,text/markdown,text/x-markdown|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'El archivo debe ser un archivo Markdown (.md).',
            'file.max'   => 'El archivo no puede superar los 10 MB.',
        ];
    }
}
