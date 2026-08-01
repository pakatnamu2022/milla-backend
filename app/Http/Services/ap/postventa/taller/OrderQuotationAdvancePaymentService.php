<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\gp\maestroGeneral\SunatConcepts;

/**
 * Servicio especializado para cálculos de anticipos de cotizaciones.
 *
 * Responsabilidades:
 * - Calcular montos netos de anticipos (considerando NC/ND)
 * - Calcular totales gravados (sin IGV) de anticipos
 * - Proveer una única fuente de verdad para cálculos financieros
 */
class OrderQuotationAdvancePaymentService
{
  protected OrderQuotationDocumentService $documentService;

  public function __construct(OrderQuotationDocumentService $documentService)
  {
    $this->documentService = $documentService;
  }

  /**
   * Obtiene el monto neto pagado en anticipos activos
   * Considera notas de crédito y débito sobre los anticipos
   * (suma de anticipos - NC parciales + ND sobre esos anticipos)
   *
   * @param ApOrderQuotations $quotation
   * @return float
   */
  public function getNetAmountFromAdvances(ApOrderQuotations $quotation): float
  {
    $totalNet = 0;

    foreach ($this->documentService->getActiveAdvances($quotation) as $advance) {
      $totalNet += $this->getNetAmountForAdvance($advance);
    }

    return (float)$totalNet;
  }

  /**
   * Neto de un anticipo puntual (su total menos NC parciales, más ND) aplicando
   * la misma regla que getNetAmountFromAdvances(). Se usa también para armar la
   * línea "anticipo_regularizacion" en getInvoicePreview(), de modo que ambos
   * cuadren siempre entre sí.
   *
   * @param ElectronicDocument $advance
   * @return float
   */
  public function getNetAmountForAdvance(ElectronicDocument $advance): float
  {
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

    return (float)$netAmount;
  }

  /**
   * Obtiene el total gravada (sin IGV) de los anticipos activos
   * Usa directamente los totales gravados almacenados en los documentos electrónicos
   * para evitar problemas de redondeo por divisiones
   *
   * @param ApOrderQuotations $quotation
   * @return float
   */
  public function getTotalGravadaFromAdvances(ApOrderQuotations $quotation): float
  {
    $totalGravada = 0;
    $activeAdvances = $this->documentService->getActiveAdvances($quotation);

    foreach ($activeAdvances as $advance) {
      $advanceGravada = $this->getTotalGravadaForAdvance($advance);
      $totalGravada += $advanceGravada;
    }

    return (float)$totalGravada;
  }

  /**
   * Total gravada (sin IGV) de un anticipo puntual
   * Considera notas de crédito y débito sobre el anticipo
   *
   * @param ElectronicDocument $advance
   * @return float
   */
  public function getTotalGravadaForAdvance(ElectronicDocument $advance): float
  {
    $totalGravada = $advance->total_gravada ?? 0;

    // Restar total gravada de notas de crédito sobre este anticipo
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
      $totalGravada -= ($creditNote->total_gravada ?? 0);
    }

    // Sumar total gravada de notas de débito sobre este anticipo
    $debitNotesOnAdvance = ElectronicDocument::where('original_document_id', $advance->id)
      ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_DEBITO)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', 0)
      ->get();

    foreach ($debitNotesOnAdvance as $debitNote) {
      $totalGravada += ($debitNote->total_gravada ?? 0);
    }

    return (float)$totalGravada;
  }
}
