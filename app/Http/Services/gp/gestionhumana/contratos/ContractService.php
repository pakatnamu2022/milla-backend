<?php

namespace App\Http\Services\gp\gestionhumana\contratos;

use App\Http\Resources\gp\gestionhumana\contratos\ContractResource;
use App\Http\Services\BaseService;
use App\Http\Services\common\ExportService;
use App\Models\gp\gestionhumana\contratos\Contract;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contratos (F5). CRUD, PDF sin firma y merge de plantilla. El flujo de firma
 * digital X.509 (solicitud, aprobación, firma individual/lote, confirmación de
 * lectura, contratos vencidos) vive en ContractSignatureService, que reutiliza
 * mergeTemplate() de esta clase para generar el PDF que finalmente se firma.
 */
class ContractService extends BaseService
{
  public const RELATIONS = ['worker', 'contractType', 'contractTemplate', 'sede', 'position', 'signer.worker', 'secondarySigner.worker'];

  public function __construct(private ExportService $exportService) {}

  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      Contract::query()->with(self::RELATIONS),
      $request,
      Contract::filters,
      Contract::sorts,
      ContractResource::class,
    );
  }

  public function show(int $id): ContractResource
  {
    return new ContractResource(Contract::with(self::RELATIONS)->findOrFail($id));
  }

  public function store(array $data): ContractResource
  {
    return DB::transaction(function () use ($data) {
      $contract = Contract::create([
        ...$data,
        'write_id'       => auth()->id(),
        'status_deleted' => 1,
      ]);

      return $this->show($contract->id);
    });
  }

  public function update(int $id, array $data): ContractResource
  {
    $contract = Contract::findOrFail($id);
    $contract->update($data);

    return $this->show($contract->id);
  }

  public function destroy(int $id): void
  {
    $contract = Contract::findOrFail($id);
    $contract->update(['status_deleted' => 0]);
  }

  public function export(Request $request)
  {
    return $this->exportService->exportFromRequest($request, Contract::class);
  }

  /**
   * PDF sin firma del contrato, generado on-demand desde la plantilla (igual que el
   * legacy `ContratoController::pdfsinfirma`, que tampoco persiste el archivo).
   */
  public function pdf(int $id): Response
  {
    $contract = Contract::with([...self::RELATIONS, 'parentContract.sede'])->findOrFail($id);

    if (!$contract->contractTemplate) {
      throw new \RuntimeException('El contrato no tiene una plantilla asignada.');
    }

    $content = $this->mergeTemplate($contract->contractTemplate->contenido, $contract);

    $pdf = Pdf::loadView('reports.gp.contratos.contract-pdf', ['content' => $content]);

    return $pdf->stream('contrato_' . $contract->id . '.pdf');
  }

  public function mergeTemplate(string $content, Contract $contract): string
  {
    $worker = $contract->worker;
    $sede = $contract->sede;
    $origenSede = $contract->parentContract?->sede ?? $sede;

    $replacements = [
      '{$empresa}'               => $sede?->razon_social ?? '',
      '{$RucEmpresa}'            => $sede?->ruc ?? '',
      '{$DireccionEmpresa}'      => $sede?->direccion ?? '',
      '{$DistritoEmpresa}'       => $sede?->distrito ?? '',
      '{$ProvinciaEmpresa}'      => $sede?->provincia ?? '',
      '{$DepartamentoEmpresa}'   => $sede?->departamento ?? '',
      '{$InfoEmpresa}'           => $sede?->info_labores ?? '',
      '{$abrev_suc}'             => $sede?->suc_abrev ?? '',

      '{$empresa_origen}'        => $origenSede?->razon_social ?? '',
      '{$RucEmpresa_origen}'     => $origenSede?->ruc ?? '',
      '{$DireccionEmpresa_origen}'    => $origenSede?->direccion ?? '',
      '{$DistritoEmpresa_origen}'     => $origenSede?->distrito ?? '',
      '{$ProvinciaEmpresa_origen}'    => $origenSede?->provincia ?? '',
      '{$DepartamentoEmpresa_origen}' => $origenSede?->departamento ?? '',
      '{$InfoEmpresa_origen}'         => $origenSede?->info_labores ?? '',
      '{$FechaInicio_origen}'         => optional($contract->parentContract?->fecha_inicio_contrato)->format('d/m/Y') ?? '',

      '{$NombreTrabajador}'      => $worker?->nombre_completo ?? '',
      '{$DocTrabajador}'         => $worker?->vat ?? '',
      '{$DireccionTrabajador}'   => $worker?->direccion_principal ?? '',
      '{$DistritoTrabajador}'    => $worker?->distrito ?? '',
      '{$ProvinciaTrabajador}'   => $worker?->provincia ?? '',
      '{$DepartamentoTrabajador}' => $worker?->departamento ?? '',
      '{$EmailTrabajador}'       => $worker?->email ?? '',

      '{$CargoTrabajador}'       => $contract->position?->name ?? '',
      '{$DescCargo}'             => $contract->position?->descripcion ?? '',
      '{$SueldoTrabajador}'      => $contract->sueldo !== null ? number_format((float) $contract->sueldo, 2) : '',
      '{$TipoContrato}'          => $contract->contractType?->descripcion ?? '',
      '{$FechInicioContrato}'    => optional($contract->fecha_inicio_contrato)->format('d/m/Y') ?? '',
      '{$FechFinContrato}'       => optional($contract->fecha_fin_contrato)->format('d/m/Y') ?? '',

      '{$NombreFirmante}'            => $contract->signer?->worker?->nombre_completo ?? '',
      '{$DniFirmante}'               => $contract->signer?->worker?->vat ?? '',
      '{$NombreFirmanteSecundario}'  => $contract->secondarySigner?->worker?->nombre_completo ?? '',
      '{$DniFirmanteSecundario}'     => $contract->secondarySigner?->worker?->vat ?? '',
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $content);
  }
}
