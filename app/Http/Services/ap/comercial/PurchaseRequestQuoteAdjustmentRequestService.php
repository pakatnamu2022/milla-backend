<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\PurchaseRequestQuoteAdjustmentRequestResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\EmailService;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\DiscountCoupons;
use App\Models\ap\comercial\PurchaseRequestQuote;
use App\Models\ap\comercial\PurchaseRequestQuoteAdjustmentItem;
use App\Models\ap\comercial\PurchaseRequestQuoteAdjustmentRequest;
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
    $record->load(['items.conceptCode', 'items.discountCoupon', 'purchaseRequestQuote', 'requestedBy', 'resolvedBy']);
    return new PurchaseRequestQuoteAdjustmentRequestResource($record);
  }

  public function store(mixed $data): PurchaseRequestQuoteAdjustmentRequestResource
  {
    $quote = PurchaseRequestQuote::find($data['purchase_request_quote_id']);
    if (!$quote) {
      throw new Exception('La solicitud/cotización no existe.');
    }

    if (!$quote->is_paid) {
      throw new Exception('Solo se pueden solicitar ajustes de bono/descuento sobre cotizaciones ya pagadas.');
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
    $marginBefore = $this->purchaseRequestQuoteService->calculateMargin($quote);
    $simulated = $this->simulateItems($quote, $items);
    $marginAfter = $this->simulateMargin($quote, $simulated);

    $record = DB::transaction(function () use ($quote, $data, $items, $marginBefore, $marginAfter) {
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

      $existingById = $quote->discountCoupons->keyBy('id');

      foreach ($items as $item) {
        $action = $item['action'];
        $existing = !empty($item['discount_coupon_id']) ? $existingById->get($item['discount_coupon_id']) : null;

        $payload = [
          'adjustment_request_id' => $request->id,
          'action' => $action,
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
            (float)$quote->sale_price,
          );
          $payload['new_valor_unitario'] = $amounts['valorUnitario'];
          $payload['new_precio_unitario'] = $amounts['precioUnitario'];
        }

        PurchaseRequestQuoteAdjustmentItem::create($payload);
      }

      return $request;
    });

    $fresh = $record->fresh(['items.conceptCode', 'purchaseRequestQuote.holder', 'requestedBy']);

    $this->sendEmailNotification($fresh);

    return new PurchaseRequestQuoteAdjustmentRequestResource($fresh);
  }

  public function approve($id): PurchaseRequestQuoteAdjustmentRequestResource
  {
    $record = $this->findPending($id);

    if (!auth()->user()?->hasPermission('solicitudes-cotizaciones.approveAdjustment')) {
      throw new Exception('No tiene permisos para aprobar solicitudes de ajuste de margen.');
    }

    $record->load(['items', 'purchaseRequestQuote']);
    $quote = $record->purchaseRequestQuote;

    DB::transaction(function () use ($record, $quote) {
      foreach ($record->items as $item) {
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

      $record->update([
        'status' => PurchaseRequestQuoteAdjustmentRequest::STATUS_APPROVED,
        'resolved_by_id' => auth()->id(),
        'resolved_at' => now(),
      ]);

      $this->purchaseRequestQuoteService->refreshMargin($quote);
    });

    $fresh = $record->fresh(['items.conceptCode', 'purchaseRequestQuote.holder', 'requestedBy', 'resolvedBy']);

    $this->sendResolutionNotification($fresh, approved: true);

    return new PurchaseRequestQuoteAdjustmentRequestResource($fresh);
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

    $fresh = $record->fresh(['items.conceptCode', 'purchaseRequestQuote.holder', 'requestedBy', 'resolvedBy']);

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
   * Aplica en memoria (sin tocar discount_coupons) las líneas de la solicitud
   * sobre la colección actual de bonos/descuentos, para poder simular el margen.
   */
  private function simulateItems(PurchaseRequestQuote $quote, array $items): array
  {
    $simulated = $quote->discountCoupons->map(fn($d) => (object)[
      'id' => $d->id,
      'is_negative' => $d->is_negative,
      'precio_unitario' => (float)$d->precio_unitario,
    ])->keyBy('id')->all();

    foreach ($items as $item) {
      $action = $item['action'];

      if ($action === PurchaseRequestQuoteAdjustmentItem::ACTION_DELETE) {
        if (!empty($item['discount_coupon_id'])) {
          unset($simulated[$item['discount_coupon_id']]);
        }
        continue;
      }

      $concept = ApMasters::find($item['concept_code_id'] ?? null);
      $isNegative = $concept ? is_null($concept->parent_id) : false;
      $hasRetention = (bool)($item['has_retention'] ?? false);
      $amounts = $this->computeAmounts($item['type'], (float)($item['value'] ?? 0), $hasRetention, (float)$quote->sale_price);

      if ($action === PurchaseRequestQuoteAdjustmentItem::ACTION_UPDATE && !empty($item['discount_coupon_id'])) {
        $simulated[$item['discount_coupon_id']] = (object)[
          'id' => $item['discount_coupon_id'],
          'is_negative' => $isNegative,
          'precio_unitario' => $amounts['precioUnitario'],
        ];
      } else {
        // create (o update sin discount_coupon_id resuelto): usar clave temporal negativa para no chocar con ids reales
        $tempKey = 'new_' . count($simulated);
        $simulated[$tempKey] = (object)[
          'id' => null,
          'is_negative' => $isNegative,
          'precio_unitario' => $amounts['precioUnitario'],
        ];
      }
    }

    return array_values($simulated);
  }

  /**
   * Reimplementación deliberada (no extraída) de la fórmula de margen de
   * PurchaseRequestQuoteService::calculateMargin(), pero recibiendo una
   * colección simulada de bonos/descuentos en vez de la relación real —
   * fuente de verdad de la fórmula: PurchaseRequestQuoteService::calculateMargin().
   */
  private function simulateMargin(PurchaseRequestQuote $quote, array $simulatedCoupons): array
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

    $paidAccTotal = 0.0;
    $giftTotal = 0.0;
    foreach ($quote->accessories as $acc) {
      $acc->type === 'OBSEQUIO' ? $giftTotal += (float)$acc->total : $paidAccTotal += (float)$acc->total;
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
        'concept' => $item->conceptCode->description ?? null,
        'previous_precio_unitario' => $item->previous_precio_unitario,
        'new_precio_unitario' => $item->new_precio_unitario,
      ])->toArray(),
      'button_url' => config('app.frontend_url') . '/ap/comercial/solicitudes-cotizaciones/ajustes-margen/' . $record->id,
    ];
  }
}
