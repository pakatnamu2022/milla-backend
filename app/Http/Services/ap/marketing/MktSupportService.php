<?php

namespace App\Http\Services\ap\marketing;

use App\Http\Resources\ap\marketing\MktSupportResource;
use App\Http\Services\ap\marketing\Concerns\ConvertsToUsd;
use App\Http\Services\ap\marketing\Concerns\NormalizesUppercaseText;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Models\ap\marketing\MktActivity;
use App\Models\ap\marketing\MktPurchaseOrder;
use App\Models\ap\marketing\MktSupport;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class MktSupportService extends BaseService implements BaseServiceInterface
{
  use NormalizesUppercaseText;
  use ConvertsToUsd;

  const UPPERCASE_FIELDS = ['document_series', 'document_number', 'notes'];

  /** Máximo de imágenes/archivos que puede tener un sustento. */
  const MAX_FILES = 10;

  protected DigitalFileService $digitalFileService;

  public function __construct(DigitalFileService $digitalFileService)
  {
    $this->digitalFileService = $digitalFileService;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      MktSupport::query()->with(['activity:id,name', 'purchaseOrder:id,number,status', 'currency:id,name,code,symbol', 'supplier:id,full_name']),
      $request,
      MktSupport::filters,
      MktSupport::sorts,
      MktSupportResource::class
    );
  }

  public function find(int $id): MktSupport
  {
    $support = MktSupport::with(['activity', 'purchaseOrder', 'supplier', 'currency', 'digitalFiles'])->find($id);
    if (!$support) {
      throw new Exception('Soporte no encontrado');
    }
    return $support;
  }

  /**
   * @param array           $data  Campos del sustento (sin `files`/`file`).
   * @param UploadedFile[]  $files Archivos nuevos a adjuntar.
   */
  public function store(mixed $data, array $files = []): MktSupportResource
  {
    $data = $this->normalizeUpperFields($data, self::UPPERCASE_FIELDS);
    $this->assertWithinBudget($data);

    DB::beginTransaction();
    try {
      $support = MktSupport::create($data);
      $this->attachFiles($support, $files);
      $support->load(['activity', 'purchaseOrder', 'supplier', 'currency', 'digitalFiles']);
      DB::commit();
      return new MktSupportResource($support);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show(int $id): MktSupportResource
  {
    return new MktSupportResource($this->find($id));
  }

  /**
   * @param array          $data  Campos del sustento (debe incluir `id`).
   * @param UploadedFile[] $files Archivos nuevos a adjuntar (se suman a los ya existentes).
   */
  public function update(mixed $data, array $files = []): MktSupportResource
  {
    $data = $this->normalizeUpperFields($data, self::UPPERCASE_FIELDS);
    $support = $this->find($data['id']);
    $this->assertWithinBudget($data, $support->id);

    if ($files && $support->digitalFiles()->count() + count($files) > self::MAX_FILES) {
      throw new Exception('Un sustento admite un máximo de ' . self::MAX_FILES . ' archivos.');
    }

    DB::beginTransaction();
    try {
      $support->update($data);
      $this->attachFiles($support, $files);
      $support->load(['activity', 'purchaseOrder', 'supplier', 'currency', 'digitalFiles']);
      DB::commit();
      return new MktSupportResource($support);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /** Elimina una imagen/archivo puntual de un sustento. */
  public function deleteFile(int $supportId, int $fileId): MktSupportResource
  {
    $support = $this->find($supportId);
    $file    = $support->digitalFiles()->where('id', $fileId)->first();

    if (!$file) {
      throw new Exception('El archivo no pertenece a este sustento');
    }

    DB::beginTransaction();
    try {
      $this->digitalFileService->destroy($fileId);
      $support->load(['activity', 'purchaseOrder', 'supplier', 'currency', 'digitalFiles']);
      DB::commit();
      return new MktSupportResource($support);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy(int $id): array
  {
    DB::beginTransaction();
    try {
      $support = $this->find($id);
      if ($support->purchase_order_id) {
        throw new Exception('No se puede eliminar un sustento vinculado a una orden de compra');
      }
      foreach ($support->digitalFiles as $file) {
        $this->digitalFileService->destroy($file->id);
      }
      $support->delete();
      DB::commit();
      return ['message' => 'Soporte eliminado correctamente'];
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /** @param UploadedFile[] $files */
  private function attachFiles(MktSupport $support, array $files): void
  {
    foreach ($files as $file) {
      if (!$file instanceof UploadedFile) {
        continue;
      }
      $fileName = '/ap/marketing/supports/' . time() . '_' . $file->getClientOriginalName();
      $url      = $this->digitalFileService->uploadImage($file, $fileName, 'public');

      $support->digitalFiles()->create([
        'name'     => $fileName,
        'url'      => $url,
        'mimeType' => $file->getClientMimeType(),
        'model'    => get_class($support),
        'id_model' => $support->id,
      ]);
    }
  }

  /**
   * Valida que el sustento no haga que la suma de sustentos + órdenes de compra
   * de las actividades del mismo presupuesto (regular o adicional) supere su
   * `amount_estimated`. Solo aplica cuando el sustento está ligado a una
   * actividad (no cuando está ligado directamente a una orden de compra).
   */
  private function assertWithinBudget(array $data, ?int $excludeSupportId = null): void
  {
    if (empty($data['activity_id']) || !isset($data['amount'])) {
      return;
    }

    $activity = MktActivity::with('budget')->find($data['activity_id']);
    if (!$activity || !$activity->budget) {
      return;
    }
    $budget = $activity->budget;

    $activityIds = MktActivity::where('budget_id', $budget->id)->pluck('id');

    $spentUsd = 0.0;

    $supportsQuery = MktSupport::whereIn('activity_id', $activityIds);
    if ($excludeSupportId) {
      $supportsQuery->where('id', '!=', $excludeSupportId);
    }
    foreach ($supportsQuery->get(['amount', 'currency_id', 'issue_date']) as $support) {
      $spentUsd += $this->toUsd((float) $support->amount, $support->currency_id, $support->issue_date?->toDateString());
    }

    foreach (MktPurchaseOrder::whereIn('activity_id', $activityIds)->get(['amount', 'currency_id', 'issue_date']) as $order) {
      $spentUsd += $this->toUsd((float) $order->amount, $order->currency_id, $order->issue_date?->toDateString());
    }

    $budgetUsd    = $this->toUsd((float) $budget->amount_estimated, $budget->currency_id);
    $availableUsd = $budgetUsd - $spentUsd;

    $newAmountUsd = $this->toUsd((float) $data['amount'], $data['currency_id'] ?? null, $data['issue_date'] ?? null);

    // Pequeño margen para evitar falsos positivos por redondeo de decimales.
    if ($newAmountUsd > $availableUsd + 0.01) {
      throw new Exception(sprintf(
        'El monto del sustento (USD %.2f aprox.) supera el presupuesto disponible de la actividad (USD %.2f disponible de USD %.2f).',
        $newAmountUsd,
        max($availableUsd, 0),
        $budgetUsd
      ));
    }
  }
}
