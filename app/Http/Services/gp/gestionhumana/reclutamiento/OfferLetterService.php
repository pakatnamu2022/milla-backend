<?php

namespace App\Http\Services\gp\gestionhumana\reclutamiento;

use App\Http\Services\common\EmailService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Models\gp\gestionhumana\reclutamiento\Applicant;
use App\Models\gp\gestionhumana\reclutamiento\OfferLetterTemplate;
use App\Models\gp\gestionsistema\DigitalFile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Genera y envia la carta oferta al marcar SELECCIONADO (Etapa 3 del plan F2).
 * Decision de negocio #3: la carta la genera el sistema desde plantilla (no se sube manual).
 * El PDF se guarda via DigitalFileService (S3 + gp_digital_files), igual que el resto de
 * archivos generados/subidos en la aplicacion.
 */
class OfferLetterService
{
  private const FILE_PATH = '/gp/gestionhumana/reclutamiento/carta-oferta/';

  public function __construct(private DigitalFileService $digitalFileService) {}

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
    $content = $pdf->output();
    $filename = 'carta_oferta_' . $worker->id . '_' . time() . '.pdf';

    if ($worker->carta_oferta) {
      $this->deletePreviousFile($worker->carta_oferta);
    }

    $digitalFile = $this->digitalFileService->storeFromContent(
      $content,
      $filename,
      self::FILE_PATH,
      'private',
      'application/pdf',
      $worker->getTable(),
      $worker->id
    );

    $worker->carta_oferta = $digitalFile->url;
    $worker->status_carta_oferta_id = 20; // config_status: PENDIENTE (tipo_8)
    $worker->save();

    $this->sendEmail($worker, $subject, $body, $content);
  }

  private function deletePreviousFile(string $url): void
  {
    $digitalFile = DigitalFile::where('url', $url)->first();
    if ($digitalFile) {
      $this->digitalFileService->destroy($digitalFile->id);
    }
  }

  private function mergeTemplate(string $content, Applicant $worker): string
  {
    $content = str_replace('{$cargo}', '<strong>' . ($worker->position?->name ?? '') . '</strong>', $content);
    $content = str_replace('{$area}', '<strong>' . ($worker->area?->name ?? '') . '</strong>', $content);
    $content = str_replace('{$sede}', '<strong>' . ($worker->sede?->abreviatura ?? '') . '</strong>', $content);
    $content = str_replace('{$postulante}', '<strong>' . $worker->nombre_completo . '</strong>', $content);

    return $content;
  }

  private function sendEmail(Applicant $worker, string $subject, string $body, string $pdfContent): void
  {
    if (!$worker->email) {
      return;
    }

    $attachments = [
      ['content' => $pdfContent, 'name' => 'Carta_Oferta.pdf', 'mime' => 'application/pdf'],
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
