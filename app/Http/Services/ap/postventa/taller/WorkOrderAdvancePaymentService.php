<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Database\Eloquent\Model;

/**
 * Servicio especializado para cálculos de anticipos de órdenes de trabajo y cotizaciones.
 *
 * Responsabilidades:
 * - Calcular montos netos de anticipos (considerando NC/ND)
 * - Calcular subtotales (sin IGV) de anticipos
 * - Proveer una única fuente de verdad para cálculos financieros
 */
class WorkOrderAdvancePaymentService
{
  protected WorkOrderDocumentService $documentService;

  public function __construct(WorkOrderDocumentService $documentService)
  {
    $this->documentService = $documentService;
  }

  /**
   * Obtiene el monto neto pagado en anticipos activos
   * Considera notas de crédito y débito sobre los anticipos
   * (suma de anticipos - NC parciales + ND sobre esos anticipos)
   *
   * @param Model $model Instance of ApWorkOrder or ApOrderQuotations
   * @return float
   */
  public function getNetAmountFromAdvances(Model $model): float
  {
    $totalNet = 0;
    $activeAdvances = $this->documentService->getActiveAdvances($model);

    foreach ($activeAdvances as $advance) {
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
   * Obtiene el subtotal (sin IGV) de los anticipos activos
   * Usa directamente los subtotales almacenados en los documentos electrónicos
   * para evitar problemas de redondeo por divisiones
   *
   * @param Model $model Instance of ApWorkOrder
   * @return float
   */
  public function getSubtotalFromAdvances(Model $model): float
  {
    $totalSubtotal = 0;
    $activeAdvances = $this->documentService->getActiveAdvances($model);

    foreach ($activeAdvances as $advance) {
      $advanceSubtotal = $this->getSubtotalForAdvance($advance);
      $totalSubtotal += $advanceSubtotal;
    }

    return (float)$totalSubtotal;
  }

  /**
   * Subtotal (sin IGV) de un anticipo puntual
   * Considera notas de crédito y débito sobre el anticipo
   *
   * @param ElectronicDocument $advance
   * @return float
   */
  public function getSubtotalForAdvance(ElectronicDocument $advance): float
  {
    $subtotal = $advance->total_gravada ?? 0;

    // Restar subtotales de notas de crédito sobre este anticipo
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
      $subtotal -= ($creditNote->total_gravada ?? 0);
    }

    // Sumar subtotales de notas de débito sobre este anticipo
    $debitNotesOnAdvance = ElectronicDocument::where('original_document_id', $advance->id)
      ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_DEBITO)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', 0)
      ->get();

    foreach ($debitNotesOnAdvance as $debitNote) {
      $subtotal += ($debitNote->total_gravada ?? 0);
    }

    return (float)$subtotal;
  }

  /**
   * Obtiene el total gravada (sin IGV) de los anticipos activos (para cotizaciones)
   * Usa directamente los totales gravados almacenados en los documentos electrónicos
   * para evitar problemas de redondeo por divisiones
   *
   * @param Model $model Instance of ApOrderQuotations
   * @return float
   */
  public function getTotalGravadaFromAdvances(Model $model): float
  {
    $totalGravada = 0;
    $activeAdvances = $this->documentService->getActiveAdvances($model);

    foreach ($activeAdvances as $advance) {
      $advanceGravada = $this->getTotalGravadaForAdvance($advance);
      $totalGravada += $advanceGravada;
    }

    return (float)$totalGravada;
  }

  /**
   * Total gravada (sin IGV) de un anticipo puntual (para cotizaciones)
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

  /**
   * Valida que el nuevo final_amount no sea menor al monto ya pagado en anticipos.
   * Se usa antes de permitir ediciones que reduzcan el monto de la OT.
   *
   * Esta validación garantiza integridad financiera: no se puede reducir el total de una OT
   * por debajo de lo que ya se cobró en anticipos. Si esto fuera necesario, primero se deben
   * anular los anticipos correspondientes.
   *
   * @param Model $model Instance of ApWorkOrder or ApOrderQuotations
   * @param float $newFinalAmount El nuevo monto total proyectado
   * @throws \Exception Si el nuevo monto es menor al ya pagado
   */
  public function validateMinimumAmount(Model $model, float $newFinalAmount): void
  {
    $paidAmount = $this->getNetAmountFromAdvances($model);

    // Redondear a 2 decimales para evitar problemas de precisión con floats
    // Si son iguales o mayor, se permite; solo se rechaza si es estrictamente menor
    if ($paidAmount > 0 && round($newFinalAmount, 2) < round($paidAmount, 2)) {
      throw new \Exception(
        "El nuevo monto total (S/. " . number_format($newFinalAmount, 2) . ") " .
        "no puede ser menor al monto ya pagado en anticipos (S/. " . number_format($paidAmount, 2) . "). " .
        "Debe anular los anticipos correspondientes antes de reducir el monto de la orden de trabajo."
      );
    }
  }

  /**
   * Valida que un descuento solicitado no reduzca el monto total por debajo de los anticipos pagados.
   * Simula la aplicación del descuento y verifica contra los anticipos.
   *
   * Este método calcula el impacto proyectado de un descuento (global o parcial) sobre el monto final
   * de la orden de trabajo, y valida que después de aplicar ese descuento, el monto no sea inferior
   * a lo ya cobrado en anticipos.
   *
   * @param Model $model Instance of ApWorkOrder
   * @param string $type GLOBAL o PARTIAL
   * @param string $partLabourModel ApWorkOrderParts::class o WorkOrderLabour::class
   * @param float $discountPercentage Porcentaje de descuento a aplicar
   * @param int|null $partLabourId ID del ítem específico (solo para PARTIAL)
   * @throws \Exception Si el descuento reduce el monto por debajo de los anticipos pagados
   */
  public function validateDiscountAgainstAdvances(
    Model  $model,
    string $type,
    string $partLabourModel,
    float  $discountPercentage,
    ?int   $partLabourId = null
  ): void
  {
    // Obtener totales actuales usando el método del modelo
    $currentTotals = $model->getTotalsArray();

    // Variables para acumular el impacto del descuento
    $additionalDiscountAmount = 0;

    if ($type === 'PARTIAL') {
      // Descuento a un ítem específico
      if (!$partLabourId) {
        throw new \Exception('Para descuento PARTIAL se requiere el ID del ítem.');
      }

      // Obtener el ítem usando las relaciones del modelo
      if ($partLabourModel === 'ApWorkOrderParts' || str_contains($partLabourModel, 'ApWorkOrderParts')) {
        $item = $model->parts()->find($partLabourId);
        if (!$item) {
          throw new \Exception('Repuesto no encontrado.');
        }
      } elseif ($partLabourModel === 'WorkOrderLabour' || str_contains($partLabourModel, 'WorkOrderLabour')) {
        $item = $model->labours()->find($partLabourId);
        if (!$item) {
          throw new \Exception('Mano de obra no encontrada.');
        }
      } else {
        throw new \Exception('Tipo de ítem no válido.');
      }

      // Calcular el descuento adicional sobre este ítem
      $itemTotalCost = (float)$item->total_cost;
      $itemCurrentDiscount = $itemTotalCost - (float)$item->net_amount;

      // Nuevo descuento sobre el total_cost
      $newDiscountAmount = $itemTotalCost * ($discountPercentage / 100);

      // Descuento adicional = nuevo descuento - descuento actual
      $additionalDiscountAmount = $newDiscountAmount - $itemCurrentDiscount;

    } elseif ($type === 'GLOBAL') {
      // Descuento global a todos los ítems del tipo especificado
      if ($partLabourModel === 'ApWorkOrderParts' || str_contains($partLabourModel, 'ApWorkOrderParts')) {
        $items = $model->parts;
      } elseif ($partLabourModel === 'WorkOrderLabour' || str_contains($partLabourModel, 'WorkOrderLabour')) {
        $items = $model->labours;
      } else {
        throw new \Exception('Tipo de ítem no válido.');
      }

      // Calcular el descuento adicional total
      foreach ($items as $item) {
        $itemTotalCost = (float)$item->total_cost;
        $itemCurrentDiscount = $itemTotalCost - (float)$item->net_amount;

        // Nuevo descuento sobre el total_cost
        $newDiscountAmount = $itemTotalCost * ($discountPercentage / 100);

        // Descuento adicional = nuevo descuento - descuento actual
        $additionalDiscountAmount += ($newDiscountAmount - $itemCurrentDiscount);
      }
    }

    // Calcular el nuevo net_amount (suma de todos los net_amount después del descuento)
    $currentNetAmount = (float)$currentTotals['net_amount'];
    $projectedNetAmount = $currentNetAmount - $additionalDiscountAmount;

    // Aplicar IGV sobre el nuevo net_amount
    // Usar la constante de IVA desde la clase Constants (18%)
    $vatTax = defined('App\Http\Utils\Constants::VAT_TAX') ? \App\Http\Utils\Constants::VAT_TAX : 18;
    $projectedTaxAmount = $projectedNetAmount * ($vatTax / 100);

    // Calcular el nuevo monto total final (incluye IGV)
    $projectedFinalAmount = $projectedNetAmount + $projectedTaxAmount;

    // Validar contra los anticipos pagados
    $this->validateMinimumAmount($model, $projectedFinalAmount);
  }
}