<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vista_id' => 'required|integer|exists:config_vista,id',
            'title'    => 'required|string|max:255',
            'description'  => 'nullable|string|max:500',
            'order'        => 'nullable|integer|min:0',
            'file'         => 'required|file|mimetypes:text/plain,text/markdown,text/x-markdown|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'vista_id.required' => 'El módulo es obligatorio.',
            'vista_id.exists'   => 'El módulo seleccionado no existe.',
            'title.required'    => 'El título es obligatorio.',
            'file.required'         => 'El archivo del manual es obligatorio.',
            'file.mimes'            => 'El archivo debe ser un archivo Markdown (.md).',
            'file.max'              => 'El archivo no puede superar los 10 MB.',
        ];
    }
}
