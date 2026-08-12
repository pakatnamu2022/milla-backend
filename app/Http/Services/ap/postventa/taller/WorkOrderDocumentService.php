<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Database\Eloquent\Model;

/**
 * Servicio especializado para gestión de documentos electrónicos (comprobantes)
 * de órdenes de trabajo y cotizaciones.
 *
 * Responsabilidades:
 * - Obtener documentos activos/cancelados
 * - Árbol de documentos con modificaciones (NC/ND)
 * - Resumen de pagos
 * - Validación de estados de documentos
 */
class WorkOrderDocumentService
{
  /**
   * Check if a document is accepted by SUNAT based on its type.
   * Boletas can be in 'sent' status (provider sometimes takes time to respond).
   * Facturas must be in 'accepted' status.
   *
   * @param ElectronicDocument $document
   * @return bool
   */
  public function isDocumentAcceptedBySunat(ElectronicDocument $document): bool
  {
    // For boletas, accept if sent or accepted
    if ($document->sunat_concept_document_type_id === ElectronicDocument::TYPE_BOLETA) {
      return $document->status === ElectronicDocument::STATUS_SENT
        || $document->status === ElectronicDocument::STATUS_ACCEPTED;
    }

    // For facturas and other documents, must be accepted
    return $document->aceptada_por_sunat;
  }

  /**
   * Get active advances for a given model (WorkOrder or OrderQuotation).
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
   * @param Model $model Instance of ApWorkOrder or ApOrderQuotations
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getActiveAdvances(Model $model)
  {
    $annullingTypes = [
      SunatConcepts::ID_CREDIT_NOTE_ANULACION,
      SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
    ];

    // Get advances relation (advancesWorkOrder or advancesOrderQuotation)
    $advances = $this->getAdvancesRelation($model);

    return $advances->filter(function ($advance) use ($annullingTypes) {
      $passed = true;

      if (!$this->isDocumentAcceptedBySunat($advance)
        || !$advance->is_advance_payment
        || !in_array($advance->sunat_concept_document_type_id, [ElectronicDocument::TYPE_FACTURA, ElectronicDocument::TYPE_BOLETA])) {
        $passed = false;
      }

      if ($passed && ($advance->status === ElectronicDocument::STATUS_CANCELLED || $advance->anulado == 1)) {
        $passed = false;
      }

      if ($passed && $advance->credit_note_id !== null
        && in_array($advance->creditNote?->sunat_concept_credit_note_type_id, $annullingTypes)) {
        $passed = false;
      }

      return $passed;
    });
  }

  /**
   * Get cancelled advances for a given model.
   *
   * An advance is cancelled when:
   *   - status = 'cancelled', OR
   *   - anulado = 1, OR
   *   - It has a linked credit note of type ANULACION or DEVOLUCION_TOTAL.
   *
   * Advances with debit notes or partial credit notes are NOT cancelled.
   *
   * @param Model $model Instance of ApWorkOrder or ApOrderQuotations
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getCancelledAdvances(Model $model)
  {
    $annullingTypes = [
      SunatConcepts::ID_CREDIT_NOTE_ANULACION,
      SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
    ];

    $advances = $this->getAdvancesRelation($model);

    return $advances->filter(function ($advance) use ($annullingTypes) {
      if (!$this->isDocumentAcceptedBySunat($advance)
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
   * Get the final invoice (factura/boleta final) for a given model.
   *
   * A final invoice is:
   *   - NOT an advance payment (is_advance_payment = false)
   *   - Accepted by SUNAT
   *   - Type FACTURA or BOLETA
   *   - NOT cancelled (status != cancelled && anulado != 1)
   *   - NOT fully annulled by credit note
   *
   * @param Model $model Instance of ApWorkOrder or ApOrderQuotations
   * @return \Closure
   */
  public function getFinalInvoice(Model $model)
  {
    $annullingTypes = [
      SunatConcepts::ID_CREDIT_NOTE_ANULACION,
      SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
    ];

    $advances = $this->getAdvancesRelation($model);

    return $advances->first(function ($document) use ($annullingTypes) {
      // Must be final invoice (not advance)
      if ($document->is_advance_payment) {
        return false;
      }

      // Must be accepted by SUNAT
      if (!$this->isDocumentAcceptedBySunat($document)) {
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
   * Check if there's a draft final invoice for a given model.
   *
   * A draft final invoice is:
   *   - NOT an advance payment (is_advance_payment = false)
   *   - Status is DRAFT
   *   - Type FACTURA or BOLETA
   *   - NOT annulled (anulado != 1)
   *
   * @param Model $model Instance of ApWorkOrder or ApOrderQuotations
   * @return bool
   */
  public function hasDraftFinalInvoice(Model $model): bool
  {
    $advances = $this->getAdvancesRelation($model);

    return $advances
      ->filter(function ($document) {
        // Must be final invoice (not advance)
        if ($document->is_advance_payment) {
          return false;
        }

        // Must be FACTURA or BOLETA
        if (!in_array($document->sunat_concept_document_type_id, [
          ElectronicDocument::TYPE_FACTURA,
          ElectronicDocument::TYPE_BOLETA
        ])) {
          return false;
        }

        // Must be in DRAFT status
        if ($document->status !== ElectronicDocument::STATUS_DRAFT) {
          return false;
        }

        // Must not be annulled
        if ($document->anulado == 1) {
          return false;
        }

        return true;
      })
      ->isNotEmpty();
  }

  /**
   * Check if there's a draft advance payment for a given model.
   *
   * A draft advance is:
   *   - IS an advance payment (is_advance_payment = true)
   *   - Status is DRAFT
   *   - Type FACTURA or BOLETA
   *   - NOT annulled (anulado != 1)
   *
   * @param Model $model Instance of ApWorkOrder or ApOrderQuotations
   * @return bool
   */
  public function hasDraftAdvance(Model $model): bool
  {
    $advances = $this->getAdvancesRelation($model);

    return $advances
      ->filter(function ($document) {
        // Must be advance payment
        if (!$document->is_advance_payment) {
          return false;
        }

        // Must be FACTURA or BOLETA
        if (!in_array($document->sunat_concept_document_type_id, [
          ElectronicDocument::TYPE_FACTURA,
          ElectronicDocument::TYPE_BOLETA
        ])) {
          return false;
        }

        // Must be in DRAFT status
        if ($document->status !== ElectronicDocument::STATUS_DRAFT) {
          return false;
        }

        // Must not be annulled
        if ($document->anulado == 1) {
          return false;
        }

        return true;
      })
      ->isNotEmpty();
  }

  /**
   * Check if there are any active advances (optimized for validation).
   * Uses exists() instead of loading all records.
   *
   * An advance is considered active when:
   *   - is_advance_payment = true
   *   - Accepted by SUNAT (based on document type)
   *   - Type is FACTURA or BOLETA
   *   - NOT cancelled (status != 'cancelled' AND anulado != 1)
   *   - Does NOT have an annulling credit note (ANULACION or DEVOLUCION_TOTAL)
   *
   * @param Model $model Instance of ApWorkOrder or ApOrderQuotations
   * @return bool
   */
  public function hasActiveAdvances(Model $model): bool
  {
    $annullingTypes = [
      SunatConcepts::ID_CREDIT_NOTE_ANULACION,
      SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
    ];

    $relation = $this->getDocumentRelation($model);

    return $model->$relation()
      ->where('is_advance_payment', true)
      ->whereIn('sunat_concept_document_type_id', [
        ElectronicDocument::TYPE_FACTURA,
        ElectronicDocument::TYPE_BOLETA
      ])
      ->where(function ($query) {
        // For boletas, accept if sent or accepted
        $query->where(function ($q) {
          $q->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_BOLETA)
            ->whereIn('status', [ElectronicDocument::STATUS_SENT, ElectronicDocument::STATUS_ACCEPTED]);
        })
        // For facturas, must be accepted
        ->orWhere(function ($q) {
          $q->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_FACTURA)
            ->where('aceptada_por_sunat', true);
        });
      })
      ->where('status', '!=', ElectronicDocument::STATUS_CANCELLED)
      ->where('anulado', 0)
      ->where(function ($query) use ($annullingTypes) {
        // Either no credit note, or credit note is not annulling type
        $query->whereNull('credit_note_id')
          ->orWhereDoesntHave('creditNote', function ($q) use ($annullingTypes) {
            $q->whereIn('sunat_concept_credit_note_type_id', $annullingTypes);
          });
      })
      ->exists();
  }

  /**
   * Get all valid documents for a model (advances + final invoice).
   *
   * Returns a collection containing:
   *   - Active advances (from getActiveAdvances)
   *   - Final invoice if exists (from getFinalInvoice)
   *
   * @param Model $model Instance of ApWorkOrder or ApOrderQuotations
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getValidDocuments(Model $model)
  {
    $documents = collect();

    // Add active advances
    $activeAdvances = $this->getActiveAdvances($model);
    if ($activeAdvances->isNotEmpty()) {
      $documents = $documents->merge($activeAdvances);
    }

    // Add final invoice if exists
    $finalInvoice = $this->getFinalInvoice($model);
    if ($finalInvoice) {
      $documents->push($finalInvoice);
    }

    return $documents;
  }

  /**
   * Get all documents organized in a tree structure with cancelled and active documents.
   * Active documents include their credit/debit note modifications.
   *
   * @param Model $model Instance of ApWorkOrder or ApOrderQuotations
   * @return array
   */
  public function getDocumentsTree(Model $model): array
  {
    $annullingTypes = [
      SunatConcepts::ID_CREDIT_NOTE_ANULACION,
      SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
    ];

    $cancelled = [];
    $active = [];

    $advances = $this->getAdvancesRelation($model);

    // Process all documents
    foreach ($advances as $document) {
      // Skip if not accepted by SUNAT or not the right type
      if (!$this->isDocumentAcceptedBySunat($document)
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
   * Get payment summary information for a model.
   *
   * Returns only payment-related information without duplicating data already
   * available in the resource header (final_amount, subtotal, etc.)
   *
   * Uses rounding tolerance to account for IGV calculation differences.
   *
   * @param Model $model Instance of ApWorkOrder or ApOrderQuotations
   * @param WorkOrderAdvancePaymentService $advancePaymentService
   * @return array
   */
  public function getPaymentSummary(Model $model, WorkOrderAdvancePaymentService $advancePaymentService): array
  {
    $finalInvoice = $this->getFinalInvoice($model);
    $activeAdvances = $this->getActiveAdvances($model);

    // Get the final_amount from model (works for both WorkOrder and OrderQuotation)
    $finalAmount = $model->final_amount ?? $model->total_amount;

    // If there's a final invoice, total paid = sum of all active vouchers
    // Otherwise, only count advances with their credit/debit notes applied
    if ($finalInvoice) {
      $paidAmount = $activeAdvances->sum('total') + $finalInvoice->total;
    } else {
      $paidAmount = $advancePaymentService->getNetAmountFromAdvances($model);
    }

    $pendingAmount = max(0, $finalAmount - $paidAmount);

    return [
      // Amount already paid/invoiced (advances + final invoice if exists)
      'paid_amount' => round((float)$paidAmount, 2),

      // Amount remaining to be paid/invoiced (same as remaining_balance for compatibility)
      'pending_amount' => round((float)$pendingAmount, 2),
      'remaining_balance' => round((float)$pendingAmount, 2),

      // Payment progress
      'payment_percentage' => $finalAmount > 0
        ? round(($paidAmount / $finalAmount) * 100, 2)
        : 0,

      // Payment status indicators
      'has_final_invoice' => $finalInvoice !== null,
      'advances_count' => $activeAdvances->count(),
    ];
  }

  /**
   * Get the advances relation from the model (polymorphic support)
   *
   * @param Model $model
   * @return \Illuminate\Database\Eloquent\Collection
   */
  private function getAdvancesRelation(Model $model)
  {
    // ApWorkOrder uses advancesWorkOrder, ApOrderQuotations uses advancesOrderQuotation
    if (property_exists($model, 'advancesWorkOrder') || method_exists($model, 'advancesWorkOrder')) {
      return $model->advancesWorkOrder;
    }

    if (property_exists($model, 'advancesOrderQuotation') || method_exists($model, 'advancesOrderQuotation')) {
      return $model->advancesOrderQuotation;
    }

    throw new \Exception('Model does not have advances relation');
  }

  /**
   * Get the relation name for querying (polymorphic support)
   *
   * @param Model $model
   * @return string
   */
  private function getDocumentRelation(Model $model): string
  {
    // ApWorkOrder uses advancesWorkOrder, ApOrderQuotations uses advancesOrderQuotation
    if (method_exists($model, 'advancesWorkOrder')) {
      return 'advancesWorkOrder';
    }

    if (method_exists($model, 'advancesOrderQuotation')) {
      return 'advancesOrderQuotation';
    }

    throw new \Exception('Model does not have advances relation');
  }
}
