<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\DiscountRequestsWorkOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\EmailService;
use App\Models\ap\postventa\DiscountRequestsWorkOrder;
use App\Models\ap\postventa\taller\WorkOrderLabour;
use App\Models\ap\postventa\taller\ApWorkOrderParts;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiscountRequestsWorkOrderService extends BaseService implements BaseServiceInterface
{
  protected EmailService $emailService;

  public function __construct(EmailService $emailService)
  {
    $this->emailService = $emailService;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      DiscountRequestsWorkOrder::class,
      $request,
      DiscountRequestsWorkOrder::filters,
      DiscountRequestsWorkOrder::sorts,
      DiscountRequestsWorkOrderResource::class,
    );
  }

  public function find($id): DiscountRequestsWorkOrder
  {
    $record = DiscountRequestsWorkOrder::find($id);
    if (!$record) {
      throw new Exception('Solicitud de descuento no encontrada.');
    }
    return $record;
  }

  public function store(mixed $data): DiscountRequestsWorkOrderResource
  {
    $type = $data['type'];

    if ($type === DiscountRequestsWorkOrder::TYPE_GLOBAL) {
      $exists = DiscountRequestsWorkOrder::where('ap_work_order_id', $data['ap_work_order_id'])
        ->where('type', DiscountRequestsWorkOrder::TYPE_GLOBAL)
        ->exists();

      if ($exists) {
        throw new Exception('Ya existe un descuento GLOBAL activo para esta orden de trabajo. Debe eliminarlo antes de crear uno nuevo.');
      }
    }

    if ($type === DiscountRequestsWorkOrder::TYPE_PARTIAL) {
      if (!isset($data['part_labour_id']) || !isset($data['part_labour_model'])) {
        throw new Exception('Para un descuento PARTIAL, debe especificar el ítem (parte o labor).');
      }

      $exists = DiscountRequestsWorkOrder::where('part_labour_id', $data['part_labour_id'])
        ->where('part_labour_model', $data['part_labour_model'])
        ->where('type', DiscountRequestsWorkOrder::TYPE_PARTIAL)
        ->exists();

      if ($exists) {
        throw new Exception('Ya existe un descuento PARTIAL activo para este ítem. Debe eliminarlo antes de crear uno nuevo.');
      }
    }

    $record = DB::transaction(function () use ($data) {
      return DiscountRequestsWorkOrder::create([
        'type' => $data['type'],
        'ap_work_order_id' => $data['ap_work_order_id'],
        'part_labour_id' => $data['part_labour_id'] ?? null,
        'part_labour_model' => $data['part_labour_model'] ?? null,
        'manager_id' => auth()->id(),
        'request_date' => now(),
        'requested_discount_percentage' => $data['requested_discount_percentage'],
        'requested_discount_amount' => $data['requested_discount_amount'],
        'status' => DiscountRequestsWorkOrder::STATUS_PENDING,
      ]);
    });

    // Send email notification to managers
    $this->sendEmailNotification($record);

    return new DiscountRequestsWorkOrderResource($record);
  }

  public function show($id): DiscountRequestsWorkOrderResource
  {
    return new DiscountRequestsWorkOrderResource($this->find($id));
  }

  public function update(mixed $data): DiscountRequestsWorkOrderResource
  {
    $record = $this->findNotApproved($data['id']);

    DB::transaction(function () use ($record, $data) {
      $record->update([
        'requested_discount_percentage' => $data['requested_discount_percentage'] ?? $record->requested_discount_percentage,
        'requested_discount_amount' => $data['requested_discount_amount'] ?? $record->requested_discount_amount,
      ]);
    });

    return new DiscountRequestsWorkOrderResource($record->fresh());
  }

  public function destroy($id): void
  {
    $record = $this->findNotApproved($id);

    DB::transaction(function () use ($record) {
      $record->delete();
    });
  }

  public function approve($id): DiscountRequestsWorkOrderResource
  {
    $record = $this->findNotApproved($id);

    DB::transaction(function () use ($record) {
      $record->update([
        'reviewed_by_id' => auth()->id(),
        'review_date' => now(),
        'status' => DiscountRequestsWorkOrder::STATUS_APPROVED,
      ]);
    });

    $fresh = $record->fresh();

    $this->sendApprovalNotification($fresh);

    return new DiscountRequestsWorkOrderResource($fresh);
  }

  public function reject($id): DiscountRequestsWorkOrderResource
  {
    $record = $this->findNotApproved($id);

    DB::transaction(function () use ($record) {
      $record->update([
        'reviewed_by_id' => auth()->id(),
        'review_date' => now(),
        'status' => DiscountRequestsWorkOrder::STATUS_REJECTED,
      ]);
    });

    $fresh = $record->fresh();

    $this->sendRejectionNotification($fresh);

    return new DiscountRequestsWorkOrderResource($fresh);
  }

  private function findNotApproved($id): DiscountRequestsWorkOrder
  {
    $record = $this->find($id);

    if ($record->status !== DiscountRequestsWorkOrder::STATUS_PENDING) {
      throw new Exception('No se puede modificar una solicitud de descuento que ya ha sido procesada.');
    }

    return $record;
  }

  private function getItemDetails($record)
  {
    $item = null;
    $itemDescription = null;
    $itemQuantity = null;
    $itemUnit = null;
    $itemUnitPrice = null;
    $originalPrice = 0;

    if ($record->type === DiscountRequestsWorkOrder::TYPE_PARTIAL && $record->part_labour_model) {
      if ($record->part_labour_model === ApWorkOrderParts::class) {
        $item = ApWorkOrderParts::find($record->part_labour_id);
        if ($item) {
          $itemDescription = $item->description ?? $item->product?->name;
          $itemQuantity = $item->quantity;
          $itemUnit = $item->unit_measure;
          $itemUnitPrice = (float)$item->unit_price;
          $originalPrice = $itemUnitPrice * $itemQuantity;
        }
      } elseif ($record->part_labour_model === WorkOrderLabour::class) {
        $item = WorkOrderLabour::find($record->part_labour_id);
        if ($item) {
          $itemDescription = $item->description ?? $item->labour?->name;
          $itemQuantity = $item->hours;
          $itemUnit = 'horas';
          $itemUnitPrice = (float)$item->cost_per_hour;
          $originalPrice = $itemUnitPrice * $itemQuantity;
        }
      }
    } else {
      // GLOBAL
      $workOrder = $record->apWorkOrder;
      $originalPrice = (float)($workOrder->total_amount ?? 0);
    }

    return [
      'item' => $item,
      'item_description' => $itemDescription,
      'item_quantity' => $itemQuantity,
      'item_unit' => $itemUnit,
      'item_unit_price' => $itemUnitPrice,
      'original_price' => $originalPrice,
    ];
  }

  private function sendApprovalNotification(DiscountRequestsWorkOrder $record): void
  {
    try {
      $record->loadMissing(['manager', 'apWorkOrder.vehicle', 'reviewer']);

      $workOrder = $record->apWorkOrder;
      $requester = $record->manager;
      $approver = $record->reviewer;

      $itemDetails = $this->getItemDetails($record);
      $originalPrice = $itemDetails['original_price'];

      $discountAmount = (float)$record->requested_discount_amount;
      $finalPrice = $originalPrice - $discountAmount;

      $sharedData = [
        'quotation_number' => $workOrder->work_order_number ?? $record->ap_work_order_id,
        'plate' => $workOrder?->vehicle?->plate,
        'type' => $record->type,
        'item_type' => $record->part_labour_model === ApWorkOrderParts::class ? 'PRODUCT' : 'LABOR',
        'requester_name' => $requester?->name ?? 'Usuario',
        'approver_name' => $approver?->name ?? 'Gerente',
        'approval_date' => $record->review_date?->format('d/m/Y H:i'),
        'item_description' => $itemDetails['item_description'],
        'item_quantity' => $itemDetails['item_quantity'],
        'item_unit' => $itemDetails['item_unit'],
        'item_unit_price' => $itemDetails['item_unit_price'],
        'original_price' => $originalPrice,
        'discount_percentage' => (float)$record->requested_discount_percentage,
        'discount_amount' => $discountAmount,
        'final_price' => $finalPrice,
        'button_url' => config('app.frontend_url') . '/ap/post-venta/taller/orden-trabajo/' . $workOrder->id,
      ];

      $subject = 'Descuento aprobado — Orden de Trabajo #' . ($workOrder->work_order_number ?? $record->ap_work_order_id);

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

  private function sendRejectionNotification(DiscountRequestsWorkOrder $record): void
  {
    try {
      $record->loadMissing(['manager', 'apWorkOrder.vehicle', 'reviewer']);

      $workOrder = $record->apWorkOrder;
      $requester = $record->manager;
      $rejector = $record->reviewer;

      $itemDetails = $this->getItemDetails($record);
      $originalPrice = $itemDetails['original_price'];

      $discountAmount = (float)$record->requested_discount_amount;
      $finalPrice = $originalPrice - $discountAmount;

      $sharedData = [
        'quotation_number' => $workOrder->work_order_number ?? $record->ap_work_order_id,
        'plate' => $workOrder?->vehicle?->plate,
        'type' => $record->type,
        'item_type' => $record->part_labour_model === ApWorkOrderParts::class ? 'PRODUCT' : 'LABOR',
        'requester_name' => $requester?->name ?? 'Usuario',
        'rejector_name' => $rejector?->name ?? 'Gerente',
        'rejection_date' => $record->review_date?->format('d/m/Y H:i'),
        'item_description' => $itemDetails['item_description'],
        'item_quantity' => $itemDetails['item_quantity'],
        'item_unit' => $itemDetails['item_unit'],
        'item_unit_price' => $itemDetails['item_unit_price'],
        'original_price' => $originalPrice,
        'discount_percentage' => (float)$record->requested_discount_percentage,
        'discount_amount' => $discountAmount,
        'final_price' => $finalPrice,
        'button_url' => config('app.frontend_url') . '/ap/post-venta/taller/orden-trabajo/' . $workOrder->id,
      ];

      $subject = 'Descuento rechazado — Orden de Trabajo #' . ($workOrder->work_order_number ?? $record->ap_work_order_id);

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

  private function sendEmailNotification(DiscountRequestsWorkOrder $record): void
  {
    try {
      $record->loadMissing(['manager', 'apWorkOrder.vehicle']);

      $workOrder = $record->apWorkOrder;
      $manager = $record->manager;

      $itemDetails = $this->getItemDetails($record);
      $originalPrice = $itemDetails['original_price'];

      $discountAmount = (float)$record->requested_discount_amount;
      $finalPrice = $originalPrice - $discountAmount;

      $data = [
        // Orden de trabajo
        'quotation_number' => $workOrder->work_order_number ?? $record->ap_work_order_id,
        'plate' => $workOrder?->vehicle?->plate,
        'type' => $record->type,
        'item_type' => $record->part_labour_model === ApWorkOrderParts::class ? 'PRODUCT' : 'LABOR',

        // Solicitante
        'manager_name' => $manager?->name ?? 'Gerente',
        'requester_name' => $manager?->name ?? 'Usuario',

        // Ítem (solo PARTIAL)
        'item_description' => $itemDetails['item_description'],
        'item_quantity' => $itemDetails['item_quantity'],
        'item_unit' => $itemDetails['item_unit'],
        'item_unit_price' => $itemDetails['item_unit_price'],

        // Resumen descuento
        'original_price' => $originalPrice,
        'discount_percentage' => (float)$record->requested_discount_percentage,
        'discount_amount' => $discountAmount,
        'final_price' => $finalPrice,

        // Link
        'button_url' => config('app.frontend_url') . '/ap/post-venta/taller/orden-trabajo/' . $record->apWorkOrder->id,
      ];

      $this->emailService->queue([
        //'to' => $manager?->email2,
        'to' => 'wsuclupef2001@gmail.com',
        'subject' => 'Nueva solicitud de descuento — Orden de Trabajo #' . ($workOrder->work_order_number ?? $record->ap_work_order_id),
        'template' => 'emails.discount-request-notification',
        'data' => $data,
      ]);
    } catch (Exception $e) {
      \Log::error('Error al enviar notificación de solicitud de descuento: ' . $e->getMessage());
    }
  }
}