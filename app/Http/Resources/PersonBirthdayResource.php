<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PersonBirthdayResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $days = (int)$this->days_to_birthday;

    $fotoBase64 = null;

    if ($this->foto_adjunto) {
      $path = $this->foto_adjunto;
      if (Storage::disk('general')->exists($path)) {
        $mime = Storage::disk('general')->mimeType($path);
        $content = Storage::disk('general')->get($path);
        $fotoBase64 = "data:$mime;base64," . base64_encode($content);
      }
    }

// Establecer locale a español
    Carbon::setLocale('es');

// Construimos el texto personalizado
    $diffText = Carbon::now()
      ->addDays($days)
      ->calendar(null, [
        'sameDay' => '[Hoy]',
        'nextDay' => '[Mañana]',
        'nextWeek' => 'dddd',
        'lastDay' => '[Ayer]',
        'lastWeek' => '[Último] dddd',
        'sameElse' => 'd [de] MMMM',
      ]);

// Aplicar ucfirst para capitalizar la primera letra
    $diffText = ucfirst($diffText);

    return [
      'id' => $this->id,
      'nombre_completo' => $this->nombre_completo,
      'photo' => $fotoBase64,
      'position' => $this->position->name,
      'days_to_birthday' => $days,
      'fecha_nacimiento' => $diffText,
    ];
  }
}
