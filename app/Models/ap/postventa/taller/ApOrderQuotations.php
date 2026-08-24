<?php

namespace App\Models\ap\postventa\taller;

use App\Http\Services\ap\postventa\taller\OrderQuotationAdvancePaymentService;
use App\Http\Services\ap\postventa\taller\OrderQuotationBillingService;
use App\Http\Services\ap\postventa\taller\OrderQuotationDocumentService;
use App\Http\Traits\HasExchangeRateToUsd;
use App\Http\Utils\Constants;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\gp\maestroGeneral\ExchangeRate;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\DiscountRequestsOrderQuotation;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApOrderQuotations extends Model
{
  use softDeletes, HasExchangeRateToUsd;

  // Lazy-loaded service instances (singleton pattern per model instance)
  private ?OrderQuotationDocumentService $documentService = null;
  private ?OrderQuotationAdvancePaymentService $advancePaymentService = null;
  private ?OrderQuotationBillingService $billingService = null;

  protected $table = 'ap_order_quotations';

  protected $fillable = [
    'vehicle_id',
    'client_id',
    'sede_id',
    'quotation_number',
    'subtotal',
    'mileage',
    'discount_percentage',
    'discount_amount',
    'tax_amount',
    'total_amount',
    'deductible_amount',
    'validity_days',
    'quotation_date',
    'expiration_date',
    'collection_date',
    'observations',
    'notes',
    'created_by',
    'is_take',
    'is_take_ot',
    'is_requested_by_management',
    'emails_sent_count',
    'area_id',
    'invoice_to',
    'currency_id',
    'exchange_rate_id',
    'exchange_rate',
    'has_invoice_generated',
    'is_fully_paid',
    'output_generation_warehouse',
    'discard_reason_id',
    'discarded_note',
    'discarded_by',
    'discarded_at',
    'supply_type',
    'customer_signature_url',
    'customer_signature_delivery_url',
    'delivery_document_number',
    'chief_approval_by',
    'manager_approval_by',
    'status_id',
    'confirmation_token',
    'confirmation_token_expires_at',
    'confirmed_at',
    'confirmation_channel',
    'confirmation_ip',
    'confirmation_metadata',
    'parent_quotation_id',
    'shipping_guide_id',
    'is_sold_at_valid_price',
  ];

  const filters = [
    'search' => ['quotation_number', 'observations', 'vehicle.customer.full_name', 'client.full_name', 'client.num_doc', 'vehicle.plate'],
    'vehicle_id' => '=',
    'quotation_date' => 'between',
    'is_take' => '=',
    'area_id' => '=',
    'currency_id' => '=',
    'discard_reason_id' => '=',
    'status_id' => 'in_or_equal',
    'sede_id' => '=',
    'supply_type' => 'in',
    'has_invoice_generated' => '=',
    'is_take_ot' => '='
  ];

  const sorts = [
    'id',
    'quotation_number',
    'quotation_date',
    'total_amount',
    'created_at',
  ];

  protected $casts = [
    'quotation_date' => 'datetime',
    'expiration_date' => 'datetime',
    'collection_date' => 'datetime',
    'discarded_at' => 'datetime',
    'has_invoice_generated' => 'boolean',
    'is_fully_paid' => 'boolean',
    'is_take_ot' => 'boolean',
    'is_requested_by_management' => 'boolean',
    'confirmation_token_expires_at' => 'datetime',
    'confirmed_at' => 'datetime',
    'confirmation_metadata' => 'array',
    'is_sold_at_valid_price' => 'boolean',
  ];

  // DIAS PERMITIDOS PARA EDITAR O ELIMINAR UNA COTIZACION
  const  DAYS_TO_EDIT_OR_DELETE = 15;

  // CONFIRMATION CHANNEL CONSTANTS
  const CONFIRMATION_CHANNEL_PRESENCIAL = 'presencial';
  const CONFIRMATION_CHANNEL_VIRTUAL = 'virtual';

  // DIAS DE VALIDEZ DEL TOKEN DE CONFIRMACION
  const CONFIRMATION_TOKEN_VALIDITY_DAYS = 30;

  protected static function boot()
  {
    parent::boot();

    // when deleting a quotation, also delete its details
    static::deleting(function ($quotation) {
      $quotation->details()->delete();
    });
  }

  public function setDiscardedNoteAttribute($value)
  {
    $this->attributes['discarded_note'] = strtoupper($value);
  }

  public function setObservationsAttribute($value)
  {
    $this->attributes['observations'] = strtoupper($value);
  }

  public function setNotesAttribute($value)
  {
    $this->attributes['notes'] = strtoupper($value);
  }

  public function getDeductibleAmountWithoutTaxAttribute()
  {
    if (!$this->deductible_amount) {
      return 0;
    }

    return (float)($this->deductible_amount / (1 + Constants::VAT_TAX / 100));
  }

  // Service Helpers (lazy-loaded singletons per instance)
  protected function documentService(): OrderQuotationDocumentService
  {
    if ($this->documentService === null) {
      $this->documentService = app(OrderQuotationDocumentService::class);
    }
    return $this->documentService;
  }

  protected function advancePaymentService(): OrderQuotationAdvancePaymentService
  {
    if ($this->advancePaymentService === null) {
      $this->advancePaymentService = app(OrderQuotationAdvancePaymentService::class);
    }
    return $this->advancePaymentService;
  }

  protected function billingService(): OrderQuotationBillingService
  {
    if ($this->billingService === null) {
      $this->billingService = app(OrderQuotationBillingService::class);
    }
    return $this->billingService;
  }

  // Relations
  public function vehicle(): BelongsTo
  {
    return $this->belongsTo(Vehicles::class, 'vehicle_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function chiefApprovalBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'chief_approval_by');
  }

  public function managerApprovalBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'manager_approval_by');
  }

  public function area(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'area_id');
  }

  public function invoiceTo(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'invoice_to');
  }

  public function typeCurrency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_id');
  }

  public function exchangeRate(): BelongsTo
  {
    return $this->belongsTo(ExchangeRate::class, 'exchange_rate_id');
  }

  public function client(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'client_id');
  }

  public function status(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'status_id');
  }

  public function details()
  {
    return $this->hasMany(ApOrderQuotationDetails::class, 'order_quotation_id');
  }

  public function advancesOrderQuotation(): HasMany
  {
    return $this->hasMany(ElectronicDocument::class, 'order_quotation_id');
  }

  /**
   * @see HasExchangeRateToUsd
   */
  public function exchangeRateDocuments(): HasMany
  {
    return $this->advancesOrderQuotation();
  }

  public function discardReason(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'discard_reason_id');
  }

  public function discardedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'discarded_by');
  }

  public function discountRequests()
  {
    return $this->hasMany(DiscountRequestsOrderQuotation::class, 'ap_order_quotation_id');
  }

  public function workOrders(): HasMany
  {
    return $this->hasMany(
      ApWorkOrder::class,
      'order_quotation_id'
    );
  }

  public function parentQuotation(): BelongsTo
  {
    return $this->belongsTo(ApOrderQuotations::class, 'parent_quotation_id');
  }

  public function segmentedQuotations(): HasMany
  {
    return $this->hasMany(ApOrderQuotations::class, 'parent_quotation_id');
  }

  public function shippingGuide(): BelongsTo
  {
    return $this->belongsTo(ShippingGuides::class, 'shipping_guide_id');
  }

  public function deductibles(): HasMany
  {
    return $this->hasMany(ApDeductibleOrderQuotation::class, 'order_quotation_id');
  }

  /**
   * Asocia una guía de remisión a esta cotización
   *
   * @param int $shippingGuideId ID de la guía de remisión
   * @return void
   * @throws \Exception si la guía no existe, está anulada o ya está asociada
   */
  public function associateShippingGuide(int $shippingGuideId): void
  {
    $shippingGuide = ShippingGuides::find($shippingGuideId);

    if (!$shippingGuide) {
      throw new Exception('La guía de remisión no existe');
    }

    if ($shippingGuide->cancelled_at) {
      throw new Exception('No se puede asociar una guía de remisión anulada');
    }

    if (!$shippingGuide->status) {
      throw new Exception('No se puede asociar una guía de remisión inactiva');
    }

    // Verificar si ya está asociada a otra cotización
    $existingAssociation = self::where('shipping_guide_id', $shippingGuideId)
      ->where('id', '!=', $this->id)
      ->first();

    if ($existingAssociation) {
      throw new Exception("La guía de remisión ya está asociada a la cotización {$existingAssociation->quotation_number}");
    }

    $this->shipping_guide_id = $shippingGuideId;
    $this->save();
  }

  /**
   * Desasocia la guía de remisión de esta cotización
   *
   * @return void
   */
  public function dissociateShippingGuide(): void
  {
    $this->shipping_guide_id = null;
    $this->save();
  }

  public function markAsTaken(): void
  {
    $this->is_take = 1;
    $this->save();
  }

  public static function generateNextQuotationNumber(int $sedeId): string
  {
    $sede = Sede::find($sedeId);
    if (!$sede) {
      throw new Exception('sede no encontrada');
    }

    $dynCode = $sede->dyn_code;
    $year = date('Y');
    $month = date('m');
    $prefix = "COT-{$dynCode}-{$year}{$month}";

    $lastQuotation = self::withTrashed()
      ->where('quotation_number', 'like', "{$prefix}%")
      ->orderBy('quotation_number', 'desc')
      ->lockForUpdate()
      ->first();

    if ($lastQuotation) {
      $lastNumber = (int)substr($lastQuotation->quotation_number, -4);
      $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    } else {
      $newNumber = '0001';
    }

    return "{$prefix}{$newNumber}";
  }

  /**
   * Calcula y actualiza el campo is_sold_at_valid_price según los precios de venta de los items.
   * Evalúa si TODOS los productos se vendieron al precio mínimo o superior comparando
   * con el sale_price_min_original guardado en cada detalle.
   *
   * @return void
   */
  public function calculateIsSoldAtValidPrice(): void
  {
    // Obtener todos los detalles de tipo PRODUCT (excluir mano de obra)
    $productDetails = $this->details()
      ->where('item_type', ApOrderQuotationDetails::ITEM_TYPE_PRODUCT)
      ->whereNotNull('product_id')
      ->get();

    // Si no hay productos, marcar como true
    if ($productDetails->isEmpty()) {
      $this->is_sold_at_valid_price = true;
      return;
    }

    // Validar cada producto
    foreach ($productDetails as $detail) {
      // Bypass: Si es travesía, no validar precio
      if ($detail->is_traverse) {
        continue;
      }

      // Si no tiene sale_price_min_original guardado, continuar (no afecta la validación)
      if (!$detail->sale_price_min_original || $detail->sale_price_min_original == 0) {
        continue;
      }

      // Convertir unit_price a soles si está en USD
      $unitPriceInSoles = $detail->unit_price;
      if ($this->currency_id === TypeCurrency::USD_ID && $this->exchange_rate) {
        $unitPriceInSoles = $detail->unit_price * $this->exchange_rate;
      }

      // Si el precio unitario es menor al precio mínimo original, marcar como false
      if ($unitPriceInSoles < $detail->sale_price_min_original) {
        $this->is_sold_at_valid_price = false;
        return;
      }
    }

    // Si llegamos aquí, todos los productos cumplen con el precio mínimo
    $this->is_sold_at_valid_price = true;
  }

  /**
   * Centralized method to calculate and update quotation totals based on details.
   * This method calculates:
   * - subtotal: sum of all items (quantity * unit_price) without discounts
   * - discount_amount: total discount amount in money
   * - discount_percentage: average discount percentage
   * - tax_amount: IGV (18%) calculated on subtotal after discounts
   * - total_amount: final total including discounts and taxes
   *
   * @return void
   */
  public function calculateTotals(): void
  {
    // Get all details for this quotation
    $details = $this->details;

    // Sumar total_cost de todos los items (sin descuento)
    $subtotal = $details->sum('total_cost') ?? 0;

    // Sumar net_amount de todos los items (con descuento aplicado)
    $sumNetAmountItems = $details->sum('net_amount') ?? 0;

    // Sumar tax_amount de todos los items (ya calculados a nivel de item)
    $taxAmount = $details->sum('tax_amount') ?? 0;

    // Calculate discount amount (cuánto se descontó en total en dinero)
    $discountAmount = $subtotal - $sumNetAmountItems;

    // Calculate discount percentage (porcentaje promedio de descuento)
    $discountPercentage = $subtotal > 0 ? ($discountAmount / $subtotal) * 100 : 0;

    // Calculate total amount (suma de net_amount items + tax_amount)
    $totalAmount = $sumNetAmountItems + $taxAmount;

    // Update quotation with all calculated values. Redondeo a 2 decimales:
    // los detalles (ApOrderQuotationDetails) ya llegan redondeados a 2 decimales desde
    // ApOrderQuotationDetailsService, esto es un resguardo ante arrastre de precisión
    // flotante al sumar varios ítems, igual que en ApWorkOrder::calculateTotals().
    $this->subtotal = round($subtotal, 2);
    $this->discount_amount = round($discountAmount, 2);
    $this->discount_percentage = round($discountPercentage, 2);
    $this->tax_amount = round($taxAmount, 2);
    $this->total_amount = round($totalAmount, 2);
  }

  /**
   * Genera un token único para confirmación virtual
   */
  public function generateConfirmationToken(): string
  {
    $token = Str::random(64);
    $expiresAt = Carbon::now()->addDays(self::CONFIRMATION_TOKEN_VALIDITY_DAYS);

    $this->confirmation_token = $token;
    $this->confirmation_token_expires_at = $expiresAt;
    $this->save();

    return $token;
  }

  /**
   * Verifica si el token de confirmación ha expirado
   */
  public function isConfirmationTokenExpired(): bool
  {
    if (!$this->confirmation_token_expires_at) {
      return true;
    }

    return Carbon::now()->isAfter($this->confirmation_token_expires_at);
  }

  /**
   * Verifica si la cotización ya fue confirmada
   */
  public function isConfirmed(): bool
  {
    return $this->confirmed_at !== null;
  }

  /**
   * Genera el link de confirmación virtual
   */
  public function getConfirmationLink(): string
  {
    if (!$this->confirmation_token) {
      $this->generateConfirmationToken();
    }

    $frontendUrl = config('app.frontend_url');
    return "{$frontendUrl}/confirmacion-cotizacion/{$this->confirmation_token}";
  }

  /**
   * Check if there's a draft final invoice for this quotation.
   *
   * @return bool
   */
  public function hasDraftFinalInvoice(): bool
  {
    return $this->documentService()->hasDraftFinalInvoice($this);
  }

  /**
   * Check if there's a draft advance payment for this quotation.
   *
   * @return bool
   */
  public function hasDraftAdvance(): bool
  {
    return $this->documentService()->hasDraftAdvance($this);
  }

  /**
   * Get active advances for this quotation.
   *
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getActiveAdvances()
  {
    return $this->documentService()->getActiveAdvances($this);
  }

  /**
   * Get cancelled advances for this quotation.
   *
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getCancelledAdvances()
  {
    return $this->documentService()->getCancelledAdvances($this);
  }

  /**
   * Get the final invoice (factura/boleta final) for this quotation.
   *
   * @return ElectronicDocument|null
   */
  public function getFinalInvoice()
  {
    return $this->documentService()->getFinalInvoice($this);
  }

  /**
   * Check if there's a final invoice already generated and accepted.
   *
   * @return bool
   */
  public function hasFinalInvoice(): bool
  {
    return $this->getFinalInvoice() !== null;
  }

  /**
   * Check if there are any active advances for this quotation.
   * Optimized for validation - uses exists() instead of loading all records.
   *
   * @return bool
   */
  public function hasAdvances(): bool
  {
    return $this->documentService()->hasActiveAdvances($this);
  }

  /**
   * Get all valid documents for this quotation (advances + final invoice).
   *
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getValidDocuments()
  {
    return $this->documentService()->getValidDocuments($this);
  }

  /**
   * Obtiene el monto neto pagado en anticipos activos
   * Considera notas de crédito y débito sobre los anticipos
   * (suma de anticipos - NC parciales + ND sobre esos anticipos)
   *
   * @return float
   */
  public function getNetAmountFromAdvances(): float
  {
    return $this->advancePaymentService()->getNetAmountFromAdvances($this);
  }

  /**
   * Obtiene el total gravada (sin IGV) de los anticipos activos
   * Usa directamente los totales gravados almacenados en los documentos electrónicos
   * para evitar problemas de redondeo por divisiones
   *
   * @return float
   */
  public function getTotalGravadaFromAdvances(): float
  {
    return $this->advancePaymentService()->getTotalGravadaFromAdvances($this);
  }

  /**
   * Get all documents organized in a tree structure with cancelled and active documents.
   * Active documents include their credit/debit note modifications.
   *
   * @return array
   */
  public function getDocumentsTree(): array
  {
    return $this->documentService()->getDocumentsTree($this);
  }

  /**
   * Get payment summary information for this quotation.
   *
   * Returns only payment-related information without duplicating data already
   * available in the ApOrderQuotationsResource resource header (total_amount, subtotal, etc.)
   *
   * Uses rounding tolerance to account for IGV calculation differences.
   *
   * @return array
   */
  public function getPaymentSummary(): array
  {
    return $this->documentService()->getPaymentSummary($this, $this->advancePaymentService());
  }

  /**
   * Construye el detalle de facturación (items_invoice) y sus totales (invoice_preview)
   * para esta cotización, igual patrón que ApWorkOrder::getInvoicePreview(). Solo se
   * consideran los detalles PENDIENTES: los ya tomados por una orden de trabajo se
   * facturan desde esa OT (ver ApWorkOrder::buildInvoiceItems()), no desde aquí, para
   * no facturarlos dos veces.
   *
   * @return array{items_invoice: array, invoice_preview: array}
   */
  public function getInvoicePreview(): array
  {
    return $this->billingService()->getInvoicePreview($this);
  }

  /**
   * Verifica si hay stock suficiente para todos los productos
   *
   * Lógica según estado de confirmación y supply_type:
   *
   * CONFIRMADA:
   *   - STOCK: valida quantity (físico) Y reserved_quantity (reservado)
   *   - NO STOCK (LOCAL/CENTRAL/IMPORTACION): valida available_quantity (libre actual)
   *
   * NO CONFIRMADA:
   *   - STOCK: valida available_quantity (para saber si se puede reservar)
   *   - NO STOCK: valida available_quantity (libre actual)
   *
   * @return bool
   */
  public function hasSufficientStock(): bool
  {
    // Get warehouse from sede
    $warehouse = Warehouse::getPhysicalWarehouseForPostsale($this->sede_id);

    // If no warehouse found, return false
    if (!$warehouse) {
      return false;
    }

    // Get all product details from quotation
    $productDetails = $this->details
      ->where('item_type', ApOrderQuotationDetails::ITEM_TYPE_PRODUCT)
      ->where('product_id', '!=', null);

    // If no products, return true
    if ($productDetails->isEmpty()) {
      return true;
    }

    // Determinar si la cotización ya está confirmada
    $isConfirmed = !is_null($this->confirmed_at);

    // Check stock for each product
    foreach ($productDetails as $detail) {
      // Bypass: si el repuesto tiene is_traverse = true, no validar stock
      if ($detail->is_traverse) {
        continue;
      }

      // Get stock for this product in this warehouse
      $stock = ProductWarehouseStock::where('warehouse_id', $warehouse->id)
        ->where('product_id', $detail->product_id)
        ->first();

      if (!$stock) {
        return false;
      }

      // Validación según confirmación y supply_type
      if ($isConfirmed && $detail->supply_type === ApOrderQuotationDetails::SUPPLY_TYPE_STOCK) {
        // Cotización confirmada + STOCK: validar físico Y reservado
        if ($stock->quantity < $detail->quantity || $stock->reserved_quantity < $detail->quantity) {
          return false;
        }
      } else {
        // Cualquier otro caso: validar stock disponible (libre)
        if ($stock->available_quantity < $detail->quantity) {
          return false;
        }
      }
    }

    // All products have sufficient stock
    return true;
  }

  /**
   * Determina si se puede generar un comprobante final de venta
   *
   * Este método es la fuente de verdad que gobierna si se puede emitir
   * un comprobante final (factura/boleta de venta interna código 01) o
   * un anticipo (código 04).
   *
   * Retorna un objeto con:
   * - can_final_receipt: boolean - puede generar comprobante final (venta interna)
   * - can_advance: boolean - puede generar anticipo
   * - is_toggle_enabled: boolean - si el usuario puede elegir entre ambos tipos
   * - message: string|null - mensaje explicativo de la restricción
   *
   * Reglas de negocio (según lógica del frontend):
   * 1. Sin stock → solo anticipo (fuerza is_advance_payment = true)
   * 2. Con stock pero remaining_balance = 0 → solo venta interna (fuerza is_advance_payment = false)
   * 3. Con stock y remaining_balance > 0 → usuario puede elegir (toggle habilitado)
   *
   * @return array{can_final_receipt: bool, can_advance: bool, is_toggle_enabled: bool, message: string|null}
   */
  public function canGenerateFinalReceipt(): array
  {
    // Verificar stock físico disponible
    $hasStock = $this->hasSufficientStock();

    // Obtener saldo pendiente
    $paymentSummary = $this->getPaymentSummary();
    $remainingBalance = (float)($paymentSummary['remaining_balance'] ?? 0);

    // Caso 1: Sin stock suficiente
    // → Solo puede emitir anticipos, no comprobante final
    if (!$hasStock) {
      return [
        'can_final_receipt' => false,
        'can_advance' => true,
        'is_toggle_enabled' => false,
        'message' => 'Stock insuficiente para generar comprobante final. Solo puede emitir anticipos.'
      ];
    }

    // Caso 2: Con stock pero ya no hay saldo pendiente (remaining_balance = 0)
    // → Debe emitir comprobante final, no puede emitir más anticipos
    if ($remainingBalance === 0.0) {
      return [
        'can_final_receipt' => true,
        'can_advance' => false,
        'is_toggle_enabled' => false,
        'message' => 'Debe emitir comprobante final. La cotización ya está completamente pagada.'
      ];
    }

    // Caso 3: Con stock y hay saldo pendiente
    // → Usuario puede elegir entre comprobante final o anticipo
    return [
      'can_final_receipt' => true,
      'can_advance' => true,
      'is_toggle_enabled' => true,
      'message' => null
    ];
  }

  /**
   * Valida que la cotización NO esté en los estados prohibidos
   *
   * @param array $forbiddenStatuses Estados a validar
   * @param string|null $action Acción que se está intentando (opcional, para personalizar mensaje)
   * @throws \Exception
   */
  public function ensureNotInStates(array $forbiddenStatuses, ?string $action = null): void
  {
    if (in_array($this->status_id, $forbiddenStatuses, true)) {
      $statusNames = [
        ApMasters::STATUS_ORDER_QUOTE_APERTURADO => 'aperturada',
        ApMasters::STATUS_ORDER_QUOTE_APROBADO => 'aprobada',
        ApMasters::STATUS_ORDER_QUOTE_FACTURAR => 'por facturar',
        ApMasters::STATUS_ORDER_QUOTE_FACTURADO => 'facturada',
        ApMasters::STATUS_ORDER_QUOTE_SEGMENTADO => 'segmentada',
        ApMasters::STATUS_ORDER_QUOTE_DESCARTADO => 'descartada',
      ];

      $statusName = $statusNames[$this->status_id] ?? 'este estado';
      $message = $action
        ? "No se puede {$action} en una cotización {$statusName}"
        : "Esta acción no está permitida en una cotización {$statusName}";

      throw new \Exception($message);
    }
  }

  /**
   * Valida que la cotización esté en uno de los estados permitidos
   *
   * @param array $allowedStatuses Estados permitidos
   * @param string|null $action Acción que se está intentando (opcional, para personalizar mensaje)
   * @throws \Exception
   */
  public function ensureInStates(array $allowedStatuses, ?string $action = null): void
  {
    if (!in_array($this->status_id, $allowedStatuses, true)) {
      $statusNames = [
        ApMasters::STATUS_ORDER_QUOTE_APERTURADO => 'aperturada',
        ApMasters::STATUS_ORDER_QUOTE_APROBADO => 'aprobada',
        ApMasters::STATUS_ORDER_QUOTE_FACTURAR => 'por facturar',
        ApMasters::STATUS_ORDER_QUOTE_FACTURADO => 'facturada',
        ApMasters::STATUS_ORDER_QUOTE_SEGMENTADO => 'segmentada',
        ApMasters::STATUS_ORDER_QUOTE_DESCARTADO => 'descartada',
      ];

      $currentStatusName = $statusNames[$this->status_id] ?? 'este estado';
      $message = $action
        ? "No se puede {$action} en una cotización {$currentStatusName}"
        : "Esta acción no está permitida en una cotización {$currentStatusName}";

      throw new \Exception($message);
    }
  }

  /**
   * Valida que la cotización pueda ser modificada
   * (no esté descartada o facturada)
   *
   * @throws \Exception
   */
  public function ensureCanBeModified(): void
  {
    $this->ensureNotInStates([
      ApMasters::STATUS_ORDER_QUOTE_DESCARTADO,
      ApMasters::STATUS_ORDER_QUOTE_FACTURADO,
    ]);
  }

  /**
   * Actualiza el status de la cotización
   *
   * @param int $statusId ID del nuevo estado (de ApMasters)
   * @return void
   */
  public function updateStatus(int $statusId): void
  {
    $this->status_id = $statusId;
    $this->save();
  }

  // Export Methods
  public static function getReportData($filters = [])
  {
    $query = self::with([
      'vehicle.model.family.brand',
      'vehicle.model',
      'client',
      'sede',
      'status',
      'area',
      'createdBy',
      'typeCurrency',
      'invoiceTo',
      'details',
      'advancesOrderQuotation',
      'discardReason',
      'discardedBy'
    ]);

    // Apply filters
    foreach ($filters as $filter) {
      $column = $filter['column'];
      $operator = $filter['operator'];
      $value = $filter['value'];

      if ($column === 'area_id' && $operator === '=') {
        $query->where('area_id', $value);
      } elseif ($column === 'sede_id' && $operator === '=') {
        $query->where('sede_id', $value);
      } elseif ($column === 'status_id' && $operator === 'in_or_equal') {
        if (is_array($value)) {
          $query->whereIn('status_id', $value);
        } else {
          $query->where('status_id', $value);
        }
      } elseif ($column === 'quotation_date' && $operator === 'date_between') {
        if (is_array($value) && count($value) === 2) {
          $query->whereBetween('quotation_date', [$value[0], $value[1]]);
        }
      } elseif ($column === 'estimated_delivery_date' && $operator === 'date_between') {
        if (is_array($value) && count($value) === 2) {
          $query->whereBetween('estimated_delivery_date', [$value[0], $value[1]]);
        }
      } elseif ($column === 'actual_delivery_date' && $operator === 'between') {
        if (is_array($value) && count($value) === 2) {
          $query->whereBetween('actual_delivery_date', [$value[0], $value[1]]);
        }
      } elseif ($column === 'currency_id' && $operator === '=') {
        $query->where('currency_id', $value);
      } elseif ($column === 'quotation_number' && $operator === 'like') {
        $query->where('quotation_number', 'like', '%' . $value . '%');
      } elseif ($column === 'vehicle_plate' && $operator === 'like') {
        $query->whereHas('vehicle', function ($q) use ($value) {
          $q->where('plate', 'like', '%' . $value . '%');
        });
      }
    }

    $quotations = $query->get();

    return $quotations->map(function ($quotation) {
      // Obtener el primer detalle
      $firstDetail = $quotation->details->first();

      // Obtener los anticipos activos
      $activeAdvances = $quotation->getActiveAdvances();

      // Formatear anticipos: "full_number (SI/NO) | full_number (SI/NO)"
      $advancesFormatted = '-';
      $totalAdvances = 0;
      $tieneAnticipo = 'NO';

      if ($activeAdvances && $activeAdvances->count() > 0) {
        $tieneAnticipo = 'SI';
        $advancesArray = [];
        foreach ($activeAdvances as $advance) {
          $sunatStatus = $advance->aceptada_por_sunat == 1 ? '(SI)' : '(NO)';
          $advancesArray[] = $advance->full_number . ' ' . $sunatStatus;
          $totalAdvances += $advance->total ?? 0;
        }
        $advancesFormatted = implode(' | ', $advancesArray);
      }

      // Obtener el documento electrónico final (factura/boleta)
      $electronicDocument = $quotation->getFinalInvoice();

      $data = [
        'sede' => $quotation->sede ? $quotation->sede->abreviatura : '',
        'numero_cotizacion' => $quotation->quotation_number,
        'area' => $quotation->area ? $quotation->area->description : '',
        'estado' => $quotation->status ? $quotation->status->description : '',
        'fecha_cotizacion' => $quotation->quotation_date ? $quotation->quotation_date->format('Y-m-d') : '',
        'fecha_expiracion' => $quotation->expiration_date ? $quotation->expiration_date->format('Y-m-d') : '',
        'placa_vehiculo' => $quotation->vehicle ? $quotation->vehicle->plate : '',
        'vin_vehiculo' => $quotation->vehicle ? $quotation->vehicle->vin : '',
        'cliente' => $quotation->client ? $quotation->client->full_name : '',
        'moneda' => $quotation->typeCurrency ? $quotation->typeCurrency->symbol : '',
        'cliente_facturar' => $quotation->invoiceTo ? $quotation->invoiceTo->full_name : '',
        'subtotal' => number_format($quotation->subtotal ?? 0, 2),
        'descuento' => number_format($quotation->discount_amount ?? 0, 2),
        'igv' => number_format($quotation->tax_amount ?? 0, 2),
        'total' => number_format($quotation->total_amount ?? 0, 2),
        'tiene_anticipo' => $tieneAnticipo,
        'anticipos' => $advancesFormatted,
        'total_anticipos' => number_format($totalAdvances, 2),
        'comprobante_final' => $electronicDocument?->full_number ?? '-',
        'estado_sunat' => $electronicDocument ? ($electronicDocument->aceptada_por_sunat ? 'SI' : 'NO') : '-',
        'contabilizada' => $electronicDocument ? ($electronicDocument->is_accounted ? 'SI' : 'NO') : '-',
      ];

      // Si es descartado, agregar columnas de descarte
      if ($quotation->status_id == ApMasters::STATUS_ORDER_QUOTE_DESCARTADO) {
        $data['motivo_descarte'] = $quotation->discardReason ? $quotation->discardReason->description : '-';
        $data['nota_descarte'] = $quotation->discarded_note ?? '-';
        $data['descartado_por'] = $quotation->discardedBy ? $quotation->discardedBy->name : '-';
        $data['fecha_descarte'] = $quotation->discarded_at ? $quotation->discarded_at->format('Y-m-d H:i:s') : '-';
      }

      return $data;
    });
  }

  public static function getReportableColumns()
  {
    return [
      'sede' => 'Sede',
      'numero_cotizacion' => 'Número Cotización',
      'area' => 'Área',
      'estado' => 'Estado',
      'fecha_cotizacion' => 'Fecha Cotización',
      'fecha_expiracion' => 'Fecha Expiración',
      'placa_vehiculo' => 'Placa Vehículo',
      'vin_vehiculo' => 'VIN Vehículo',
      'cliente' => 'Cliente',
      'moneda' => 'Moneda',
      'cliente_facturar' => 'Cliente Facturar',
      'subtotal' => 'Subtotal',
      'descuento' => 'Descuento',
      'igv' => 'IGV',
      'total' => 'Total',
      'tiene_anticipo' => 'Tiene Anticipo',
      'anticipos' => 'Anticipos',
      'total_anticipos' => 'Total Anticipos',
      'comprobante_final' => 'Comprobante Final',
      'estado_sunat' => 'Estado SUNAT',
      'contabilizada' => 'Contabilizada',
      'motivo_descarte' => 'Motivo Descarte',
      'nota_descarte' => 'Nota Descarte',
      'descartado_por' => 'Descartado Por',
      'fecha_descarte' => 'Fecha Descarte',
    ];
  }

  /**
   * Filtra las columnas del reporte según el contexto (filtros aplicados)
   *
   * Lógica:
   * - Si SOLO se filtra por DESCARTADO: ocultar columnas de anticipos/facturación
   * - Si NO incluye descartados: ocultar columnas de descarte
   * - Si incluye múltiples estados: mostrar todas las columnas relevantes
   */
  public static function filterReportColumns($columns, $context = [])
  {
    $statusId = $context['status_id'] ?? null;

    // Si no hay filtro de status, mostrar todas las columnas menos las de descarte
    if (!$statusId) {
      unset($columns['motivo_descarte']);
      unset($columns['nota_descarte']);
      unset($columns['descartado_por']);
      unset($columns['fecha_descarte']);
      return $columns;
    }

    // Normalizar status_id a array
    $statusIds = is_array($statusId) ? $statusId : [$statusId];

    $onlyDescartado = count($statusIds) === 1 && in_array(ApMasters::STATUS_ORDER_QUOTE_DESCARTADO, $statusIds);
    $includesDescartado = in_array(ApMasters::STATUS_ORDER_QUOTE_DESCARTADO, $statusIds);

    // Caso 1: SOLO descartadas - ocultar columnas de anticipos/facturación, mostrar columnas de descarte
    if ($onlyDescartado) {
      unset($columns['tiene_anticipo']);
      unset($columns['anticipos']);
      unset($columns['total_anticipos']);
      unset($columns['comprobante_final']);
      unset($columns['estado_sunat']);
      unset($columns['contabilizada']);
      return $columns;
    }

    // Caso 2: NO incluye descartadas - ocultar columnas de descarte
    if (!$includesDescartado) {
      unset($columns['motivo_descarte']);
      unset($columns['nota_descarte']);
      unset($columns['descartado_por']);
      unset($columns['fecha_descarte']);
      return $columns;
    }

    // Caso 3: Incluye descartadas Y otros estados - mostrar todas las columnas
    return $columns;
  }

  public static function getReportStyles()
  {
    return [
      'headerBackgroundColor' => '4472C4',
      'headerFontColor' => 'FFFFFF',
      'headerFontSize' => 11,
      'headerBold' => true,
      'bodyFontSize' => 10,
      'freezePane' => 'A2',
      'autoFilter' => true,
    ];
  }

  public static function getReportColorRules()
  {
    return [
      'tiene_anticipo' => [
        'SI' => ['bg' => '28A745', 'text' => 'FFFFFF'],  // Verde con texto blanco
        'NO' => ['bg' => 'DC3545', 'text' => 'FFFFFF'],  // Rojo con texto blanco
      ],
      'estado_sunat' => [
        'SI' => ['bg' => '28A745', 'text' => 'FFFFFF'],  // Verde con texto blanco
        'NO' => ['bg' => 'DC3545', 'text' => 'FFFFFF'],  // Rojo con texto blanco
      ],
      'contabilizada' => [
        'SI' => ['bg' => '28A745', 'text' => 'FFFFFF'],  // Verde con texto blanco
        'NO' => ['bg' => 'A9A9A9', 'text' => '000000'],  // Gris con texto negro
      ],
    ];
  }
}
