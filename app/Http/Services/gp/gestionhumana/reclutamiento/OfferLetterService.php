<?php

namespace App\Http\Services\gp\gestionhumana\reclutamiento;

use App\Http\Services\common\EmailService;
use App\Models\gp\gestionhumana\reclutamiento\Applicant;
use App\Models\gp\gestionhumana\reclutamiento\OfferLetterTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Genera y envia la carta oferta al marcar SELECCIONADO (Etapa 3 del plan F2).
 * Decision de negocio #3: la carta la genera el sistema desde plantilla (no se sube manual).
 * El PDF se guarda en el disco `private` (igual que CV/foto del postulante, ver ApplicantService).
 */
class OfferLetterService
{
  public function generateAndSend(Applicant $worker): void
  {
    $template = OfferLetterTemplate::query()->first();
    if (!$template) {
      throw new \RuntimeException('No hay una plantilla de carta oferta configurada (config_mail_carta).');
    }

    $body = $this->mergeTemplate($template->contenido, $worker);
    $subject = $template->asunto ?: 'Carta oferta';

    $pdf = Pdf::loadView('reports.gp.reclutamiento.offer-letter', [
      'subject' => $subject,
      'body'    => $body,
    ]);

    $path = 'resources_cartaoferta/' . $worker->id;
    $filename = 'carta_oferta_' . $worker->id . '_' . time() . '.pdf';
    Storage::disk('private')->put($path . '/' . $filename, $pdf->output());
    $relativePath = $path . '/' . $filename;

    $worker->carta_oferta = $relativePath;
    $worker->status_carta_oferta_id = 20; // config_status: PENDIENTE (tipo_8)
    $worker->save();

    $this->sendEmail($worker, $subject, $body, Storage::disk('private')->path($relativePath));
  }

  private function mergeTemplate(string $content, Applicant $worker): string
  {
    $content = str_replace('{$cargo}', '<strong>' . ($worker->position?->name ?? '') . '</strong>', $content);
    $content = str_replace('{$area}', '<strong>' . ($worker->area?->name ?? '') . '</strong>', $content);
    $content = str_replace('{$sede}', '<strong>' . ($worker->sede?->abreviatura ?? '') . '</strong>', $content);
    $content = str_replace('{$postulante}', '<strong>' . $worker->nombre_completo . '</strong>', $content);

    return $content;
  }

  private function sendEmail(Applicant $worker, string $subject, string $body, string $cartaPath): void
  {
    if (!$worker->email) {
      return;
    }

    $attachments = [
      ['path' => $cartaPath, 'name' => 'Carta_Oferta.pdf', 'mime' => 'application/pdf'],
    ];

    $sede = $worker->sede;
    if ($sede) {
      for ($i = 1; $i <= 8; $i++) {
        $docField = "doc{$i}_rrhh";
        if (!empty($sede->$docField) && Storage::disk('private')->exists($sede->$docField)) {
          $attachments[] = [
            'path' => Storage::disk('private')->path($sede->$docField),
            'name' => basename($sede->$docField),
          ];
        }
      }
    }

    (new EmailService())->send([
      'to'          => [$worker->email],
      'cc'          => ['soporte@grupopakatnamu.com'],
      'subject'     => $subject,
      'template'    => 'emails.reclutamiento-notification',
      'attachments' => $attachments,
      'data'        => [
        'title'     => $subject,
        'body_html' => $body,
      ],
    ]);
  }
}
