<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\PurchaseRequestQuoteAdjustmentRequestResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\EmailService;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\DetailsApprovedAccessoriesQuote;
use App\Models\ap\comercial\DiscountCoupons;
use App\Models\ap\comercial\PurchaseRequestQuote;
use App\Models\ap\comercial\PurchaseRequestQuoteAdjustmentItem;
use App\Models\ap\comercial\PurchaseRequestQuoteAdjustmentRequest;
use App\Models\ap\postventa\repuestos\ApprovedAccessories;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseRequestQuoteAdjustmentRequestService extends BaseService implements BaseServiceInterface
{
  protected EmailService $emailService;
  protected PurchaseRequestQuoteService $purchaseRequestQuoteService;

  public function __construct(EmailService $emailService, PurchaseRequestQuoteService $purchaseRequestQuoteService)
  {
    $this->emailService = $emailService;
    $this->purchaseRequestQuoteService = $purchaseRequestQuoteService;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PurchaseRequestQuoteAdjustmentRequest::class,
      $request,
      PurchaseRequestQuoteAdjustmentRequest::filters,
      PurchaseRequestQuoteAdjustmentRequest::sorts,
      PurchaseRequestQuoteAdjustmentRequestResource::class,
    );
  }

  public function find($id): PurchaseRequestQuoteAdjustmentRequest
  {
    $record = PurchaseRequestQuoteAdjustmentRequest::find($id);
    if (!$record) {
      throw new Exception('Solicitud de ajuste no encontrada.');
    }
    return $record;
  }

  public function show($id): PurchaseRequestQuoteAdjustmentRequestResource
  {
    $record = $this->find($id);
    $record->load([
      'items.conceptCode',
      'items.discountCoupon',
      'items.accessoryDetail.approvedAccessory',
      'items.approvedAccessory',
      'purchaseRequestQuote',
      'requestedBy',
      'resolvedBy',
    ]);
    return new PurchaseRequestQuoteAdjustmentRequestResource($record);
  }

  public function store(mixed $data): PurchaseRequestQuoteAdjustmentRequestResource
  {
    $quote = PurchaseRequestQuote::find($data['purchase_request_quote_id']);
    if (!$quote) {
      throw new Exception('La solicitud/cotización no existe.');
    }

    if (!$quote->is_paid) {
      throw new Exception('Solo se pueden solicitar ajustes de bono/descuento/obsequio sobre cotizaciones ya pagadas.');
    }

    $hasOpenRequest = PurchaseRequestQuoteAdjustmentRequest::where('purchase_request_quote_id', $quote->id)
      ->where('status', PurchaseRequestQuoteAdjustmentRequest::STATUS_PENDING)
      ->exists();
    if ($hasOpenRequest) {
      throw new Exception('Ya existe una solicitud de ajuste pendiente para esta cotización.');
    }

    $items = $data['items'];
    if (empty($items)) {
      throw new Exception('Debe agregar al menos una línea de cambio.');
    }

    $quote->load(['discountCoupons', 'accessories', 'others', 'vehicle']);
    $bodyTypeId = $this->purchaseRequestQuoteService->bodyTypeIdForModel($quote->ap_models_vn_id);

    $marginBefore = $this->purchaseRequestQuoteService->calculateMargin($quote);
    $simulated = $this->simulateItems($quote, $items, $bodyTypeId);
    $marginAfter = $this->simulateMargin($quote, $simulated['coupons'], $simulated['gifts']);

    $record = DB::transaction(function () use ($quote, $data, $items, $marginBefore, $marginAfter, $bodyTypeId) {
      $request = PurchaseRequestQuoteAdjustmentRequest::create([
        'purchase_request_quote_id' => $quote->id,
        'requested_by_id' => auth()->id(),
        'status' => PurchaseRequestQuoteAdjustmentRequest::STATUS_PENDING,
        'reason' => $data['reason'] ?? null,
        'margin_amount_before' => $marginBefore['margin_amount'],
        'margin_pct_before' => $marginBefore['margin_pct'],
        'margin_amount_after' => $marginAfter['margin_amount'],
        'margin_pct_after' => $marginAfter['margin_pct'],
      ]);

      $couponsById = $quote->discountCoupons->keyBy('id');
      $giftsById = $quote->accessories->where('type', 'OBSEQUIO')->keyBy('id');

      foreach ($items as $item) {
        $itemType = $item['item_type'] ?? PurchaseRequestQuoteAdjustmentItem::ITEM_TYPE_BONUS_DISCOUNT;

        $itemType === PurchaseRequestQuoteAdjustmentItem::ITEM_TYPE_GIFT
          ? $this->buildGiftItem($request->id, $item, $giftsById, $bodyTypeId)
          : $this->buildBonusItem($request->id, $item, $couponsById, (float)$quote->sale_price);
      }

      return $request;
    });

    $fresh = $record->fresh([
      'items.conceptCode',
      'items.accessoryDetail.approvedAccessory',
      'items.approvedAccessory',
      'purchaseRequestQuote.holder',
      'requestedBy',
    ]);

    $this->sendEmailNotification($fresh);

    return new PurchaseRequestQuoteAdjustmentRequestResource($fresh);
  }

  private function buildBonusItem(int $requestId, array $item, $couponsById, float $salePrice): void
  {
    $action = $item['action'];
    $existing = !empty($item['discount_coupon_id']) ? $couponsById->get($item['discount_coupon_id']) : null;

    $payload = [
      'adjustment_request_id' => $requestId,
      'action' => $action,
      'item_type' => PurchaseRequestQuoteAdjustmentItem::ITEM_TYPE_BONUS_DISCOUNT,
      'discount_coupon_id' => $item['discount_coupon_id'] ?? null,
      'concept_code_id' => $item['concept_code_id'] ?? $existing?->concept_code_id,
      'type' => $item['type'] ?? $existing?->type,
      'is_negative' => $existing?->is_negative ?? false,
      'has_retention' => (bool)($item['has_retention'] ?? $existing?->has_retention ?? false),
      'previous_valor_unitario' => $existing?->valor_unitario,
      'previous_precio_unitario' => $existing?->precio_unitario,
    ];

    if ($action !== PurchaseRequestQuoteAdjustmentItem::ACTION_DELETE) {
      $concept = ApMasters::find($payload['concept_code_id']);
      $payload['is_negative'] = $concept ? is_null($concept->parent_id) : ($existing?->is_negative ?? false);

      $amounts = $this->computeAmounts(
        $payload['type'],
        (float)($item['value'] ?? 0),
        $payload['has_retention'],
        $salePrice,
      );
      $payload['new_valor_unitario'] = $amounts['valorUnitario'];
      $payload['new_precio_unitario'] = $amounts['precioUnitario'];
    }

    PurchaseRequestQuoteAdjustmentItem::create($payload);
  }

  private function buildGiftItem(int $requestId, array $item, $giftsById, ?int $bodyTypeId): void
  {
    $action = $item['action'];
    $existing = !empty($item['accessory_detail_id']) ? $giftsById->get($item['accessory_detail_id']) : null;

    $payload = [
      'adjustment_request_id' => $requestId,
      'action' => $action,
      'item_type' => PurchaseRequestQuoteAdjustmentItem::ITEM_TYPE_GIFT,
      'accessory_detail_id' => $item['accessory_detail_id'] ?? null,
      'approved_accessory_id' => $item['approved_accessory_id'] ?? $existing?->approved_accessory_id,
      'body_type_id' => $existing?->body_type_id ?? $bodyTypeId,
      'quantity' => $item['quantity'] ?? $existing?->quantity,
      'additional_price' => $item['additional_price'] ?? $existing?->additional_price ?? 0,
      'previous_precio_unitario' => $existing ? (float)$existing->total : null,
    ];

    if ($action !== PurchaseRequestQuoteAdjustmentItem::ACTION_DELETE) {
      $pricing = $this->computeGiftAmounts(
        (int)$payload['approved_accessory_id'],
        (int)($payload['quantity'] ?? 0),
        (float)($payload['additional_price'] ?? 0),
        // al editar se mantiene el precio unitario ya fijado en la fila existente
        $existing ? (float)$existing->price : null,
        $bodyTypeId,
      );
      $payload['body_type_id'] = $pricing['body_type_id'] ?? $payload['body_type_id'];
      $payload['new_precio_unitario'] = $pricing['total'];
    }

    PurchaseRequestQuoteAdjustmentItem::create($payload);
  }

  public function approve($id): PurchaseRequestQuoteAdjustmentRequestResource
  {
    $record = $this->findPending($id);

    if (!auth()->user()?->hasPermission('solicitudes-cotizaciones.approveAdjustment')) {
      throw new Exception('No tiene permisos para aprobar solicitudes de ajuste de margen.');
    }

    $record->load(['items.accessoryDetail', 'purchaseRequestQuote']);
    $quote = $record->purchaseRequestQuote;

    DB::transaction(function () use ($record, $quote) {
      foreach ($record->items as $item) {
        $item->item_type === PurchaseRequestQuoteAdjustmentItem::ITEM_TYPE_GIFT
          ? $this->applyGiftItem($item, $quote)
          : $this->applyBonusItem($item, $quote);
      }

      $record->update([
        'status' => PurchaseRequestQuoteAdjustmentRequest::STATUS_APPROVED,
        'resolved_by_id' => auth()->id(),
        'resolved_at' => now(),
      ]);

      $this->purchaseRequestQuoteService->refreshMargin($quote);
    });

    $fresh = $record->fresh([
      'items.conceptCode',
      'items.accessoryDetail.approvedAccessory',
      'items.approvedAccessory',
      'purchaseRequestQuote.holder',
      'requestedBy',
      'resolvedBy',
    ]);

    $this->sendResolutionNotification($fresh, approved: true);

    return new PurchaseRequestQuoteAdjustmentRequestResource($fresh);
  }

  private function applyBonusItem(PurchaseRequestQuoteAdjustmentItem $item, PurchaseRequestQuote $quote): void
  {
    match ($item->action) {
      PurchaseRequestQuoteAdjustmentItem::ACTION_CREATE => DiscountCoupons::create([
        'type' => $item->type,
        'percentage' => 0,
        'amount' => $item->new_precio_unitario,
        'valor_unitario' => $item->new_valor_unitario,
        'precio_unitario' => $item->new_precio_unitario,
        'is_negative' => $item->is_negative,
        'has_retention' => $item->has_retention,
        'concept_code_id' => $item->concept_code_id,
        'purchase_request_quote_id' => $quote->id,
      ]),
      PurchaseRequestQuoteAdjustmentItem::ACTION_UPDATE => $item->discountCoupon?->update([
        'type' => $item->type,
        'valor_unitario' => $item->new_valor_unitario,
        'precio_unitario' => $item->new_precio_unitario,
        'is_negative' => $item->is_negative,
        'has_retention' => $item->has_retention,
        'concept_code_id' => $item->concept_code_id,
      ]),
      PurchaseRequestQuoteAdjustmentItem::ACTION_DELETE => $item->discountCoupon?->delete(),
      default => null,
    };
  }

  private function applyGiftItem(PurchaseRequestQuoteAdjustmentItem $item, PurchaseRequestQuote $quote): void
  {
    $unitPrice = $item->quantity > 0
      ? ($item->new_precio_unitario / $item->quantity) - (float)$item->additional_price
      : 0;

    match ($item->action) {
      PurchaseRequestQuoteAdjustmentItem::ACTION_CREATE => DetailsApprovedAccessoriesQuote::create([
        'approved_accessory_id' => $item->approved_accessory_id,
        'body_type_id' => $item->body_type_id,
        'type' => 'OBSEQUIO',
        'quantity' => $item->quantity,
        'price' => round(max(0, $unitPrice), 4),
        'additional_price' => (float)$item->additional_price,
        'total' => $item->new_precio_unitario,
        'type_currency_id' => ApprovedAccessories::whereKey($item->approved_accessory_id)->value('type_currency_id'),
        'purchase_request_quote_id' => $quote->id,
      ]),
      PurchaseRequestQuoteAdjustmentItem::ACTION_UPDATE => $item->accessoryDetail?->update([
        'quantity' => $item->quantity,
        'additional_price' => (float)$item->additional_price,
        'total' => $item->new_precio_unitario,
      ]),
      PurchaseRequestQuoteAdjustmentItem::ACTION_DELETE => $item->accessoryDetail?->delete(),
      default => null,
    };
  }

  public function reject($id, ?string $reason = null): PurchaseRequestQuoteAdjustmentRequestResource
  {
    $record = $this->findPending($id);

    if (!auth()->user()?->hasPermission('solicitudes-cotizaciones.rejectAdjustment')) {
      throw new Exception('No tiene permisos para rechazar solicitudes de ajuste de margen.');
    }

    DB::transaction(function () use ($record, $reason) {
      $record->update([
        'status' => PurchaseRequestQuoteAdjustmentRequest::STATUS_REJECTED,
        'resolved_by_id' => auth()->id(),
        'resolved_at' => now(),
        'rejection_reason' => $reason,
      ]);
    });

    $fresh = $record->fresh([
      'items.conceptCode',
      'items.accessoryDetail.approvedAccessory',
      'items.approvedAccessory',
      'purchaseRequestQuote.holder',
      'requestedBy',
      'resolvedBy',
    ]);

    $this->sendResolutionNotification($fresh, approved: false);

    return new PurchaseRequestQuoteAdjustmentRequestResource($fresh);
  }

  /**
   * No aplica: una solicitud de ajuste no se edita, solo se aprueba/rechaza/cancela.
   */
  public function update(mixed $data)
  {
    throw new Exception('Una solicitud de ajuste no puede editarse. Cáncelela y cree una nueva si es necesario.');
  }

  public function destroy(int $id): void
  {
    $record = $this->findPending($id);

    if ($record->requested_by_id !== auth()->id()) {
      throw new Exception('Solo quien creó la solicitud puede cancelarla.');
    }

    $record->delete();
  }

  private function findPending($id): PurchaseRequestQuoteAdjustmentRequest
  {
    $record = $this->find($id);
    if ($record->status !== PurchaseRequestQuoteAdjustmentRequest::STATUS_PENDING) {
      throw new Exception('Esta solicitud de ajuste ya fue procesada.');
    }
    return $record;
  }

  /**
   * Aplica en memoria (sin tocar la BD) las líneas de la solicitud sobre las
   * colecciones actuales de bonos/descuentos y obsequios, para simular el margen.
   *
   * @return array{coupons: array, gifts: array}
   */
  private function simulateItems(PurchaseRequestQuote $quote, array $items, ?int $bodyTypeId): array
  {
    $coupons = $quote->discountCoupons->map(fn($d) => (object)[
      'id' => $d->id,
      'is_negative' => $d->is_negative,
      'precio_unitario' => (float)$d->precio_unitario,
    ])->keyBy('id')->all();

    $gifts = $quote->accessories->where('type', 'OBSEQUIO')->map(fn($a) => (object)[
      'id' => $a->id,
      'total' => (float)$a->total,
      'price' => (float)$a->price,
    ])->keyBy('id')->all();

    foreach ($items as $item) {
      $action = $item['action'];
      $itemType = $item['item_type'] ?? PurchaseRequestQuoteAdjustmentItem::ITEM_TYPE_BONUS_DISCOUNT;

      if ($itemType === PurchaseRequestQuoteAdjustmentItem::ITEM_TYPE_GIFT) {
        if ($action === PurchaseRequestQuoteAdjustmentItem::ACTION_DELETE) {
          if (!empty($item['accessory_detail_id'])) {
            unset($gifts[$item['accessory_detail_id']]);
          }
          continue;
        }

        $existing = !empty($item['accessory_detail_id']) ? ($gifts[$item['accessory_detail_id']] ?? null) : null;
        $pricing = $this->computeGiftAmounts(
          (int)($item['approved_accessory_id'] ?? $existing?->id ?? 0),
          (int)($item['quantity'] ?? 0),
          (float)($item['additional_price'] ?? 0),
          $existing?->price,
          $bodyTypeId,
        );

        if ($action === PurchaseRequestQuoteAdjustmentItem::ACTION_UPDATE && !empty($item['accessory_detail_id'])) {
          $gifts[$item['accessory_detail_id']] = (object)['id' => $item['accessory_detail_id'], 'total' => $pricing['total'], 'price' => $pricing['price']];
        } else {
          $gifts['new_' . count($gifts)] = (object)['id' => null, 'total' => $pricing['total'], 'price' => $pricing['price']];
        }
        continue;
      }

      if ($action === PurchaseRequestQuoteAdjustmentItem::ACTION_DELETE) {
        if (!empty($item['discount_coupon_id'])) {
          unset($coupons[$item['discount_coupon_id']]);
        }
        continue;
      }

      $concept = ApMasters::find($item['concept_code_id'] ?? null);
      $isNegative = $concept ? is_null($concept->parent_id) : false;
      $hasRetention = (bool)($item['has_retention'] ?? false);
      $amounts = $this->computeAmounts($item['type'], (float)($item['value'] ?? 0), $hasRetention, (float)$quote->sale_price);

      if ($action === PurchaseRequestQuoteAdjustmentItem::ACTION_UPDATE && !empty($item['discount_coupon_id'])) {
        $coupons[$item['discount_coupon_id']] = (object)[
          'id' => $item['discount_coupon_id'],
          'is_negative' => $isNegative,
          'precio_unitario' => $amounts['precioUnitario'],
        ];
      } else {
        $coupons['new_' . count($coupons)] = (object)[
          'id' => null,
          'is_negative' => $isNegative,
          'precio_unitario' => $amounts['precioUnitario'],
        ];
      }
    }

    return ['coupons' => array_values($coupons), 'gifts' => array_values($gifts)];
  }

  /**
   * Reimplementación deliberada (no extraída) de la fórmula de margen de
   * PurchaseRequestQuoteService::calculateMargin(), pero recibiendo colecciones
   * simuladas de bonos/descuentos y obsequios en vez de las relaciones reales —
   * fuente de verdad de la fórmula: PurchaseRequestQuoteService::calculateMargin().
   */
  private function simulateMargin(PurchaseRequestQuote $quote, array $simulatedCoupons, array $simulatedGifts): array
  {
    $vehicle = $quote->vehicle;
    $salePrice = (float)$quote->base_selling_price;
    $billedCost = $vehicle ? (float)$vehicle->purchase_price : 0;

    if (!$vehicle || !$vehicle->vin || $billedCost <= 0 || $salePrice <= 0) {
      return ['margin_amount' => 0, 'margin_pct' => 0];
    }

    $bonusTotal = 0.0;
    $discountTotal = 0.0;
    foreach ($simulatedCoupons as $d) {
      $d->is_negative ? $discountTotal += $d->precio_unitario : $bonusTotal += $d->precio_unitario;
    }

    // Accesorios pagados (no obsequio) siguen fijos: se leen de la relación real.
    $paidAccTotal = 0.0;
    foreach ($quote->accessories as $acc) {
      if ($acc->type !== 'OBSEQUIO') {
        $paidAccTotal += (float)$acc->total;
      }
    }

    // Obsequios: colección simulada.
    $giftTotal = 0.0;
    foreach ($simulatedGifts as $g) {
      $giftTotal += (float)$g->total;
    }

    $extraCostsTotal = 0.0;
    $fleteRows = [];
    foreach ($quote->others as $other) {
      if ($other->is_locked) {
        $fleteRows[] = $other;
      } else {
        $extraCostsTotal += (float)$other->amount;
      }
    }

    $clientRevenue = $salePrice - $discountTotal + $paidAccTotal;
    $totalIncome = $clientRevenue + $bonusTotal;
    $vehicleCosts = $billedCost + $giftTotal + $extraCostsTotal;

    $grossDiff = $totalIncome - $vehicleCosts;
    $netDiff = $grossDiff / 1.18;
    $netSalePrice = $salePrice / 1.18;

    $othersNetTotal = 0.0;
    foreach ($fleteRows as $flete) {
      $othersNetTotal += $flete->type === 'PORCENTAJE'
        ? ((float)$flete->value / 100) * $netSalePrice
        : (float)$flete->value;
    }

    $realMarginAmount = $netDiff - $othersNetTotal;
    $realMarginPct = $netSalePrice > 0 ? ($realMarginAmount / $netSalePrice) * 100 : 0;

    return [
      'margin_amount' => round($realMarginAmount, 4),
      'margin_pct' => round($realMarginPct, 4),
    ];
  }

  /**
   * Misma fórmula que PurchaseRequestQuoteService::saveBonusDiscounts():
   * `value` ya es el monto final (la retención del 7% viene aplicada desde el
   * front al agregar; al editar se usa el valor tal cual). No se recalcula.
   */
  private function computeAmounts(?string $type, float $value, bool $hasRetention, float $salePrice): array
  {
    if ($type === 'FIJO') {
      $amount = $value;
      $percentage = $salePrice > 0 ? ($amount / $salePrice) * 100 : 0;
    } else { // PORCENTAJE
      $percentage = $value;
      $amount = ($salePrice * $percentage) / 100;
    }

    $precioUnitario = $amount;
    $valorUnitario = $precioUnitario / 1.18;

    return [
      'percentage' => $percentage,
      'amount' => $amount,
      'valorUnitario' => $valorUnitario,
      'precioUnitario' => $precioUnitario,
    ];
  }

  /**
   * Resuelve precio unitario y total de un obsequio, igual que
   * PurchaseRequestQuoteService::saveAccessories(): el precio depende de la
   * carrocería del modelo; al editar se conserva el precio unitario ya fijado.
   *
   * @return array{price: float, body_type_id: ?int, total: float}
   */
  private function computeGiftAmounts(int $approvedAccessoryId, int $quantity, float $additionalPrice, ?float $existingUnitPrice, ?int $bodyTypeId): array
  {
    $additionalPrice = max(0, $additionalPrice);
    $quantity = max(0, $quantity);

    if ($existingUnitPrice !== null) {
      $price = (float)$existingUnitPrice;
      $resolvedBodyType = $bodyTypeId;
    } else {
      $approved = ApprovedAccessories::with('prices')->find($approvedAccessoryId);
      if (!$approved) {
        throw new Exception('Accesorio homologado no encontrado para el obsequio.');
      }
      $priceRow = $bodyTypeId ? $approved->prices->firstWhere('body_type_id', $bodyTypeId) : null;
      $priceRow = $priceRow ?: $approved->prices->first();
      if (!$priceRow) {
        throw new Exception('El accesorio "' . $approved->description . '" no tiene un precio configurado.');
      }
      $price = (float)$priceRow->price;
      $resolvedBodyType = $priceRow->body_type_id;
    }

    return [
      'price' => $price,
      'body_type_id' => $resolvedBodyType,
      'total' => $quantity * ($price + $additionalPrice),
    ];
  }

  private function sendEmailNotification(PurchaseRequestQuoteAdjustmentRequest $record): void
  {
    try {
      $quote = $record->purchaseRequestQuote;
      $recipients = (array)config('mail.recipients.purchase_quote_adjustment.accounting', []);
      if (empty($recipients)) {
        return;
      }

      $data = $this->buildEmailData($record);
      $subject = 'Nueva solicitud de ajuste de margen — Cotización #' . ($quote->correlative ?? $quote->id);

      $this->emailService->send([
        'to' => $recipients,
        'subject' => $subject,
        'template' => 'emails.purchase-request-quote-adjustment-notification',
        'data' => $data,
      ]);
    } catch (Exception $e) {
      Log::error('Error al enviar notificación de solicitud de ajuste de margen: ' . $e->getMessage());
    }
  }

  private function sendResolutionNotification(PurchaseRequestQuoteAdjustmentRequest $record, bool $approved): void
  {
    try {
      $requester = $record->requestedBy;
      if (!$requester?->email) {
        return;
      }

      $data = $this->buildEmailData($record);
      $quote = $record->purchaseRequestQuote;
      $subject = ($approved ? 'Ajuste de margen aprobado' : 'Ajuste de margen rechazado')
        . ' — Cotización #' . ($quote->correlative ?? $quote->id);

      $this->emailService->send([
        'to' => $requester->email,
        'subject' => $subject,
        'template' => $approved
          ? 'emails.purchase-request-quote-adjustment-approved'
          : 'emails.purchase-request-quote-adjustment-rejected',
        'data' => $data,
      ]);
    } catch (Exception $e) {
      Log::error('Error al enviar notificación de resolución de ajuste de margen: ' . $e->getMessage());
    }
  }

  private function buildEmailData(PurchaseRequestQuoteAdjustmentRequest $record): array
  {
    $quote = $record->purchaseRequestQuote;

    return [
      'quote_number' => $quote->correlative ?? $quote->id,
      'holder_name' => $quote->holder->full_name ?? null,
      'requester_name' => $record->requestedBy->name ?? 'Comercial',
      'reason' => $record->reason,
      'currency_symbol' => $quote->docTypeCurrency->symbol ?? 'S/',
      'margin_amount_before' => (float)$record->margin_amount_before,
      'margin_pct_before' => (float)$record->margin_pct_before,
      'margin_amount_after' => (float)$record->margin_amount_after,
      'margin_pct_after' => (float)$record->margin_pct_after,
      'rejection_reason' => $record->rejection_reason,
      'resolver_name' => $record->resolvedBy->name ?? null,
      'items' => $record->items->map(fn($item) => [
        'action' => $item->action,
        'item_type' => $item->item_type,
        'concept' => $item->item_type === PurchaseRequestQuoteAdjustmentItem::ITEM_TYPE_GIFT
          ? ('Obsequio: ' . ($item->accessoryDetail->approvedAccessory->description
            ?? $item->approvedAccessory->description
            ?? 'Accesorio'))
          : ($item->conceptCode->description ?? null),
        'previous_precio_unitario' => $item->previous_precio_unitario,
        'new_precio_unitario' => $item->new_precio_unitario,
      ])->toArray(),
      'button_url' => config('app.frontend_url') . '/ap/comercial/solicitudes-cotizaciones/ajustes-margen/' . $record->id,
    ];
  }
}
