<?php

namespace App\Http\Resources\gp\gestionsistema;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $fotoBase64 = null;

        if ($this->person->foto_adjunto) {
            $path = $this->person->foto_adjunto;
            if (Storage::disk('general')->exists($path)) {
                $mime = Storage::disk('general')->mimeType($path);
                $content = Storage::disk('general')->get($path);
                $fotoBase64 = "data:$mime;base64," . base64_encode($content);
            }
        }


        return [
            'id' => $this->id,
            'partner_id' => $this->partner_id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->person->email,
            'foto_adjunto' => $fotoBase64,
            'position' => $this->person?->position?->name,
            'empresa' => $this->person?->sede?->razon_social,
            'sede' => $this->person?->sede?->suc_abrev,
            'fecha_ingreso' => $this->person?->fecha_inicio,
            'role' => $this->role?->nombre,
        ];
    }
}
