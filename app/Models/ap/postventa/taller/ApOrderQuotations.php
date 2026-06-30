<?php

namespace App\Models\ap\postventa\taller;

use App\Http\Utils\Constants;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\maestroGeneral\SunatConcepts;
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
  use softDeletes;

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
    'validity_days',
    'quotation_date',
    'expiration_date',
    'collection_date',
    'observations',
    'notes',
    'created_by',
    'is_take',
    'is_requested_by_management',
    'emails_sent_count',
    'area_id',
    'invoice_to',
    'currency_id',
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
    'status',
    'confirmation_token',
    'confirmation_token_expires_at',
    'confirmed_at',
    'confirmation_channel',
    'confirmation_ip',
    'confirmation_metadata',
    'parent_quotation_id',
    'shipping_guide_id',
  ];

  const filters = [
    'search' => ['quotation_number', 'observations', 'vehicle.customer.full_name', 'client.full_name', 'client.num_doc', 'vehicle.plate'],
    'vehicle_id' => '=',
    'quotation_date' => 'between',
    'is_take' => '=',
    'area_id' => '=',
    'currency_id' => '=',
    'discard_reason_id' => '=',
    'status' => 'in_or_equal',
    'sede_id' => '=',
    'supply_type' => 'in',
    'has_invoice_generated' => '=',
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
    'is_requested_by_management' => 'boolean',
    'confirmation_token_expires_at' => 'datetime',
    'confirmed_at' => 'datetime',
    'confirmation_metadata' => 'array',
  ];

  //STATUS CONSTANTS
  const STATUS_DESCARTADO = 'Descartado';
  const STATUS_APERTURADO = 'Aperturado';
  const STATUS_POR_FACTURAR = 'Por Facturar';
  const STATUS_FACTURADO = 'Facturado';

  // SUPPLY TYPE CONSTANTS
  const STOCK = 'STOCK';
  const TRASLADO = 'TRASLADO';
  const LOCAL = 'LOCAL';
  const CENTRAL = 'CENTRAL';
  const IMPORTACION = 'IMPORTACION';

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

  public function client(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'client_id');
  }

  public function details()
  {
    return $this->hasMany(ApOrderQuotationDetails::class, 'order_quotation_id');
  }

  public function advancesOrderQuotation(): HasMany
  {
    return $this->hasMany(ElectronicDocument::class, 'order_quotation_id');
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

    // Update quotation with all calculated values
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
   * Get active advances for this quotation.
   *
   * An advance is truly cancelled (and therefore excluded) only when:
   *   - status = 'cancelled' (voided locally before SUNAT communication)
   *   - anulado = 1 (low-communication sent to SUNAT)
   *   - It has a linked credit note of type ANULACION or DEVOLUCION_TOTAL,
   *     which fully reverses the original transaction to zero.
   *
   * Advances with debit notes or partial credit notes (DESCUENTO_GLOBAL,
   * DEVOLUCION_ITEM, etc.) remain active — they only adjust the amount.
   *
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getActiveAdvances()
  {
    $annullingTypes = [
      SunatConcepts::ID_CREDIT_NOTE_ANULACION,
      SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
    ];

    return $this->advancesOrderQuotation->filter(function ($advance) use ($annullingTypes) {
      if (!$advance->aceptada_por_sunat
        || !$advance->is_advance_payment
        || !in_array($advance->sunat_concept_document_type_id, [ElectronicDocument::TYPE_FACTURA, ElectronicDocument::TYPE_BOLETA])) {
        return false;
      }

      if ($advance->status === ElectronicDocument::STATUS_CANCELLED || $advance->anulado == 1) {
        return false;
      }

      if ($advance->credit_note_id !== null
        && in_array($advance->creditNote?->sunat_concept_credit_note_type_id, $annullingTypes)) {
        return false;
      }

      return true;
    });
  }

  /**
   * Get cancelled advances for this quotation.
   *
   * An advance is cancelled when:
   *   - status = 'cancelled', OR
   *   - anulado = 1, OR
   *   - It has a linked credit note of type ANULACION or DEVOLUCION_TOTAL.
   *
   * Advances with debit notes or partial credit notes are NOT cancelled.
   *
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getCancelledAdvances()
  {
    $annullingTypes = [
      SunatConcepts::ID_CREDIT_NOTE_ANULACION,
      SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
    ];

    return $this->advancesOrderQuotation->filter(function ($advance) use ($annullingTypes) {
      if (!$advance->aceptada_por_sunat
        || !$advance->is_advance_payment
        || !in_array($advance->sunat_concept_document_type_id, [ElectronicDocument::TYPE_FACTURA, ElectronicDocument::TYPE_BOLETA])) {
        return false;
      }

      if ($advance->status === ElectronicDocument::STATUS_CANCELLED || $advance->anulado == 1) {
        return true;
      }

      return $advance->credit_note_id !== null
        && in_array($advance->creditNote?->sunat_concept_credit_note_type_id, $annullingTypes);
    });
  }

  /**
   * Get the final invoice (factura/boleta final) for this quotation.
   *
   * A final invoice is:
   *   - NOT an advance payment (is_advance_payment = false)
   *   - Accepted by SUNAT
   *   - Type FACTURA or BOLETA
   *   - NOT cancelled (status != cancelled && anulado != 1)
   *   - NOT fully annulled by credit note
   *
   * @return ElectronicDocument|null
   */
  public function getFinalInvoice()
  {
    $annullingTypes = [
      SunatConcepts::ID_CREDIT_NOTE_ANULACION,
      SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
    ];

    return $this->advancesOrderQuotation->first(function ($document) use ($annullingTypes) {
      // Must be final invoice (not advance)
      if ($document->is_advance_payment) {
        return false;
      }

      // Must be accepted by SUNAT
      if (!$document->aceptada_por_sunat) {
        return false;
      }

      // Must be FACTURA or BOLETA
      if (!in_array($document->sunat_concept_document_type_id, [ElectronicDocument::TYPE_FACTURA, ElectronicDocument::TYPE_BOLETA])) {
        return false;
      }

      // Must not be cancelled
      if ($document->status === ElectronicDocument::STATUS_CANCELLED || $document->anulado == 1) {
        return false;
      }

      // Must not have annulling credit note
      if ($document->credit_note_id !== null
        && in_array($document->creditNote?->sunat_concept_credit_note_type_id, $annullingTypes)) {
        return false;
      }

      return true;
    });
  }

  /**
   * Get all valid documents for this quotation (advances + final invoice).
   *
   * Returns a collection containing:
   *   - Active advances (from getActiveAdvances)
   *   - Final invoice if exists (from getFinalInvoice)
   *
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getValidDocuments()
  {
    $documents = collect();

    // Add active advances
    $activeAdvances = $this->getActiveAdvances();
    if ($activeAdvances->isNotEmpty()) {
      $documents = $documents->merge($activeAdvances);
    }

    // Add final invoice if exists
    $finalInvoice = $this->getFinalInvoice();
    if ($finalInvoice) {
      $documents->push($finalInvoice);
    }

    return $documents;
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
    $activeAdvances = $this->getActiveAdvances();

    $totalNet = 0;

    foreach ($activeAdvances as $advance) {
      $netAmount = $advance->total;

      // Restar notas de crédito sobre este anticipo (que NO sean de anulación/devolución total)
      // porque esas ya están excluidas por getActiveAdvances()
      $creditNotesOnAdvance = ElectronicDocument::where('original_document_id', $advance->id)
        ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_CREDITO)
        ->where('aceptada_por_sunat', true)
        ->where('anulado', 0)
        ->whereNotIn('sunat_concept_credit_note_type_id', [
          SunatConcepts::ID_CREDIT_NOTE_ANULACION,
          SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
        ])
        ->get();

      foreach ($creditNotesOnAdvance as $creditNote) {
        $netAmount -= $creditNote->total;
      }

      // Sumar notas de débito sobre este anticipo
      $debitNotesOnAdvance = ElectronicDocument::where('original_document_id', $advance->id)
        ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_DEBITO)
        ->where('aceptada_por_sunat', true)
        ->where('anulado', 0)
        ->get();

      foreach ($debitNotesOnAdvance as $debitNote) {
        $netAmount += $debitNote->total;
      }

      $totalNet += $netAmount;
    }

    return (float)$totalNet;
  }

  /**
   * Get all documents organized in a tree structure with cancelled and active documents.
   * Active documents include their credit/debit note modifications.
   *
   * @return array
   */
  public function getDocumentsTree(): array
  {
    $annullingTypes = [
      SunatConcepts::ID_CREDIT_NOTE_ANULACION,
      SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
    ];

    $cancelled = [];
    $active = [];

    // Process all documents
    foreach ($this->advancesOrderQuotation as $document) {
      // Skip if not accepted by SUNAT or not the right type
      if (!$document->aceptada_por_sunat
        || !in_array($document->sunat_concept_document_type_id, [
          ElectronicDocument::TYPE_FACTURA,
          ElectronicDocument::TYPE_BOLETA
        ])) {
        continue;
      }

      $isCancelled = false;
      $cancellationReason = null;
      $creditNoteNumber = null;
      $creditNoteTypeId = null;
      $creditNoteTypeDescription = null;

      // Check if it's cancelled
      if ($document->status === ElectronicDocument::STATUS_CANCELLED || $document->anulado == 1) {
        $isCancelled = true;
        $cancellationReason = $document->observaciones;
      }

      // Check if it has an annulling credit note
      if ($document->credit_note_id !== null
        && in_array($document->creditNote?->sunat_concept_credit_note_type_id, $annullingTypes)) {
        $isCancelled = true;
        $cancellationReason = $document->creditNote?->observaciones;
        $creditNoteNumber = $document->creditNote?->full_number;
        $creditNoteTypeId = $document->creditNote?->sunat_concept_credit_note_type_id;
        $creditNoteTypeDescription = $document->creditNote?->creditNoteType?->description;
      }

      $documentData = [
        'id' => $document->id,
        'is_advance_payment' => (boolean)$document->is_advance_payment,
        'document_type' => $document->documentType->description,
        'number' => $document->full_number,
        'serie' => $document->serie,
        'numero' => $document->numero,
        'total' => (float)$document->total,
        'issue_date' => $document->fecha_de_emision?->format('Y-m-d'),
        'client_name' => $document->cliente_denominacion,
        'client_document' => $document->cliente_numero_de_documento,
        'status' => $document->status,
        'sunat_responsecode' => $document->sunat_responsecode,
        'enlace_del_pdf' => $document->enlace_del_pdf,
      ];

      if ($isCancelled) {
        $documentData['cancellation_reason'] = $cancellationReason;
        $documentData['credit_note_number'] = $creditNoteNumber;
        $documentData['sunat_concept_credit_note_type_id'] = $creditNoteTypeId;
        $documentData['credit_note_type_description'] = $creditNoteTypeDescription;
        $cancelled[] = $documentData;
      } else {
        // Get credit notes (excluding annulling types)
        $creditNotes = ElectronicDocument::where('original_document_id', $document->id)
          ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_CREDITO)
          ->where('aceptada_por_sunat', true)
          ->where('anulado', 0)
          ->whereNotIn('sunat_concept_credit_note_type_id', $annullingTypes)
          ->get();

        // Get debit notes
        $debitNotes = ElectronicDocument::where('original_document_id', $document->id)
          ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_DEBITO)
          ->where('aceptada_por_sunat', true)
          ->where('anulado', 0)
          ->get();

        $modifications = [];
        $netAmount = $document->total;

        // Add credit notes
        foreach ($creditNotes as $creditNote) {
          $modifications[] = [
            'id' => $creditNote->id,
            'type' => 'credit_note',
            'concept_type' => $creditNote->creditNoteType?->description,
            'concept_type_id' => $creditNote->sunat_concept_credit_note_type_id,
            'number' => $creditNote->full_number,
            'serie' => $creditNote->serie,
            'numero' => $creditNote->numero,
            'total' => -(float)$creditNote->total,
            'issue_date' => $creditNote->fecha_de_emision?->format('Y-m-d'),
            'original_document_id' => $document->id,
            'observaciones' => $creditNote->observaciones,
            'enlace_del_pdf' => $creditNote->enlace_del_pdf,
          ];
          $netAmount -= $creditNote->total;
        }

        // Add debit notes
        foreach ($debitNotes as $debitNote) {
          $modifications[] = [
            'id' => $debitNote->id,
            'type' => 'debit_note',
            'concept_type' => $debitNote->debitNoteType?->description,
            'concept_type_id' => $debitNote->sunat_concept_debit_note_type_id,
            'number' => $debitNote->full_number,
            'serie' => $debitNote->serie,
            'numero' => $debitNote->numero,
            'total' => (float)$debitNote->total,
            'issue_date' => $debitNote->fecha_de_emision?->format('Y-m-d'),
            'original_document_id' => $document->id,
            'observaciones' => $debitNote->observaciones,
            'enlace_del_pdf' => $debitNote->enlace_del_pdf,
          ];
          $netAmount += $debitNote->total;
        }

        $documentData['net_amount'] = (float)$netAmount;
        $documentData['has_modifications'] = count($modifications) > 0;
        $documentData['modifications'] = $modifications;

        $active[] = $documentData;
      }
    }

    return [
      'cancelled' => $cancelled,
      'active' => $active,
    ];
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
    $finalInvoice = $this->getFinalInvoice();
    $activeAdvances = $this->getActiveAdvances();

    // If there's a final invoice, total paid = sum of all active vouchers
    // Otherwise, only count advances with their credit/debit notes applied
    if ($finalInvoice) {
      $paidAmount = $activeAdvances->sum('total') + $finalInvoice->total;
    } else {
      $paidAmount = $this->getNetAmountFromAdvances();
    }

    $pendingAmount = max(0, $this->total_amount - $paidAmount);

    return [
      // Amount already paid/invoiced (advances + final invoice if exists)
      'paid_amount' => round((float)$paidAmount, 2),

      // Amount remaining to be paid/invoiced (same as remaining_balance for compatibility)
      'pending_amount' => round((float)$pendingAmount, 2),
      'remaining_balance' => round((float)$pendingAmount, 2),

      // Payment progress
      'payment_percentage' => $this->total_amount > 0
        ? round(($paidAmount / $this->total_amount) * 100, 2)
        : 0,

      // Payment status indicators
      'has_final_invoice' => $finalInvoice !== null,
      'advances_count' => $activeAdvances->count(),
    ];
  }
}
