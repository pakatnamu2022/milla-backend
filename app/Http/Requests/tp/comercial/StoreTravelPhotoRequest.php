<?php

namespace App\Http\Requests\tp\comercial;

use Illuminate\Foundation\Http\FormRequest;


class StoreTravelPhotoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'photo' => [
                'required',
                'string',
                function($attribute, $value, $fail){
                    if(!preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/', $value)){
                        $fail('El formato de imagen no es valido. Use JPEG, PNG, GIF o webP en base64.');
                    }

                    $base64 = explode(',', $value)[1] ?? $value;
                    $size = (int) (strlen($base64) * 3 / 4);

                    if($size > 5 * 1024 * 1024){
                        $fail('La imagen es demasiado grande. Maximo 5MB');
                    }
                }
            ],
            'photo_type' => 'required|in:start,end,fuel,incident,invoice',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string|max:1000'
        ];
    }

    public function messages(){
        return [
            'photo.required' => 'La foto es requerida',
            'photo_type.required' => 'El tipo de foto es requerido',
            'photo_type.in' => 'Tipo de foto no válido. Use: inicio, fin, combustible, incidente o comprobante',
            'latitude.between' => 'La latitud debe estar entre -90 y 90 grados',
            'longitude.between' => 'La longitud debe estar entre -180 y 180 grados'
        ];
    }
}