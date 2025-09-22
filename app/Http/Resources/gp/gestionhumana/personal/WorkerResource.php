<?php

namespace App\Http\Resources\gp\gestionhumana\personal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use function base64_encode;

class WorkerResource extends JsonResource
{
  protected $showExtra = false;

  public function showExtra($show = true)
  {
    $this->showExtra = $show;
    return $this;
  }

  public function toArray(Request $request): array
  {
    $fotoBase64 = null;

    $response = [
      'id' => $this->id,
      'name' => $this->nombre_completo,
      'document' => $this->vat,
      'sede' => $this->sede?->abreviatura,
      'position' => $this->position?->name,
      'offerLetterConfirmationId' => $this->status_carta_oferta_id,
      'emailOfferLetterStatusId' => $this->status_envio_mail_carta_oferta,
      'offerLetterConfirmation' => $this->offerLetterStatus?->estado,
      'emailOfferLetterStatus' => $this->emailOfferLetterStatus?->estado,
    ];

    if ($this->showExtra) {
      if ($this->foto_adjunto) {
        $path = $this->foto_adjunto;
        if (Storage::disk('general')->exists($path)) {
          $mime = Storage::disk('general')->mimeType($path);
          $content = Storage::disk('general')->get($path);
          $fotoBase64 = "data:$mime;base64," . base64_encode($content);
        }
        $response['photo'] = $fotoBase64;
      }
    }

    // Agregar campos de diagnóstico si están disponibles
    if (isset($this->inclusion_reason)) {
      $response['inclusion_reason'] = $this->inclusion_reason;
      $response['has_category'] = $this->has_category ?? false;
      $response['has_objectives'] = $this->has_objectives ?? false;
      $response['has_competences'] = $this->has_competences ?? false;
    }

    return $response;
  }
}
