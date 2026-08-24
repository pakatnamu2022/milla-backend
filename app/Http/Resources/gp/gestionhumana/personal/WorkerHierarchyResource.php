<?php

namespace App\Http\Resources\gp\gestionhumana\personal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use function base64_encode;

class WorkerHierarchyResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $photoBase64 = null;

    if ($this->foto_adjunto) {
      $path = $this->foto_adjunto;
      if (Storage::disk('general')->exists($path)) {
        $mime = Storage::disk('general')->mimeType($path);
        $content = Storage::disk('general')->get($path);
        $photoBase64 = "data:$mime;base64," . base64_encode($content);
      }
    }

    return [
      'id' => $this->id,
      'name' => $this->nombre_completo,
      'position' => $this->position?->name,
      'sede' => $this->sede?->abreviatura,
      'photo' => $photoBase64,
      'has_subordinates' => ($this->subordinates_count ?? 0) > 0,
    ];
  }
}
