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
      $exists = DiscountRequestsOrderQuotation::where('ap_order_quotation_id', $data['ap_order_quotation_id'])
        ->where('type', DiscountRequestsOrderQuotation::TYPE_GLOBAL)
        ->where('item_type', $data['item_type'])
        ->exists();

      if ($exists) {
        throw new Exception('Ya existe un descuento GLOBAL activo para esta cotización. Debe eliminarlo antes de crear uno nuevo.');
      }
    }

    if ($type === DiscountRequestsOrderQuotation::TYPE_PARTIAL) {
      $data['ap_order_quotation_id'] = ApOrderQuotationDetails::findOrFail($data['ap_order_quotation_detail_id'])->order_quotation_id;
      $exists = DiscountRequestsOrderQuotation::where('ap_order_quotation_detail_id', $data['ap_order_quotation_detail_id'])
        ->where('type', DiscountRequestsOrderQuotation::TYPE_PARTIAL)
        ->where('item_type', $data['item_type'])
        ->exists();

      if ($exists) {
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
      $record->update([
        'approved_id' => auth()->id(),
        'approval_date' => now(),
      ]);
    });

    $fresh = $record->fresh();

    $this->sendApprovalNotification($fresh);

    return new DiscountRequestsOrderQuotationResource($fresh);
  }

  private function findNotApproved($id): DiscountRequestsOrderQuotation
  {
    $record = $this->find($id);

    if (!is_null($record->approved_id) || !is_null($record->approval_date)) {
      throw new Exception('No se puede modificar una solicitud de descuento que ya ha sido aprobada.');
    }

    return $record;
  }

  private function sendApprovalNotification(DiscountRequestsOrderQuotation $record): void
  {
    try {
      $record->loadMissing(['manager', 'apOrderQuotation.vehicle', 'apOrderQuotationDetail']);

      $quotation = $record->apOrderQuotation;
      $detail = $record->apOrderQuotationDetail;
      $requester = $record->manager;
      $approver = $record->approved;

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
        'approval_date' => $record->approval_date?->format('d/m/Y H:i'),
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
