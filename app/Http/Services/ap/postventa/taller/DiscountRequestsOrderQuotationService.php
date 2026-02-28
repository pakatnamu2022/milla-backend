<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\DiscountRequestsOrderQuotationResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\EmailService;
use App\Models\ap\postventa\DiscountRequestsOrderQuotation;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
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

    $record = DB::transaction(function () use ($data) {
      return DiscountRequestsOrderQuotation::create([
        'type' => $data['type'],
        'ap_order_quotation_id' => $data['ap_order_quotation_id'] ?? null,
        'ap_order_quotation_detail_id' => $data['ap_order_quotation_detail_id'] ?? null,
        'manager_id' => auth()->id(),
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

  private function findNotApproved($id): DiscountRequestsOrderQuotation
  {
    $record = $this->find($id);

    if ($record->status !== DiscountRequestsOrderQuotation::STATUS_PENDING) {
      throw new Exception('No se puede modificar una solicitud de descuento que ya ha sido procesada.');
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

    // Aplicar el porcentaje de descuento al detalle
    $detail->update([
      'discount_percentage' => $discountPercentage,
    ]);

    // Recalcular el total del detalle
    $unitPrice = (float)$detail->unit_price;
    $quantity = (float)$detail->quantity;
    $subtotal = $unitPrice * $quantity;
    $discountAmount = $subtotal * ($discountPercentage / 100);
    $totalAmount = $subtotal - $discountAmount;

    $detail->update([
      'total_amount' => $totalAmount,
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
      $detail->update([
        'discount_percentage' => $discountPercentage,
      ]);

      // Recalcular el total del detalle
      $unitPrice = (float)$detail->unit_price;
      $quantity = (float)$detail->quantity;
      $subtotal = $unitPrice * $quantity;
      $discountAmount = $subtotal * ($discountPercentage / 100);
      $totalAmount = $subtotal - $discountAmount;

      $detail->update([
        'total_amount' => $totalAmount,
      ]);
    }
  }

  private function recalculateQuotationTotals($quotation): void
  {
    // Recargar los detalles para tener los valores actualizados
    $quotation->load('details');

    // Calcular subtotal (suma de todos los totales de detalles)
    $subtotal = 0;
    $totalDiscountAmount = 0;

    foreach ($quotation->details as $detail) {
      $unitPrice = (float)$detail->unit_price;
      $quantity = (float)$detail->quantity;
      $discountPercentage = (float)($detail->discount_percentage ?? 0);

      $itemSubtotal = $unitPrice * $quantity;
      $itemDiscount = $itemSubtotal * ($discountPercentage / 100);

      $subtotal += $itemSubtotal;
      $totalDiscountAmount += $itemDiscount;
    }

    // Calcular el porcentaje de descuento global
    $discountPercentage = $subtotal > 0 ? ($totalDiscountAmount / $subtotal) * 100 : 0;

    // Calcular impuestos (18% sobre el subtotal después del descuento)
    $subtotalAfterDiscount = $subtotal - $totalDiscountAmount;
    $taxAmount = $subtotalAfterDiscount * 0.18;

    // Calcular total
    $totalAmount = $subtotalAfterDiscount + $taxAmount;

    // Actualizar la cotización
    $quotation->update([
      'subtotal' => $subtotal,
      'discount_percentage' => $discountPercentage,
      'discount_amount' => $totalDiscountAmount,
      'tax_amount' => $taxAmount,
      'total_amount' => $totalAmount,
    ]);
  }

  private function sendApprovalNotification(DiscountRequestsOrderQuotation $record): void
  {
    try {
      $record->loadMissing(['manager', 'apOrderQuotation.vehicle', 'apOrderQuotationDetail', 'reviewer']);

      $quotation = $record->apOrderQuotation;
      $detail = $record->apOrderQuotationDetail;
      $requester = $record->manager;
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
        'requester_name' => $requester?->name ?? 'Usuario',
        'approver_name' => $approver?->name ?? 'Gerente',
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

      // Notificar al solicitante
      $this->emailService->queue([
        'to' => 'wsuclupef2001@gmail.com', //$requester?->email,
        'subject' => $subject,
        'template' => 'emails.discount-request-approved',
        'data' => array_merge($sharedData, ['recipient_name' => $requester?->name ?? 'Usuario']),
      ]);

      // Notificar al aprobador
      $this->emailService->queue([
        'to' => 'wsuclupef2001@gmail.com', //$approver?->email,
        'subject' => $subject,
        'template' => 'emails.discount-request-approved',
        'data' => array_merge($sharedData, ['recipient_name' => $approver?->name ?? 'Gerente']),
      ]);
    } catch (Exception $e) {
      \Log::error('Error al enviar notificación de aprobación de descuento: ' . $e->getMessage());
    }
  }

  private function sendRejectionNotification(DiscountRequestsOrderQuotation $record): void
  {
    try {
      $record->loadMissing(['manager', 'apOrderQuotation.vehicle', 'apOrderQuotationDetail', 'reviewer']);

      $quotation = $record->apOrderQuotation;
      $detail = $record->apOrderQuotationDetail;
      $requester = $record->manager;
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
        'requester_name' => $requester?->name ?? 'Usuario',
        'rejector_name' => $rejector?->name ?? 'Gerente',
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

      // Notificar al solicitante
      $this->emailService->queue([
        'to' => 'wsuclupef2001@gmail.com', //$requester?->email,
        'subject' => $subject,
        'template' => 'emails.discount-request-rejected',
        'data' => array_merge($sharedData, ['recipient_name' => $requester?->name ?? 'Usuario']),
      ]);

      // Notificar al que rechazó
      $this->emailService->queue([
        'to' => 'wsuclupef2001@gmail.com', //$rejector?->email,
        'subject' => $subject,
        'template' => 'emails.discount-request-rejected',
        'data' => array_merge($sharedData, ['recipient_name' => $rejector?->name ?? 'Gerente']),
      ]);
    } catch (Exception $e) {
      \Log::error('Error al enviar notificación de rechazo de descuento: ' . $e->getMessage());
    }
  }

  private function sendEmailNotification(DiscountRequestsOrderQuotation $record): void
  {
    try {
      $record->loadMissing(['manager', 'apOrderQuotation.vehicle', 'apOrderQuotationDetail']);

      $quotation = $record->apOrderQuotation;
      $detail = $record->apOrderQuotationDetail;
      $manager = $record->manager;

      // Precio base según tipo de descuento
      if ($record->type === DiscountRequestsOrderQuotation::TYPE_PARTIAL && $detail) {
        $originalPrice = (float)$detail->unit_price * (float)$detail->quantity;
      } else {
        $originalPrice = (float)($quotation->total_amount ?? 0);
      }

      $discountAmount = (float)$record->requested_discount_amount;
      $finalPrice = $originalPrice - $discountAmount;

      $data = [
        // Cotización
        'quotation_number' => $quotation->quotation_number ?? $record->ap_order_quotation_id,
        'plate' => $quotation?->vehicle?->plate,
        'type' => $record->type,
        'item_type' => $record->item_type,

        // Solicitante
        'manager_name' => $manager?->name ?? 'Gerente',
        'requester_name' => $manager?->name ?? 'Usuario',

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

      $this->emailService->queue([
        //'to' => $manager?->email2,
        'to' => 'wsuclupef2001@gmail.com',
        'subject' => 'Nueva solicitud de descuento — Cotización #' . ($quotation->quotation_number ?? $record->ap_order_quotation_id),
        'template' => 'emails.discount-request-notification',
        'data' => $data,
      ]);
    } catch (Exception $e) {
      \Log::error('Error al enviar notificación de solicitud de descuento: ' . $e->getMessage());
    }
  }
}
