<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Utils\Constants;
use App\Models\ap\configuracionComercial\venta\ApAccountingAccountPlan;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\UnitMeasurement;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\ApWorkOrderParts;
use App\Models\ap\postventa\taller\WorkOrderLabour;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Database\Eloquent\Model;

/**
 * Servicio especializado para generación de facturación de órdenes de trabajo y cotizaciones.
 *
 * Responsabilidades:
 * - Construir invoice preview (vista previa de facturación)
 * - Generar items de factura (labours, parts, quotation details, advances)
 * - Calcular montos de ítems de factura
 * - Proveer estructura correcta para documentos electrónicos
 */
class WorkOrderBillingService
{
  protected WorkOrderDocumentService $documentService;
  protected WorkOrderAdvancePaymentService $advancePaymentService;

  public function __construct(
    WorkOrderDocumentService         $documentService,
    WorkOrderAdvancePaymentService   $advancePaymentService
  )
  {
    $this->documentService = $documentService;
    $this->advancePaymentService = $advancePaymentService;
  }

  /**
   * Construye el detalle de facturación (items_invoice) y sus totales (invoice_preview)
   * para que el frontend deje de recalcular esto por su cuenta.
   *
   * Reutiliza los montos ya persistidos por item (net_amount/tax_amount/total_cost) y
   * el mismo neto de anticipos usado en getPaymentSummary(), así todo cuadra entre sí.
   *
   * @param ApWorkOrder $workOrder
   * @return array{items_invoice: array, invoice_preview: array}
   */
  public function getInvoicePreviewForWorkOrder(ApWorkOrder $workOrder): array
  {
    $items = $this->buildInvoiceItemsForWorkOrder($workOrder);

    // La línea anticipo_regularizacion va en negativo (ver buildAdvanceInvoiceItem),
    // igual que buildRegularizationItems() para vehículos, así que SÍ se suma aquí junto
    // con el resto: el neto es justamente lo que falta facturar ahora (0 si el/los
    // anticipo(s) ya cubrieron todo). total_gravada/total_igv/total quedan siempre
    // = suma exacta de items_invoice, sin ninguna fórmula aparte.
    $totalGravada = 0;
    $totalIgv = 0;

    foreach ($items as $item) {
      $totalGravada += $item['subtotal'];
      $totalIgv += $item['igv'];
    }

    // Si la OT tiene deducibles activos, ya fueron cubiertos por esos comprobantes (aunque
    // el ítem "Deducible" no aparece como línea en items_invoice, ver buildInvoiceItems()),
    // así que se descuenta su gravada e IGV de los totales, igual que calculateTotals()
    // ya neteaba el deducible completo (con IGV) en subtotal/tax_amount/final_amount de
    // la OT. Así invoice_preview.total vuelve a cuadrar con final_amount.
    $activeDeductibles = $workOrder->deductibles->whereNull('deleted_at');
    foreach ($activeDeductibles as $deductible) {
      if ($deductible->electronicDocument) {
        $totalGravada -= (float)$deductible->electronicDocument->total_gravada;
        $totalIgv -= (float)$deductible->electronicDocument->total_igv;
      }
    }

    // total_anticipo es informativo (lo ya cobrado en anticipos), por eso se mantiene
    // positivo aunque su línea en items_invoice esté en negativo.
    // Usamos directamente los subtotales almacenados para evitar problemas de redondeo
    $totalAnticipo = $this->advancePaymentService->getSubtotalFromAdvances($workOrder);

    // Redondear los totales
    $totalGravadaRounded = round($totalGravada, 2);
    $totalIgvRounded = round($totalIgv, 2);
    $totalFinal = round($totalGravada + $totalIgv, 2);

    // Si el total está muy cercano a 0 (dentro del umbral de ±0.03 por errores de redondeo),
    // forzar tanto total_gravada como total_igv a 0 para evitar inconsistencias como tener
    // IGV positivo sobre base gravada negativa cuando el total es 0.
    if (abs($totalFinal) < 0.03) {
      $totalGravadaRounded = 0;
      $totalIgvRounded = 0;
      $totalFinal = 0;
    }

    // +0 normaliza el -0.0 que puede salir al cancelarse gravada/igv contra el anticipo
    // negativo (matemáticamente es cero, pero "-0" en el JSON se ve como un bug).
    return [
      'items_invoice' => $items,
      'invoice_preview' => [
        'total_gravada' => $totalGravadaRounded + 0,
        'total_inafecta' => 0,
        'total_exonerada' => 0,
        'total_igv' => $totalIgvRounded + 0,
        'total_gratuita' => 0,
        'total_anticipo' => round($totalAnticipo, 2) + 0,
        'total' => $totalFinal + 0,
      ],
    ];
  }

  /**
   * Construye el detalle de facturación para cotizaciones (solo items pendientes)
   *
   * @param Model $quotation ApOrderQuotations
   * @return array{items_invoice: array, invoice_preview: array}
   */
  public function getInvoicePreviewForQuotation(Model $quotation): array
  {
    $items = $this->buildInvoiceItemsForQuotation($quotation);

    $totalGravada = 0;
    $totalIgv = 0;

    foreach ($items as $item) {
      $totalGravada += $item['subtotal'];
      $totalIgv += $item['igv'];
    }

    // total_anticipo es informativo (lo ya cobrado en anticipos sin IGV), por eso se mantiene
    // positivo aunque su línea en items_invoice esté en negativo.
    // Usamos directamente los totales gravados almacenados para evitar problemas de redondeo
    $totalAnticipo = $this->advancePaymentService->getTotalGravadaFromAdvances($quotation);

    // +0 normaliza el -0.0 que puede salir al cancelarse gravada/igv contra el anticipo
    // negativo (matemáticamente es cero, pero "-0" en el JSON se ve como un bug).
    return [
      'items_invoice' => $items,
      'invoice_preview' => [
        'total_gravada' => round($totalGravada, 2) + 0,
        'total_inafecta' => 0,
        'total_exonerada' => 0,
        'total_igv' => round($totalIgv, 2) + 0,
        'total_gratuita' => 0,
        'total_anticipo' => round($totalAnticipo, 2) + 0,
        'total' => round($totalGravada + $totalIgv, 2) + 0,
      ],
    ];
  }

  /**
   * Construye todos los items de factura para una orden de trabajo
   *
   * @param ApWorkOrder $workOrder
   * @return array
   */
  private function buildInvoiceItemsForWorkOrder(ApWorkOrder $workOrder): array
  {
    $items = [];

    // Filtrar labours: NO incluir el ítem de deducible (is_deductible)
    // ya que este resta del total de mano de obra pero no debe aparecer como item en la factura
    foreach ($workOrder->labours as $labour) {
      if (!$labour->is_deductible) {
        $items[] = $this->buildLabourInvoiceItem($labour);
      }
    }

    foreach ($workOrder->parts as $part) {
      $items[] = $this->buildPartInvoiceItem($part);
    }

    // Misma condición que calculateTotalsWithQuotation(): si la OT está ligada a una
    // cotización, sus ítems PENDIENTES (aún no materializados en labours/parts) también
    // forman parte de lo que hay que facturar y ya cuentan en final_amount.
    if ($workOrder->order_quotation_id && $workOrder->orderQuotation) {
      $pendingDetails = $workOrder->orderQuotation->details
        ->where('status', ApOrderQuotationDetails::STATUS_PENDING);

      foreach ($pendingDetails as $detail) {
        $items[] = $this->buildQuotationDetailInvoiceItem($detail);
      }
    }

    foreach ($this->documentService->getActiveAdvances($workOrder) as $advance) {
      $items[] = $this->buildAdvanceInvoiceItem($advance);
    }

    // Si hay deducibles, agregar el texto a la descripción del último item
    if ($workOrder->deductible_amount > 0 && count($items) > 0) {
      $activeDeductibles = $workOrder->deductibles->whereNull('deleted_at');

      if ($activeDeductibles->isNotEmpty()) {
        $lastIndex = count($items) - 1;
        $deductibleTexts = [];

        foreach ($activeDeductibles as $deductible) {
          if ($deductible->electronicDocument) {
            $deductibleTexts[] = $deductible->electronicDocument->full_number;
          }
        }

        if (!empty($deductibleTexts)) {
          $items[$lastIndex]['descripcion'] .= "\nPLACA: " . $workOrder->vehicle_plate .
            " - DSCTO POR PAGO DE DEDUCIBLE - Doc: " . implode(', ', $deductibleTexts);
        }
      }
    }

    return $items;
  }

  /**
   * Construye items de factura para cotización (solo items pendientes)
   *
   * @param Model $quotation ApOrderQuotations
   * @return array
   */
  private function buildInvoiceItemsForQuotation(Model $quotation): array
  {
    $items = [];

    $pendingDetails = $quotation->details->where('status', ApOrderQuotationDetails::STATUS_PENDING);
    foreach ($pendingDetails as $detail) {
      $items[] = $this->buildDetailInvoiceItemForQuotation($detail);
    }

    foreach ($this->documentService->getActiveAdvances($quotation) as $advance) {
      $items[] = $this->buildAdvanceInvoiceItem($advance);
    }

    return $items;
  }

  /**
   * Construye item de factura para mano de obra
   *
   * @param WorkOrderLabour $labour
   * @return array
   */
  private function buildLabourInvoiceItem(WorkOrderLabour $labour): array
  {
    $isMaterial = $labour->labour_type === WorkOrderLabour::LABOUR_TYPE_MATERIAL;

    $billing = $this->calculateInvoiceItemAmounts(
      (float)$labour->hourly_rate,
      (float)$labour->time_spent_decimal,
      (float)$labour->discount_percentage,
      (float)$labour->net_amount,
      (float)$labour->tax_amount
    );

    return array_merge([
      'type' => 'labour',
      'source_id' => $labour->id,
      'account_plan_id' => $isMaterial
        ? ApAccountingAccountPlan::LABOUR_ACCOUNT_MATERIAL_ID
        : ApAccountingAccountPlan::LABOUR_ACCOUNT_ID,
      'unidad_de_medida' => $this->getServiceUnitCode(),
      'codigo' => (string)$labour->id,
      'product_id' => null,
      'descripcion' => $labour->description,
      'cantidad' => (float)$labour->time_spent_decimal,
      'sunat_concept_igv_type_id' => SunatConcepts::ID_IGV_GRAVADO_ONEROSA,
      'anticipo_regularizacion' => false,
      'anticipo_documento_serie' => null,
      'anticipo_documento_numero' => null,
      'reference_document_id' => null,
      'from_quotation' => false,
    ], $billing);
  }

  /**
   * Construye item de factura para repuesto
   *
   * @param ApWorkOrderParts $part
   * @return array
   */
  private function buildPartInvoiceItem(ApWorkOrderParts $part): array
  {
    $billing = $this->calculateInvoiceItemAmounts(
      (float)$part->unit_price,
      (float)$part->quantity_used,
      (float)$part->discount_percentage,
      (float)$part->net_amount,
      (float)$part->tax_amount
    );

    return array_merge([
      'type' => 'part',
      'source_id' => $part->id,
      'account_plan_id' => ApAccountingAccountPlan::AFTER_SALES_MAINTENANCE_SERVICE_ID,
      'unidad_de_medida' => $part->product?->unitMeasurement?->nubefac_code ?? 'NIU',
      'codigo' => $part->product?->code ?? (string)$part->product_id,
      'product_id' => $part->product_id,
      'descripcion' => $part->product?->name,
      'cantidad' => (float)$part->quantity_used,
      'sunat_concept_igv_type_id' => SunatConcepts::ID_IGV_GRAVADO_ONEROSA,
      'anticipo_regularizacion' => false,
      'anticipo_documento_serie' => null,
      'anticipo_documento_numero' => null,
      'reference_document_id' => null,
      'from_quotation' => false,
    ], $billing);
  }

  /**
   * Construye item de factura para detalle de cotización (para WorkOrder)
   *
   * @param ApOrderQuotationDetails $detail
   * @return array
   */
  private function buildQuotationDetailInvoiceItem(ApOrderQuotationDetails $detail): array
  {
    $billing = $this->calculateInvoiceItemAmounts(
      (float)$detail->unit_price,
      (float)$detail->quantity,
      (float)$detail->discount_percentage,
      (float)$detail->net_amount,
      (float)$detail->tax_amount
    );

    if ($detail->item_type === ApOrderQuotationDetails::ITEM_TYPE_LABOR) {
      $isMaterial = trim(strtolower($detail->description ?? '')) === 'materiales';

      return array_merge([
        'type' => 'labour',
        'source_id' => $detail->id,
        'account_plan_id' => $isMaterial
          ? ApAccountingAccountPlan::LABOUR_ACCOUNT_MATERIAL_ID
          : ApAccountingAccountPlan::LABOUR_ACCOUNT_ID,
        'unidad_de_medida' => $this->getServiceUnitCode(),
        'codigo' => (string)$detail->id,
        'product_id' => null,
        'descripcion' => $detail->description,
        'cantidad' => (float)$detail->quantity,
        'sunat_concept_igv_type_id' => SunatConcepts::ID_IGV_GRAVADO_ONEROSA,
        'anticipo_regularizacion' => false,
        'anticipo_documento_serie' => null,
        'anticipo_documento_numero' => null,
        'reference_document_id' => null,
        'from_quotation' => true,
      ], $billing);
    }

    return array_merge([
      'type' => 'part',
      'source_id' => $detail->id,
      'account_plan_id' => ApAccountingAccountPlan::AFTER_SALES_MAINTENANCE_SERVICE_ID,
      'unidad_de_medida' => $detail->product?->unitMeasurement?->nubefac_code ?? 'NIU',
      'codigo' => $detail->product?->code ?? (string)$detail->product_id,
      'product_id' => $detail->product_id,
      'descripcion' => $detail->product?->name ?? $detail->description,
      'cantidad' => (float)$detail->quantity,
      'sunat_concept_igv_type_id' => SunatConcepts::ID_IGV_GRAVADO_ONEROSA,
      'anticipo_regularizacion' => false,
      'anticipo_documento_serie' => null,
      'anticipo_documento_numero' => null,
      'reference_document_id' => null,
      'from_quotation' => true,
    ], $billing);
  }

  /**
   * Construye item de factura para detalle de cotización (para Quotations directas)
   *
   * @param ApOrderQuotationDetails $detail
   * @return array
   */
  private function buildDetailInvoiceItemForQuotation(ApOrderQuotationDetails $detail): array
  {
    $billing = $this->calculateInvoiceItemAmounts(
      (float)$detail->unit_price,
      (float)$detail->quantity,
      (float)$detail->discount_percentage,
      (float)$detail->net_amount,
      (float)$detail->tax_amount
    );

    if ($detail->item_type === ApOrderQuotationDetails::ITEM_TYPE_LABOR) {
      $isMaterial = trim(strtolower($detail->description ?? '')) === 'materiales';

      return array_merge([
        'type' => 'labour',
        'source_id' => $detail->id,
        'account_plan_id' => $isMaterial
          ? ApAccountingAccountPlan::LABOUR_ACCOUNT_MATERIAL_ID
          : ApAccountingAccountPlan::LABOUR_ACCOUNT_ID,
        'unidad_de_medida' => $this->getServiceUnitCode(),
        'codigo' => (string)$detail->id,
        'product_id' => null,
        'descripcion' => $detail->description,
        'cantidad' => (float)$detail->quantity,
        'sunat_concept_igv_type_id' => SunatConcepts::ID_IGV_GRAVADO_ONEROSA,
        'anticipo_regularizacion' => false,
        'anticipo_documento_serie' => null,
        'anticipo_documento_numero' => null,
        'reference_document_id' => null,
        'from_quotation' => true,
      ], $billing);
    }

    return array_merge([
      'type' => 'part',
      'source_id' => $detail->id,
      'account_plan_id' => ApAccountingAccountPlan::AFTER_SALES_MAINTENANCE_SERVICE_ID,
      'unidad_de_medida' => $detail->product?->unitMeasurement?->nubefac_code ?? 'NIU',
      'codigo' => $detail->product?->code ?? (string)$detail->product_id,
      'product_id' => $detail->product_id,
      'descripcion' => $detail->product?->name ?? $detail->description,
      'cantidad' => (float)$detail->quantity,
      'sunat_concept_igv_type_id' => SunatConcepts::ID_IGV_GRAVADO_ONEROSA,
      'anticipo_regularizacion' => false,
      'anticipo_documento_serie' => null,
      'anticipo_documento_numero' => null,
      'reference_document_id' => null,
      'from_quotation' => true,
    ], $billing);
  }

  /**
   * Construye item de factura para anticipo (regularización)
   *
   * @param ElectronicDocument $advance
   * @return array
   */
  private function buildAdvanceInvoiceItem(ElectronicDocument $advance): array
  {
    $netTotal = $this->advancePaymentService->getNetAmountForAdvance($advance);
    $valorUnitario = round($netTotal / (1 + Constants::VAT_TAX / 100), 2);
    $igv = round($netTotal - $valorUnitario, 2);

    return [
      'type' => 'anticipo_regularizacion',
      'source_id' => $advance->id,
      'account_plan_id' => ApAccountingAccountPlan::ADVANCE_PAYMENTS_ACCOUNT_ID,
      'unidad_de_medida' => $this->getServiceUnitCode(),
      'codigo' => (string)$advance->id,
      'product_id' => null,
      'descripcion' => 'ANTICIPO: ' . $advance->serie . '-' . $advance->numero
        . ' DEL ' . $advance->fecha_de_emision?->format('d/m/Y'),
      'cantidad' => 1,
      // Negativo, igual que buildRegularizationItems() para vehículos: esta línea resta
      // del total a facturar lo que ya se cobró como anticipo (no es solo informativa).
      'valor_unitario' => -$valorUnitario,
      'precio_unitario' => -round($netTotal, 2),
      'descuento' => null,
      'subtotal' => -$valorUnitario,
      'sunat_concept_igv_type_id' => SunatConcepts::ID_IGV_ANTICIPO_GRAVADO,
      'igv' => -$igv,
      'total' => -round($netTotal, 2),
      'anticipo_regularizacion' => true,
      'anticipo_documento_serie' => $advance->serie,
      'anticipo_documento_numero' => $advance->numero,
      'reference_document_id' => $advance->id,
      'from_quotation' => false,
    ];
  }

  /**
   * Obtiene labours y parts dinámicamente para uso en PDFs y otros reportes:
   * - Si NO tiene cotización: retorna labours y parts existentes
   * - Si SÍ tiene cotización: retorna labours y parts existentes + items pendientes de la cotización
   *
   * Este método es utilizado principalmente para generar PDFs de la orden de trabajo,
   * donde se deben mostrar tanto los items ya materializados como los pendientes de cotización.
   *
   * @param ApWorkOrder $workOrder
   * @param int|null $groupNumber Filtro opcional por número de grupo
   * @return array{labours: \Illuminate\Support\Collection, parts: \Illuminate\Support\Collection}
   */
  public function getDynamicItemsForInvoicing(ApWorkOrder $workOrder, ?int $groupNumber = null): array
  {
    // Si NO tiene cotización asociada, retornar items existentes
    if (!$workOrder->order_quotation_id || !$workOrder->orderQuotation) {
      $labours = $groupNumber !== null
        ? $workOrder->labours->where('group_number', $groupNumber)
        : $workOrder->labours;

      $parts = $groupNumber !== null
        ? $workOrder->parts->where('group_number', $groupNumber)
        : $workOrder->parts;

      return [
        'labours' => $labours,
        'parts' => $parts,
      ];
    }

    // Si SÍ tiene cotización asociada, incluir items pendientes
    $labours = $groupNumber !== null
      ? $workOrder->labours->where('group_number', $groupNumber)
      : $workOrder->labours;

    $parts = $groupNumber !== null
      ? $workOrder->parts->where('group_number', $groupNumber)
      : $workOrder->parts;

    // Obtener items pendientes de la cotización
    $quotationLabours = collect();
    $quotationParts = collect();

    $pendingDetails = $workOrder->orderQuotation->details
      ->where('status', ApOrderQuotationDetails::STATUS_PENDING);

    foreach ($pendingDetails as $detail) {
      if ($detail->item_type === ApOrderQuotationDetails::ITEM_TYPE_LABOR) {
        // Mapear ApOrderQuotationDetails a estructura de WorkOrderLabour.
        // total_cost/net_amount/tax_amount se toman TAL CUAL del detalle (misma fuente
        // que usa calculateTotalsWithQuotation()), nunca se recalculan aquí: recalcular
        // asumiendo cantidad=1 producía un net_amount incorrecto cuando el detalle real
        // tenía otra cantidad (ej. 2.5 horas), y con eso este PDF llegó a mostrar un monto
        // muy por debajo del real.
        $mappedLabour = new \stdClass();
        $mappedLabour->id = null; // No tiene ID porque es de cotización
        $mappedLabour->description = $detail->description;
        $mappedLabour->time_spent = null;
        $mappedLabour->hourly_rate = (float)$detail->unit_price;
        $mappedLabour->discount_percentage = (float)$detail->discount_percentage;
        $mappedLabour->total_cost = (float)$detail->total_cost;
        $mappedLabour->tax_amount = (float)$detail->tax_amount;
        $mappedLabour->net_amount = (float)$detail->net_amount;
        $mappedLabour->worker_id = null;
        $mappedLabour->worker = null;
        $mappedLabour->group_number = null;
        $mappedLabour->work_order_id = $workOrder->id;
        $mappedLabour->from_quotation = true; // Flag para identificar que viene de cotización

        $quotationLabours->push($mappedLabour);
      } elseif ($detail->item_type === ApOrderQuotationDetails::ITEM_TYPE_PRODUCT) {
        // Mapear ApOrderQuotationDetails a estructura de ApWorkOrderParts.
        // Mismo criterio: total_cost/net_amount/tax_amount tal cual el detalle, sin
        // recalcular (antes tax_amount quedaba hardcodeado en 0 para estos ítems).
        $mappedPart = new \stdClass();
        $mappedPart->id = null; // No tiene ID porque es de cotización
        $mappedPart->product_id = $detail->product_id;
        $mappedPart->quantity_used = (float)$detail->quantity;
        $mappedPart->unit_cost = (float)($detail->purchase_price ?? 0);
        $mappedPart->unit_price = (float)$detail->unit_price;
        $mappedPart->discount_percentage = (float)$detail->discount_percentage;
        $mappedPart->total_cost = (float)$detail->total_cost;
        $mappedPart->tax_amount = (float)$detail->tax_amount;
        $mappedPart->net_amount = (float)$detail->net_amount;
        $mappedPart->product = $detail->product;
        $mappedPart->group_number = null;
        $mappedPart->work_order_id = $workOrder->id;
        $mappedPart->warehouse_id = null;
        $mappedPart->warehouse = null;
        $mappedPart->registered_by = null;
        $mappedPart->is_delivered = false;
        $mappedPart->from_quotation = true; // Flag para identificar que viene de cotización

        $quotationParts->push($mappedPart);
      }
    }

    // Combinar items existentes con items pendientes de cotización
    $allLabours = $labours->concat($quotationLabours);
    $allParts = $parts->concat($quotationParts);

    return [
      'labours' => $allLabours,
      'parts' => $allParts,
    ];
  }

  /**
   * Código SUNAT (catálogo 03) de "servicio", única fuente de verdad para las líneas
   * de mano de obra y de anticipo en items_invoice. Cambiar el nubefac_code del
   * UnitMeasurement::SERVICE_ID en la BD basta para que ambas líneas se actualicen.
   */
  private function getServiceUnitCode(): string
  {
    return UnitMeasurement::find(UnitMeasurement::SERVICE_ID)?->nubefac_code ?? 'ZZ';
  }

  /**
   * valor_unitario/precio_unitario/descuento/subtotal/igv/total de una línea gravada.
   *
   * subtotal/igv se toman DIRECTO de net_amount/tax_amount ya persistidos (misma fuente
   * de verdad que WorkOrderLabourService/ApWorkOrderPartsService), en vez de recalcularlos
   * a partir de basePrice/quantity: recalcular redondeando el precio unitario antes de
   * multiplicarlo por una cantidad fraccionaria (ej. 7.5 litros) diverge unos centavos del
   * monto ya guardado. valor_unitario/precio_unitario/descuento se derivan de esos montos
   * ya redondeados solo para mostrar (nunca alimentan el total), para que con cantidad=1
   * precio_unitario coincida siempre con total: antes se recalculaba desde basePrice crudo
   * (sin descuento y con su propio redondeo), lo que lo desalineaba del total hasta en S/ 0.10.
   */
  private function calculateInvoiceItemAmounts(
    float $basePrice,
    float $quantity,
    float $discountPercentage,
    float $netAmount,
    float $taxAmount
  ): array
  {
    $subtotal = round($netAmount, 2);
    $igv = round($taxAmount, 2);
    $total = round($subtotal + $igv, 2);

    // Según SUNAT/UBL 2.1: valor_unitario y precio_unitario deben ser ANTES del descuento
    $valorUnitario = round($basePrice, 2);
    $precioUnitario = round($basePrice * (1 + Constants::VAT_TAX / 100), 2);
    $descuento = $discountPercentage > 0 ? round(($basePrice * $quantity) - $netAmount, 2) : null;

    return [
      'valor_unitario' => $valorUnitario,
      'precio_unitario' => $precioUnitario,
      'descuento' => $descuento,
      'subtotal' => $subtotal,
      'igv' => $igv,
      'total' => $total,
    ];
  }
}