<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;

class UpdateApFamilyImageRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'image' => [
        'required',
        'file',
        'mimes:jpeg,png,webp,jpg',
        'max:4096',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'image.required' => 'Debe seleccionar una imagen.',
      'image.file' => 'La imagen debe ser un archivo.',
      'image.mimes' => 'La imagen debe ser un archivo JPG, PNG o WebP.',
      'image.max' => 'La imagen no debe superar los 4MB.',
    ];
  }
}
