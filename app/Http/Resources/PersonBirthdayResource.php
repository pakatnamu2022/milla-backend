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

        // Construimos el texto personalizado
        if ($days === 0) {
            $diffText = 'hoy';
        } elseif ($days === 1) {
            $diffText = 'mañana';
        } else {
            $diffText = Carbon::now()
                ->addDays($days)
                ->longRelativeToNowDiffForHumans(['parts' => 2, 'syntax' => Carbon::DIFF_RELATIVE_TO_NOW]);
        }

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
