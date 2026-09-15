<?php

namespace App\Http\Services\gp\gestionhumana\contratos;

use App\Http\Resources\gp\gestionhumana\contratos\ContractResource;
use App\Http\Services\common\EmailService;
use App\Models\gp\gestionhumana\contratos\Contract;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Flujo de firma digital de contratos (F5, segunda entrega): solicitud de
 * aprobación RRHH, aprobación, firma individual/por lote con certificado X.509
 * (vía PdfDigitalSignatureService, openssl nativo), envío al trabajador y
 * confirmación de lectura. Equivale a los métodos de firma de
 * AdministracionPersonal/ContratoController del legacy.
 */
class ContractSignatureService
{
  private const SIGNED_PDF_DISK_PATH = 'resources_ce/CE%d.pdf';

  public function __construct(
    private EmailService $emailService,
    private ContractService $contractService,
    private PdfDigitalSignatureService $pdfSignatureService,
  ) {}

  public function requestApproval(int $contractId): ContractResource
  {
    $contract = Contract::with(ContractService::RELATIONS)->findOrFail($contractId);

    if (!$contract->contractTemplate) {
      throw new RuntimeException('El contrato no tiene una plantilla asignada.');
    }

    if (!$contract->firmante_id) {
      throw new RuntimeException('El contrato no tiene un firmante asignado.');
    }

    $contract->solicitar_firma = 1;
    $contract->fecha_solicitud = now();
    $contract->save();

    $url = URL::temporarySignedRoute('public.contract.approve', now()->addDays(7), ['contract' => $contract->id]);

    $this->emailService->send([
      'to'       => config('mail.rrhh_contratos'),
      'subject'  => 'Solicitud de aprobación de contrato',
      'template' => 'emails.reclutamiento-notification',
      'data'     => [
        'title'     => 'Solicitud de aprobación de contrato',
        'body_html' => $this->actionEmailBody(
          "Se solicita la aprobación del contrato de <strong>{$contract->worker?->nombre_completo}</strong>.",
          $url,
          'Aprobar contrato'
        ),
      ],
    ]);

    return new ContractResource($contract->fresh(ContractService::RELATIONS));
  }

  public function approveByHr(Contract $contract): ContractResource
  {
    if (!$contract->solicitar_firma) {
      throw new RuntimeException('Este contrato no tiene una solicitud de aprobación pendiente.');
    }

    if ($contract->conformidad_rrhh) {
      throw new RuntimeException('Este contrato ya fue aprobado.');
    }

    $contract->conformidad_rrhh = 1;
    $contract->fecha_aprobacion_rrhh = now();
    $contract->save();

    $contract->load('signer.worker');
    $signerEmail = $contract->signer?->worker?->email;

    if ($signerEmail) {
      $url = URL::temporarySignedRoute('public.contract.sign', now()->addDays(7), ['contract' => $contract->id]);

      $this->emailService->send([
        'to'       => [$signerEmail],
        'subject'  => 'Contrato pendiente de firma',
        'template' => 'emails.reclutamiento-notification',
        'data'     => [
          'title'     => 'Contrato pendiente de firma',
          'body_html' => $this->actionEmailBody(
            "Tiene un contrato pendiente de firma de <strong>{$contract->worker?->nombre_completo}</strong>.",
            $url,
            'Firmar contrato'
          ),
        ],
      ]);
    }

    return new ContractResource($contract->fresh(ContractService::RELATIONS));
  }

  public function sign(Contract $contract): ContractResource
  {
    if (!$contract->conformidad_rrhh) {
      throw new RuntimeException('El contrato aún no ha sido aprobado por RRHH.');
    }

    if ($contract->confirmacion_firmante) {
      throw new RuntimeException('Este contrato ya fue firmado.');
    }

    $contract->load(['contractTemplate', 'signer', 'secondarySigner', 'worker', 'sede', 'position', 'contractType', 'parentContract.sede']);

    if (!$contract->contractTemplate) {
      throw new RuntimeException('El contrato no tiene una plantilla asignada.');
    }

    if (!$contract->signer) {
      throw new RuntimeException('El contrato no tiene un firmante asignado.');
    }

    if (!$contract->signer->file || !$contract->signer->key) {
      throw new RuntimeException('El firmante no tiene certificado/llave configurados.');
    }

    $signedPdf = $this->generateSignedPdf($contract);

    Storage::disk('local')->put($this->signedPdfPath($contract->id), $signedPdf);

    $contract->confirmacion_firmante = 1;
    $contract->fecha_confirmacion_firma = now();
    $contract->save();

    return new ContractResource($contract->fresh(ContractService::RELATIONS));
  }

  /** @return array<int, array{id:int, ok:bool, error?:string}> */
  public function signBatch(string $lote, int $firmanteId): array
  {
    $contracts = Contract::where('lote', $lote)
      ->where('firmante_id', $firmanteId)
      ->where('conformidad_rrhh', 1)
      ->where(function ($q) {
        $q->whereNull('confirmacion_firmante')->orWhere('confirmacion_firmante', 0);
      })
      ->get();

    if ($contracts->isEmpty()) {
      throw new RuntimeException('No hay contratos pendientes de firma para este lote y firmante.');
    }

    $results = [];

    foreach ($contracts as $contract) {
      try {
        $this->sign($contract);
        $results[] = ['id' => $contract->id, 'ok' => true];
      } catch (\Throwable $e) {
        $results[] = ['id' => $contract->id, 'ok' => false, 'error' => $e->getMessage()];
      }
    }

    return $results;
  }

  public function sendToWorker(int $contractId): ContractResource
  {
    $contract = Contract::with(ContractService::RELATIONS)->findOrFail($contractId);

    if (!$contract->confirmacion_firmante) {
      throw new RuntimeException('El contrato aún no ha sido firmado.');
    }

    if (!$contract->worker?->email) {
      throw new RuntimeException('El trabajador no tiene un email registrado.');
    }

    $path = $this->signedPdfPath($contract->id);

    if (!Storage::disk('local')->exists($path)) {
      throw new RuntimeException('No se encontró el PDF firmado del contrato.');
    }

    $readingUrl = URL::temporarySignedRoute('public.contract.confirm-reading', now()->addDays(30), ['contract' => $contract->id]);

    $this->emailService->send([
      'to'          => [$contract->worker->email],
      'cc'          => config('mail.rrhh_contratos'),
      'subject'     => 'Contrato firmado',
      'template'    => 'emails.reclutamiento-notification',
      'data'        => [
        'title'     => 'Su contrato ha sido firmado',
        'body_html' => $this->actionEmailBody(
          'Adjunto encontrará su contrato firmado. Por favor confirme la lectura del mismo.',
          $readingUrl,
          'Confirmar lectura'
        ),
      ],
      'attachments' => [Storage::disk('local')->path($path)],
    ]);

    $contract->estado_envio_email = 1;
    $contract->fecha_envio_email = now();
    $contract->save();

    return new ContractResource($contract->fresh(ContractService::RELATIONS));
  }

  public function confirmReading(Contract $contract): ContractResource
  {
    if (!$contract->estado_envio_email) {
      throw new RuntimeException('Este contrato aún no ha sido enviado al trabajador.');
    }

    if (!$contract->conformidad_lectura) {
      $contract->conformidad_lectura = 1;
      $contract->fecha_lectura = now();
      $contract->save();
    }

    return new ContractResource($contract->fresh(ContractService::RELATIONS));
  }

  public function downloadSigned(int $contractId): BinaryFileResponse
  {
    $contract = Contract::findOrFail($contractId);
    $path = $this->signedPdfPath($contract->id);

    if (!Storage::disk('local')->exists($path)) {
      throw new RuntimeException('No se encontró el PDF firmado del contrato.');
    }

    return Storage::disk('local')->download($path, 'contrato_firmado_' . $contract->id . '.pdf');
  }

  /** Contratos próximos a vencer (equivalente a ContratosVencidosExport / MailContratosVencidos del legacy). */
  public function expiring(int $days = 50): Collection
  {
    return Contract::query()
      ->with(['worker', 'sede'])
      ->whereNotNull('fecha_fin_contrato')
      ->whereRaw('DATEDIFF(fecha_fin_contrato, CURDATE()) <= ?', [$days])
      ->orderBy('fecha_fin_contrato')
      ->get()
      ->map(fn(Contract $c) => [
        'id'                 => $c->id,
        'sede'               => $c->sede?->abreviatura,
        'trabajador'         => $c->worker?->nombre_completo,
        'fecha_fin_contrato' => $c->fecha_fin_contrato,
        'dias'               => now()->startOfDay()->diffInDays($c->fecha_fin_contrato, false),
      ]);
  }

  private function generateSignedPdf(Contract $contract): string
  {
    $content = $this->contractService->mergeTemplate($contract->contractTemplate->contenido, $contract);
    $pdf = Pdf::loadView('reports.gp.contratos.contract-pdf', ['content' => $content]);

    return $this->pdfSignatureService->sign(
      $pdf->output(),
      Storage::disk('local')->path($contract->signer->file),
      Storage::disk('local')->path($contract->signer->key),
      $contract->signer->getDecryptedPassword(),
      [
        'Name'        => 'Grupo Pakatnamu Sac',
        'Location'    => $contract->sede?->abreviatura ?? 'Lambayeque',
        'Reason'      => 'Contrato de trabajo',
        'ContactInfo' => 'http://www.grupopakatnamu.com',
      ]
    );
  }

  private function signedPdfPath(int $contractId): string
  {
    return sprintf(self::SIGNED_PDF_DISK_PATH, $contractId);
  }

  private function actionEmailBody(string $message, string $url, string $buttonLabel): string
  {
    return '<p>' . $message . '</p>'
      . '<p><a href="' . $url . '" style="display:inline-block;padding:10px 20px;background:#1a1a1a;color:#fff;text-decoration:none;border-radius:4px;">' . $buttonLabel . '</a></p>';
  }
}
