<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class UploadSignedKycDeclarationRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'signed_file' => 'required|file|mimes:pdf|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'signed_file.required' => 'El archivo firmado es obligatorio.',
            'signed_file.file'     => 'El archivo firmado no es válido.',
            'signed_file.mimes'    => 'El archivo firmado debe ser un PDF.',
            'signed_file.max'      => 'El archivo firmado no puede superar los 10 MB.',
        ];
    }
}
