<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\DiscountRequestsOrderQuotationResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\EmailService;
use App\Http\Utils\Constants;
use App\Models\ap\ApMasters;
use App\Models\ap\postventa\DiscountRequestsOrderQuotation;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\GeneralMaster;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\Position;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiscountRequestsOrderQuotationService extends BaseService implements BaseServiceInterface
{
  protected EmailService $emailService;

  public function __construct(EmailService $emailService)
  {
    $this->emailService = $emailService;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      DiscountRequestsOrderQuotation::class,
      $request,
      DiscountRequestsOrderQuotation::filters,
      DiscountRequestsOrderQuotation::sorts,
      DiscountRequestsOrderQuotationResource::class,
    );
  }

  public function find($id): DiscountRequestsOrderQuotation
  {
    $record = DiscountRequestsOrderQuotation::find($id);
    if (!$record) {
      throw new Exception('Solicitud de descuento no encontrada.');
    }
    return $record;
  }

  public function store(mixed $data): DiscountRequestsOrderQuotationResource
  {
    $type = $data['type'];

    if ($type === DiscountRequestsOrderQuotation::TYPE_GLOBAL) {
      $existingDiscount = DiscountRequestsOrderQuotation::where('ap_order_quotation_id', $data['ap_order_quotation_id'])
        ->where('type', DiscountRequestsOrderQuotation::TYPE_GLOBAL)
        ->where('item_type', $data['item_type'])
        ->whereIn('status', [DiscountRequestsOrderQuotation::STATUS_REJECTED, DiscountRequestsOrderQuotation::STATUS_PENDING])
        ->select('status')
        ->first();

      if ($existingDiscount) {
        if ($existingDiscount->status === DiscountRequestsOrderQuotation::STATUS_REJECTED) {
          throw new Exception('Ya existe un descuento GLOBAL rechazado para esta cotización. No se puede crear un nuevo descuento.');
        }
        throw new Exception('Ya existe un descuento GLOBAL activo para esta cotización. Debe eliminarlo antes de crear uno nuevo.');
      }
    }

    if ($type === DiscountRequestsOrderQuotation::TYPE_PARTIAL) {
      $data['ap_order_quotation_id'] = ApOrderQuotationDetails::findOrFail($data['ap_order_quotation_detail_id'])->order_quotation_id;

      $existingDiscount = DiscountRequestsOrderQuotation::where('ap_order_quotation_detail_id', $data['ap_order_quotation_detail_id'])
        ->where('type', DiscountRequestsOrderQuotation::TYPE_PARTIAL)
        ->where('item_type', $data['item_type'])
        ->whereIn('status', [DiscountRequestsOrderQuotation::STATUS_REJECTED, DiscountRequestsOrderQuotation::STATUS_PENDING])
        ->select('status')
        ->first();

      if ($existingDiscount) {
        if ($existingDiscount->status === DiscountRequestsOrderQuotation::STATUS_REJECTED) {
          throw new Exception('Ya existe un descuento PARTIAL rechazado para este detalle de cotización. No se puede crear un nuevo descuento.');
        }
        throw new Exception('Ya existe un descuento PARTIAL activo para este detalle de cotización. Debe eliminarlo antes de crear uno nuevo.');
      }
    }

    //Obtenemos al Gerente y Jefe
    $apOrderQuotation = ApOrderQuotations::findOrFail($data['ap_order_quotation_id']);

    if ($apOrderQuotation->segmentedQuotations()->count() > 0) {
      throw new Exception('No se pueden solicitar descuentos para una cotización segmentada.');
    }

    // Obtener el gerente (cargo 142) - mismo para ambas áreas
    $manager = Worker::working()
      ->whereIn('cargo_id', Position::POSITION_GERENTE_PV_IDS)
      ->first();

    // Obtener el jefe según el área
    $bossPositionIds = $apOrderQuotation->area_id === ApMasters::AREA_TALLER
      ? Position::POSITION_JEFE_TALLER_PVT_IDS  // Taller: cargo 143
      : Position::POSITION_JEFE_REPUESTO_PVT_IDS; // Repuestos: cargo 344

    $boss = Worker::working()
      ->whereIn('cargo_id', $bossPositionIds)
      ->first();

    $data['manager_id'] = $manager?->user->id;
    $data['boss_id'] = $boss?->user->id;

    $record = DB::transaction(function () use ($data) {
      return DiscountRequestsOrderQuotation::create([
        'type' => $data['type'],
        'ap_order_quotation_id' => $data['ap_order_quotation_id'] ?? null,
        'ap_order_quotation_detail_id' => $data['ap_order_quotation_detail_id'] ?? null,
        'manager_id' => $data['manager_id'] ?? null,
        'boss_id' => $data['boss_id'] ?? null,
        'advisor_id' => auth()->id(),
        'request_date' => now(),
        'requested_discount_percentage' => $data['requested_discount_percentage'],
        'requested_discount_amount' => $data['requested_discount_amount'],
        'item_type' => $data['item_type'],
        'status' => DiscountRequestsOrderQuotation::STATUS_PENDING,
      ]);
    });

    // Send email notification to managers
    $this->sendEmailNotification($record);

    return new DiscountRequestsOrderQuotationResource($record);
  }

  public function show($id): DiscountRequestsOrderQuotationResource
  {
    return new DiscountRequestsOrderQuotationResource($this->find($id));
  }

  public function update(mixed $data): DiscountRequestsOrderQuotationResource
  {
    $record = $this->findNotApproved($data['id']);

    DB::transaction(function () use ($record, $data) {
      $record->update([
        'requested_discount_percentage' => $data['requested_discount_percentage'] ?? $record->requested_discount_percentage,
        'requested_discount_amount' => $data['requested_discount_amount'] ?? $record->requested_discount_amount,
      ]);
    });

    return new DiscountRequestsOrderQuotationResource($record->fresh());
  }

  public function destroy($id): void
  {
    $record = $this->findNotApproved($id);

    DB::transaction(function () use ($record) {
      $record->delete();
    });
  }

  public function approve($id): DiscountRequestsOrderQuotationResource
  {
    $record = $this->findNotApproved($id);

    // Validar quién puede aprobar según el área y el porcentaje de descuento
    $this->validateApproval($record);

    DB::transaction(function () use ($record) {
      // Actualizar el estado de la solicitud
      $record->update([
        'reviewed_by_id' => auth()->id(),
        'review_date' => now(),
        'status' => DiscountRequestsOrderQuotation::STATUS_APPROVED,
      ]);

      // Aplicar el descuento a la cotización
      $this->applyDiscountToQuotation($record);
    });

    $fresh = $record->fresh();

    $this->sendApprovalNotification($fresh);

    return new DiscountRequestsOrderQuotationResource($fresh);
  }

  public function reject($id): DiscountRequestsOrderQuotationResource
  {
    $record = $this->findNotApproved($id);

    DB::transaction(function () use ($record) {
      $record->update([
        'reviewed_by_id' => auth()->id(),
        'review_date' => now(),
        'status' => DiscountRequestsOrderQuotation::STATUS_REJECTED,
      ]);
    });

    $fresh = $record->fresh();

    $this->sendRejectionNotification($fresh);

    return new DiscountRequestsOrderQuotationResource($fresh);
  }

  public function revert($id, $reason = null): DiscountRequestsOrderQuotationResource
  {
    $record = $this->findApproved($id);

    // Validar que no haya sido revertido previamente
    if ($record->reverted_at !== null) {
      throw new Exception('Este descuento ya ha sido revertido previamente.');
    }

    // Validar que el usuario tenga permisos para revertir
    $this->validateRevert($record);

    DB::transaction(function () use ($record, $reason) {
      // Revertir el descuento aplicado en la cotización
      $this->revertDiscountFromQuotation($record);

      // Actualizar el registro con la información de reversión
      $record->update([
        'reverted_by_id' => auth()->id(),
        'reverted_at' => now(),
        'reverted_reason' => $reason,
      ]);
    });

    $fresh = $record->fresh();

    $this->sendRevertNotification($fresh);

    return new DiscountRequestsOrderQuotationResource($fresh);
  }

  private function findNotApproved($id): DiscountRequestsOrderQuotation
  {
    $record = $this->find($id);

    if ($record->status !== DiscountRequestsOrderQuotation::STATUS_PENDING) {
      throw new Exception('No se puede modificar una solicitud de descuento que ya ha sido procesada.');
    }

    return $record;
  }

  private function findApproved($id): DiscountRequestsOrderQuotation
  {
    $record = $this->find($id);

    if ($record->status !== DiscountRequestsOrderQuotation::STATUS_APPROVED) {
      throw new Exception('Solo se pueden revertir descuentos que hayan sido aprobados.');
    }

    return $record;
  }

  private function applyDiscountToQuotation(DiscountRequestsOrderQuotation $discountRequest): void
  {
    $discountRequest->loadMissing(['apOrderQuotation', 'apOrderQuotationDetail']);

    $quotation = $discountRequest->apOrderQuotation;
    if (!$quotation) {
      throw new Exception('Cotización no encontrada.');
    }

    if ($discountRequest->type === DiscountRequestsOrderQuotation::TYPE_PARTIAL) {
      // Descuento por ítem específico
      $this->applyPartialDiscount($discountRequest);
    } else {
      // Descuento global a todos los detalles del tipo de item
      $this->applyGlobalDiscount($discountRequest);
    }

    // Recalcular totales de la cotización
    $this->recalculateQuotationTotals($quotation);
  }

  private function applyPartialDiscount(DiscountRequestsOrderQuotation $discountRequest): void
  {
    $detail = $discountRequest->apOrderQuotationDetail;
    if (!$detail) {
      throw new Exception('Detalle de cotización no encontrado.');
    }

    $discountPercentage = $discountRequest->requested_discount_percentage;
    $unitPrice = (float)$detail->unit_price;
    $quantity = (float)$detail->quantity;

    // Calcular total_cost, net_amount y tax_amount
    $totalCost = $unitPrice * $quantity;
    $netAmount = $totalCost - ($totalCost * $discountPercentage / 100);
    $taxAmount = $netAmount * (Constants::VAT_TAX / 100);

    // Actualizar el detalle con el nuevo descuento y los campos calculados
    $detail->update([
      'discount_percentage' => $discountPercentage,
      'total_cost' => $totalCost,
      'net_amount' => $netAmount,
      'tax_amount' => $taxAmount,
    ]);
  }

  private function applyGlobalDiscount(DiscountRequestsOrderQuotation $discountRequest): void
  {
    $quotation = $discountRequest->apOrderQuotation;
    $itemType = $discountRequest->item_type;
    $discountPercentage = $discountRequest->requested_discount_percentage;

    // Obtener todos los detalles del tipo de item especificado
    $details = $quotation->details()
      ->where('item_type', $itemType)
      ->get();

    if ($details->isEmpty()) {
      throw new Exception('No se encontraron detalles del tipo ' . $itemType . ' en la cotización.');
    }

    // Aplicar el descuento a cada detalle
    foreach ($details as $detail) {
      $unitPrice = (float)$detail->unit_price;
      $quantity = (float)$detail->quantity;

      // Calcular total_cost, net_amount y tax_amount
      $totalCost = $unitPrice * $quantity;
      $netAmount = $totalCost - ($totalCost * $discountPercentage / 100);
      $taxAmount = $netAmount * (Constants::VAT_TAX / 100);

      // Actualizar el detalle con el nuevo descuento y los campos calculados
      $detail->update([
        'discount_percentage' => $discountPercentage,
        'total_cost' => $totalCost,
        'net_amount' => $netAmount,
        'tax_amount' => $taxAmount,
      ]);
    }
  }

  private function revertDiscountFromQuotation(DiscountRequestsOrderQuotation $discountRequest): void
  {
    $discountRequest->loadMissing(['apOrderQuotation', 'apOrderQuotationDetail']);

    $quotation = $discountRequest->apOrderQuotation;
    if (!$quotation) {
      throw new Exception('Cotización no encontrada.');
    }

    if ($discountRequest->type === DiscountRequestsOrderQuotation::TYPE_PARTIAL) {
      // Revertir descuento por ítem específico
      $this->revertPartialDiscount($discountRequest);
    } else {
      // Revertir descuento global de todos los detalles del tipo de item
      $this->revertGlobalDiscount($discountRequest);
    }

    // Recalcular totales de la cotización
    $this->recalculateQuotationTotals($quotation);
  }

  private function revertPartialDiscount(DiscountRequestsOrderQuotation $discountRequest): void
  {
    $detail = $discountRequest->apOrderQuotationDetail;
    if (!$detail) {
      throw new Exception('Detalle de cotización no encontrado.');
    }

    $unitPrice = (float)$detail->unit_price;
    $quantity = (float)$detail->quantity;

    // Resetear el descuento a 0
    $discountPercentage = 0;

    // Calcular total_cost, net_amount y tax_amount sin descuento
    $totalCost = $unitPrice * $quantity;
    $netAmount = $totalCost - ($totalCost * $discountPercentage / 100);
    $taxAmount = $netAmount * (Constants::VAT_TAX / 100);

    // Actualizar el detalle removiendo el descuento
    $detail->update([
      'discount_percentage' => $discountPercentage,
      'total_cost' => $totalCost,
      'net_amount' => $netAmount,
      'tax_amount' => $taxAmount,
    ]);
  }

  private function revertGlobalDiscount(DiscountRequestsOrderQuotation $discountRequest): void
  {
    $quotation = $discountRequest->apOrderQuotation;
    $itemType = $discountRequest->item_type;

    // Obtener todos los detalles del tipo de item especificado
    $details = $quotation->details()
      ->where('item_type', $itemType)
      ->get();

    if ($details->isEmpty()) {
      throw new Exception('No se encontraron detalles del tipo ' . $itemType . ' en la cotización.');
    }

    // Resetear el descuento a 0
    $discountPercentage = 0;

    // Revertir el descuento de cada detalle
    foreach ($details as $detail) {
      $unitPrice = (float)$detail->unit_price;
      $quantity = (float)$detail->quantity;

      // Calcular total_cost, net_amount y tax_amount sin descuento
      $totalCost = $unitPrice * $quantity;
      $netAmount = $totalCost - ($totalCost * $discountPercentage / 100);
      $taxAmount = $netAmount * (Constants::VAT_TAX / 100);

      // Actualizar el detalle removiendo el descuento
      $detail->update([
        'discount_percentage' => $discountPercentage,
        'total_cost' => $totalCost,
        'net_amount' => $netAmount,
        'tax_amount' => $taxAmount,
      ]);
    }
  }

  private function recalculateQuotationTotals($quotation): void
  {
    // Recargar los detalles para tener los valores actualizados
    $quotation->load('details');

    // Usar el método centralizado del modelo para recalcular todos los totales
    // Este método ya usa la lógica actualizada con total_cost, net_amount y tax_amount
    $quotation->calculateTotals();
    $quotation->save();
  }

  private function sendApprovalNotification(DiscountRequestsOrderQuotation $record): void
  {
    try {
      $record->loadMissing(['advisor', 'manager', 'boss', 'apOrderQuotation.vehicle', 'apOrderQuotationDetail', 'reviewer']);

      $quotation = $record->apOrderQuotation;
      $detail = $record->apOrderQuotationDetail;
      $advisor = $record->advisor;
      $manager = $record->manager;
      $boss = $record->boss;
      $approver = $record->reviewer;

      if ($record->type === DiscountRequestsOrderQuotation::TYPE_PARTIAL && $detail) {
        $originalPrice = (float)$detail->unit_price * (float)$detail->quantity;
      } else {
        $originalPrice = (float)($quotation->total_amount ?? 0);
      }

      $discountAmount = (float)$record->requested_discount_amount;
      $finalPrice = $originalPrice - $discountAmount;

      $sharedData = [
        'quotation_number' => $quotation->quotation_number ?? $record->ap_order_quotation_id,
        'plate' => $quotation?->vehicle?->plate,
        'type' => $record->type,
        'item_type' => $record->item_type,
        'requester_name' => $advisor?->name ?? 'Asesor',
        'approver_name' => $approver?->name ?? 'Aprobador',
        'approval_date' => $record->review_date?->format('d/m/Y H:i'),
        'item_description' => $detail?->description,
        'item_quantity' => $detail?->quantity,
        'item_unit' => $detail?->unit_measure,
        'item_unit_price' => (float)($detail?->unit_price ?? 0),
        'original_price' => $originalPrice,
        'discount_percentage' => (float)$record->requested_discount_percentage,
        'discount_amount' => $discountAmount,
        'final_price' => $finalPrice,
        'button_url' => config('app.frontend_url') . '/ap/post-venta/repuestos/cotizacion-meson/solicitar-descuento/' . $quotation->id,
      ];

      $subject = 'Descuento aprobado — Cotización #' . ($quotation->quotation_number ?? $record->ap_order_quotation_id);

      // Notificar al asesor (quien solicitó el descuento)
      if ($advisor?->email2) {
        $this->emailService->queue([
          'to' => $advisor->email2,
          'subject' => $subject,
          'template' => 'emails.discount-request-approved',
          'data' => array_merge($sharedData, ['recipient_name' => $advisor->name ?? 'Asesor']),
        ]);
      }

      $requestedDiscountPercentage = (float)$record->requested_discount_percentage;
      $bossSetting = GeneralMaster::find(GeneralMaster::BOSS_DISCOUNT_PERCENTAGE_PVR_ID);
      $maxDiscountPercentage = $bossSetting ? ($bossSetting->value * 100) : 20;

      $shouldNotifyManager = $requestedDiscountPercentage > $maxDiscountPercentage;

      // Notificar al gerente
      if ($shouldNotifyManager && $manager?->email2) {
        $this->emailService->queue([
          'to' => $manager->email2,
          'subject' => $subject,
          'template' => 'emails.discount-request-approved',
          'data' => array_merge($sharedData, ['recipient_name' => $manager->name ?? 'Gerente']),
        ]);
      }

      // Notificar al jefe
      if ($boss?->email2) {
        $this->emailService->queue([
          'to' => $boss->email2,
          'subject' => $subject,
          'template' => 'emails.discount-request-approved',
          'data' => array_merge($sharedData, ['recipient_name' => $boss->name ?? 'Jefe']),
        ]);
      }
    } catch (Exception $e) {
      \Log::error('Error al enviar notificación de aprobación de descuento: ' . $e->getMessage());
    }
  }

  private function sendRejectionNotification(DiscountRequestsOrderQuotation $record): void
  {
    try {
      $record->loadMissing(['advisor', 'manager', 'boss', 'apOrderQuotation.vehicle', 'apOrderQuotationDetail', 'reviewer']);

      $quotation = $record->apOrderQuotation;
      $detail = $record->apOrderQuotationDetail;
      $advisor = $record->advisor;
      $manager = $record->manager;
      $boss = $record->boss;
      $rejector = $record->reviewer;

      if ($record->type === DiscountRequestsOrderQuotation::TYPE_PARTIAL && $detail) {
        $originalPrice = (float)$detail->unit_price * (float)$detail->quantity;
      } else {
        $originalPrice = (float)($quotation->total_amount ?? 0);
      }

      $discountAmount = (float)$record->requested_discount_amount;
      $finalPrice = $originalPrice - $discountAmount;

      $sharedData = [
        'quotation_number' => $quotation->quotation_number ?? $record->ap_order_quotation_id,
        'plate' => $quotation?->vehicle?->plate,
        'type' => $record->type,
        'item_type' => $record->item_type,
        'requester_name' => $advisor?->name ?? 'Asesor',
        'rejector_name' => $rejector?->name ?? 'Rechazador',
        'rejection_date' => $record->review_date?->format('d/m/Y H:i'),
        'item_description' => $detail?->description,
        'item_quantity' => $detail?->quantity,
        'item_unit' => $detail?->unit_measure,
        'item_unit_price' => (float)($detail?->unit_price ?? 0),
        'original_price' => $originalPrice,
        'discount_percentage' => (float)$record->requested_discount_percentage,
        'discount_amount' => $discountAmount,
        'final_price' => $finalPrice,
        'button_url' => config('app.frontend_url') . '/ap/post-venta/repuestos/cotizacion-meson/solicitar-descuento/' . $quotation->id,
      ];

      $subject = 'Descuento rechazado — Cotización #' . ($quotation->quotation_number ?? $record->ap_order_quotation_id);

      // Notificar al asesor (quien solicitó el descuento)
      if ($advisor?->email2) {
        $this->emailService->queue([
          'to' => $advisor->email2,
          'subject' => $subject,
          'template' => 'emails.discount-request-rejected',
          'data' => array_merge($sharedData, ['recipient_name' => $advisor->name ?? 'Asesor']),
        ]);
      }

      $requestedDiscountPercentage = (float)$record->requested_discount_percentage;
      $bossSetting = GeneralMaster::find(GeneralMaster::BOSS_DISCOUNT_PERCENTAGE_PVR_ID);
      $maxDiscountPercentage = $bossSetting ? ($bossSetting->value * 100) : 20;

      $shouldNotifyManager = $requestedDiscountPercentage > $maxDiscountPercentage;

      // Notificar al gerente
      if ($shouldNotifyManager && $manager?->email2) {
        $this->emailService->queue([
          'to' => $manager->email2,
          'subject' => $subject,
          'template' => 'emails.discount-request-rejected',
          'data' => array_merge($sharedData, ['recipient_name' => $manager->name ?? 'Gerente']),
        ]);
      }

      // Notificar al jefe
      if ($boss?->email2) {
        $this->emailService->queue([
          'to' => $boss->email2,
          'subject' => $subject,
          'template' => 'emails.discount-request-rejected',
          'data' => array_merge($sharedData, ['recipient_name' => $boss->name ?? 'Jefe']),
        ]);
      }
    } catch (Exception $e) {
      \Log::error('Error al enviar notificación de rechazo de descuento: ' . $e->getMessage());
    }
  }

  private function sendEmailNotification(DiscountRequestsOrderQuotation $record): void
  {
    try {
      $record->loadMissing(['manager', 'boss', 'apOrderQuotation.vehicle', 'apOrderQuotationDetail']);

      $quotation = $record->apOrderQuotation;
      $detail = $record->apOrderQuotationDetail;
      $manager = $record->manager;
      $boss = $record->boss;

      // Precio base según tipo de descuento
      if ($record->type === DiscountRequestsOrderQuotation::TYPE_PARTIAL && $detail) {
        $originalPrice = (float)$detail->unit_price * (float)$detail->quantity;
      } else {
        $originalPrice = (float)($quotation->total_amount ?? 0);
      }

      $discountAmount = (float)$record->requested_discount_amount;
      $finalPrice = $originalPrice - $discountAmount;

      $sharedData = [
        // Cotización
        'quotation_number' => $quotation->quotation_number ?? $record->ap_order_quotation_id,
        'plate' => $quotation?->vehicle?->plate,
        'type' => $record->type,
        'item_type' => $record->item_type,

        // Ítem (solo PARTIAL)
        'item_description' => $detail?->description,
        'item_quantity' => $detail?->quantity,
        'item_unit' => $detail?->unit_measure,
        'item_unit_price' => (float)($detail?->unit_price ?? 0),

        // Resumen descuento
        'original_price' => $originalPrice,
        'discount_percentage' => (float)$record->requested_discount_percentage,
        'discount_amount' => $discountAmount,
        'final_price' => $finalPrice,

        // Link
        'button_url' => config('app.frontend_url') . '/ap/post-venta/repuestos/cotizacion-meson/solicitar-descuento/' . $record->apOrderQuotation->id,
      ];

      $subject = 'Nueva solicitud de descuento — Cotización #' . ($quotation->quotation_number ?? $record->ap_order_quotation_id);

      $requestedDiscountPercentage = (float)$record->requested_discount_percentage;
      $bossSetting = GeneralMaster::find(GeneralMaster::BOSS_DISCOUNT_PERCENTAGE_PVR_ID);
      $maxDiscountPercentage = $bossSetting ? ($bossSetting->value * 100) : 20;

      $shouldNotifyManager = $requestedDiscountPercentage > $maxDiscountPercentage;

      // Notificar al gerente
      if ($shouldNotifyManager && $manager?->email2) {
        $this->emailService->queue([
          'to' => $manager->email2,
          'subject' => $subject,
          'template' => 'emails.discount-request-notification',
          'data' => array_merge($sharedData, [
            'manager_name' => $manager->name ?? 'Gerente',
            'requester_name' => auth()->user()->name ?? 'Asesor',
          ]),
        ]);
      }

      // Notificar al jefe
      if ($boss?->email2) {
        $this->emailService->queue([
          'to' => $boss->email2,
          'subject' => $subject,
          'template' => 'emails.discount-request-notification',
          'data' => array_merge($sharedData, [
            'manager_name' => $boss->name ?? 'Jefe',
            'requester_name' => auth()->user()->name ?? 'Asesor',
          ]),
        ]);
      }
    } catch (Exception $e) {
      \Log::error('Error al enviar notificación de solicitud de descuento: ' . $e->getMessage());
    }
  }

  private function sendRevertNotification(DiscountRequestsOrderQuotation $record): void
  {
    try {
      $record->loadMissing(['advisor', 'manager', 'boss', 'apOrderQuotation.vehicle', 'apOrderQuotationDetail', 'revertedBy']);

      $quotation = $record->apOrderQuotation;
      $detail = $record->apOrderQuotationDetail;
      $advisor = $record->advisor;
      $manager = $record->manager;
      $boss = $record->boss;
      $reverter = $record->revertedBy;

      if ($record->type === DiscountRequestsOrderQuotation::TYPE_PARTIAL && $detail) {
        $originalPrice = (float)$detail->unit_price * (float)$detail->quantity;
      } else {
        $originalPrice = (float)($quotation->total_amount ?? 0);
      }

      $discountAmount = (float)$record->requested_discount_amount;
      $finalPrice = $originalPrice - $discountAmount;

      $sharedData = [
        'quotation_number' => $quotation->quotation_number ?? $record->ap_order_quotation_id,
        'plate' => $quotation?->vehicle?->plate,
        'type' => $record->type,
        'item_type' => $record->item_type,
        'requester_name' => $advisor?->name ?? 'Asesor',
        'reverter_name' => $reverter?->name ?? 'Administrador',
        'reverted_date' => $record->reverted_at?->format('d/m/Y H:i'),
        'reverted_reason' => $record->reverted_reason ?? 'No se especificó razón',
        'item_description' => $detail?->description,
        'item_quantity' => $detail?->quantity,
        'item_unit' => $detail?->unit_measure,
        'item_unit_price' => (float)($detail?->unit_price ?? 0),
        'original_price' => $originalPrice,
        'discount_percentage' => (float)$record->requested_discount_percentage,
        'discount_amount' => $discountAmount,
        'final_price' => $finalPrice,
        'button_url' => config('app.frontend_url') . '/ap/post-venta/repuestos/cotizacion-meson/solicitar-descuento/' . $quotation->id,
      ];

      $subject = 'Descuento revertido — Cotización #' . ($quotation->quotation_number ?? $record->ap_order_quotation_id);

      // Notificar al asesor (quien solicitó el descuento originalmente)
      if ($advisor?->email2) {
        $this->emailService->queue([
          'to' => $advisor->email2,
          'subject' => $subject,
          'template' => 'emails.discount-request-reverted',
          'data' => array_merge($sharedData, ['recipient_name' => $advisor->name ?? 'Asesor']),
        ]);
      }

      $requestedDiscountPercentage = (float)$record->requested_discount_percentage;
      $bossSetting = GeneralMaster::find(GeneralMaster::BOSS_DISCOUNT_PERCENTAGE_PVR_ID);
      $maxDiscountPercentage = $bossSetting ? ($bossSetting->value * 100) : 20;

      $shouldNotifyManager = $requestedDiscountPercentage > $maxDiscountPercentage;

      // Notificar al gerente
      if ($shouldNotifyManager && $manager?->email2) {
        $this->emailService->queue([
          'to' => $manager->email2,
          'subject' => $subject,
          'template' => 'emails.discount-request-reverted',
          'data' => array_merge($sharedData, ['recipient_name' => $manager->name ?? 'Gerente']),
        ]);
      }

      // Notificar al jefe
      if ($boss?->email2) {
        $this->emailService->queue([
          'to' => $boss->email2,
          'subject' => $subject,
          'template' => 'emails.discount-request-reverted',
          'data' => array_merge($sharedData, ['recipient_name' => $boss->name ?? 'Jefe']),
        ]);
      }
    } catch (Exception $e) {
      \Log::error('Error al enviar notificación de reversión de descuento: ' . $e->getMessage());
    }
  }

  /**
   * Valida que el usuario tenga permisos para aprobar el descuento según el área y el porcentaje solicitado
   */
  private function validateApproval(DiscountRequestsOrderQuotation $record): void
  {
    // 1. Obtener la cotización y determinar el área
    $quotation = ApOrderQuotations::findOrFail($record->ap_order_quotation_id);
    $areaId = $quotation->area_id;

    // 2. Obtener el usuario autenticado y su cargo
    $user = auth()->user();
    $positionId = $user->person?->position?->id;

    if (!$positionId) {
      throw new Exception('No se pudo determinar el cargo del usuario autenticado.');
    }

    // 3. Determinar si es gerente
    $isManager = in_array($positionId, Position::POSITION_GERENTE_PV_IDS);

    // 4. Determinar si es jefe del área correspondiente
    $isBoss = false;
    $bossDiscountSettingId = null;

    if ($areaId === ApMasters::AREA_TALLER) {
      $isBoss = in_array($positionId, Position::POSITION_JEFE_TALLER_PVT_IDS);
      $bossDiscountSettingId = GeneralMaster::BOSS_DISCOUNT_PERCENTAGE_PVT_ID;
    } elseif ($areaId === ApMasters::AREA_MESON) {
      $isBoss = in_array($positionId, Position::POSITION_JEFE_REPUESTO_PVT_IDS);
      $bossDiscountSettingId = GeneralMaster::BOSS_DISCOUNT_PERCENTAGE_PVR_ID;
    } else {
      throw new Exception('El área de la cotización no es válida para solicitudes de descuento.');
    }

    // 5. Validar que el usuario sea gerente o jefe
    if (!$isManager && !$isBoss) {
      throw new Exception('No tiene permisos para aprobar esta solicitud de descuento. Solo el jefe del área o el gerente pueden aprobar.');
    }

    // 6. Obtener el porcentaje de descuento solicitado (convertir de 30 a 0.30 para comparar)
    $requestedDiscountPercentage = (float)$record->requested_discount_percentage / 100;

    // 7. Obtener límites de descuento
    $managerDiscountSetting = GeneralMaster::find(GeneralMaster::MANAGER_DISCOUNT_PERCENTAGE_PV_ID);
    $maxManagerDiscount = $managerDiscountSetting ? (float)$managerDiscountSetting->value : 0.30; // Default 30%

    $bossDiscountSetting = GeneralMaster::find($bossDiscountSettingId);
    $maxBossDiscount = $bossDiscountSetting ? (float)$bossDiscountSetting->value : 0.20; // Default 20%

    // 8. Validar según el rol y el porcentaje
    if ($isBoss && !$isManager) {
      // Si es solo jefe (no gerente), validar que el descuento no exceda su límite
      if ($requestedDiscountPercentage > $maxBossDiscount) {
        throw new Exception(
          'El descuento solicitado (' . ($requestedDiscountPercentage * 100) . '%) excede el límite permitido para el jefe (' . ($maxBossDiscount * 100) . '%). Solo el gerente puede aprobar este descuento.'
        );
      }
    } elseif ($isManager) {
      // Si es gerente, validar que el descuento no exceda su límite
      if ($requestedDiscountPercentage > $maxManagerDiscount) {
        throw new Exception(
          'El descuento solicitado (' . ($requestedDiscountPercentage * 100) . '%) excede el límite máximo permitido (' . ($maxManagerDiscount * 100) . '%).'
        );
      }
    }
  }

  /**
   * Valida que el usuario tenga permisos para revertir el descuento según el área
   */
  private function validateRevert(DiscountRequestsOrderQuotation $record): void
  {
    // 1. Obtener la cotización y determinar el área
    $quotation = ApOrderQuotations::findOrFail($record->ap_order_quotation_id);
    $areaId = $quotation->area_id;

    // 2. Obtener el usuario autenticado y su cargo
    $user = auth()->user();
    $positionId = $user->person?->position?->id;

    if (!$positionId) {
      throw new Exception('No se pudo determinar el cargo del usuario autenticado.');
    }

    // 3. Determinar si es gerente
    $isManager = in_array($positionId, Position::POSITION_GERENTE_PV_IDS);

    // 4. Determinar si es jefe del área correspondiente
    $isBoss = false;

    if ($areaId === ApMasters::AREA_TALLER) {
      $isBoss = in_array($positionId, Position::POSITION_JEFE_TALLER_PVT_IDS);
    } elseif ($areaId === ApMasters::AREA_MESON) {
      $isBoss = in_array($positionId, Position::POSITION_JEFE_REPUESTO_PVT_IDS);
    } else {
      throw new Exception('El área de la cotización no es válida para solicitudes de descuento.');
    }

    // 5. Validar que el usuario sea gerente o jefe
    if (!$isManager && !$isBoss) {
      throw new Exception('No tiene permisos para revertir esta solicitud de descuento. Solo el jefe del área o el gerente pueden revertir.');
    }
  }
}
