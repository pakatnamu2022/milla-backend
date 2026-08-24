<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Utils\Constants;
use App\Models\ap\configuracionComercial\venta\ApAccountingAccountPlan;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\UnitMeasurement;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\gp\maestroGeneral\SunatConcepts;

/**
 * Servicio especializado para generación de facturación de cotizaciones.
 *
 * Responsabilidades:
 * - Construir invoice preview (vista previa de facturación)
 * - Generar items de factura (quotation details, advances)
 * - Calcular montos de ítems de factura
 * - Proveer estructura correcta para documentos electrónicos
 */
class OrderQuotationBillingService
{
  protected OrderQuotationDocumentService $documentService;
  protected OrderQuotationAdvancePaymentService $advancePaymentService;

  public function __construct(
    OrderQuotationDocumentService         $documentService,
    OrderQuotationAdvancePaymentService   $advancePaymentService
  )
  {
    $this->documentService = $documentService;
    $this->advancePaymentService = $advancePaymentService;
  }

  /**
   * Construye el detalle de facturación (items_invoice) y sus totales (invoice_preview)
   * para esta cotización, igual patrón que ApWorkOrder::getInvoicePreview(). Solo se
   * consideran los detalles PENDIENTES: los ya tomados por una orden de trabajo se
   * facturan desde esa OT (ver ApWorkOrder::buildInvoiceItems()), no desde aquí, para
   * no facturarlos dos veces.
   *
   * @param ApOrderQuotations $quotation
   * @return array{items_invoice: array, invoice_preview: array}
   */
  public function getInvoicePreview(ApOrderQuotations $quotation): array
  {
    $items = $this->buildInvoiceItems($quotation);

    $totalGravada = 0;
    $totalIgv = 0;

    foreach ($items as $item) {
      $totalGravada += $item['subtotal'];
      $totalIgv += $item['igv'];
    }

    // Si la cotización tiene un deducible activo, ya fue cubierto por ese comprobante (aunque
    // el ítem "Deducible" no aparece como línea en items_invoice, ver buildInvoiceItems()),
    // así que se descuenta su gravada e IGV de los totales, igual que en ApWorkOrder. Así
    // invoice_preview.total vuelve a cuadrar con total_amount.
    $activeDeductible = $quotation->deductibles->whereNull('deleted_at')->first();
    if ($activeDeductible && $activeDeductible->electronicDocument) {
      $totalGravada -= (float)$activeDeductible->electronicDocument->total_gravada;
      $totalIgv -= (float)$activeDeductible->electronicDocument->total_igv;
    }

    // total_anticipo es informativo (lo ya cobrado en anticipos sin IGV), por eso se mantiene
    // positivo aunque su línea en items_invoice esté en negativo.
    // Usamos directamente los totales gravados almacenados para evitar problemas de redondeo
    $totalAnticipo = $this->advancePaymentService->getTotalGravadaFromAdvances($quotation);

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
   * Construye todos los items de factura para la cotización
   *
   * @param ApOrderQuotations $quotation
   * @return array
   */
  private function buildInvoiceItems(ApOrderQuotations $quotation): array
  {
    $items = [];

    // Filtrar detalles: NO incluir el ítem de deducible (is_deductible)
    // ya que este resta del total pero no debe aparecer como item en la factura
    $pendingDetails = $quotation->details
      ->where('status', ApOrderQuotationDetails::STATUS_PENDING)
      ->where('is_deductible', false);

    foreach ($pendingDetails as $detail) {
      $items[] = $this->buildDetailInvoiceItem($detail, $quotation);
    }

    foreach ($this->documentService->getActiveAdvances($quotation) as $advance) {
      $items[] = $this->buildAdvanceInvoiceItem($advance, $quotation);
    }

    // Si hay deducible, agregar el texto a la descripción del último item
    if ($quotation->deductible_amount > 0 && count($items) > 0) {
      $firstDeductible = $quotation->deductibles->first();
      if ($firstDeductible && $firstDeductible->electronicDocument) {
        $lastIndex = count($items) - 1;
        $items[$lastIndex]['descripcion'] .= "\nPLACA: " . $quotation->vehicle->plate .
          " - DSCTO POR PAGO DE DEDUCIBLE - Doc: " . $firstDeductible->electronicDocument->full_number;
      }
    }

    return $items;
  }

  /**
   * Construye item de factura para detalle de cotización
   *
   * @param ApOrderQuotationDetails $detail
   * @param ApOrderQuotations $quotation
   * @return array
   */
  private function buildDetailInvoiceItem(ApOrderQuotationDetails $detail, ApOrderQuotations $quotation): array
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
   * @param ApOrderQuotations $quotation
   * @return array
   */
  private function buildAdvanceInvoiceItem(ElectronicDocument $advance, ApOrderQuotations $quotation): array
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
      // Negativo: esta línea resta del total a facturar lo que ya se cobró como
      // anticipo (no es solo informativa), igual que en ApWorkOrder.
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
   * de verdad que ApOrderQuotationDetailsService), en vez de recalcularlos a partir de
   * basePrice/quantity: recalcular redondeando el precio unitario antes de multiplicarlo
   * por una cantidad fraccionaria diverge unos centavos del monto ya guardado.
   * valor_unitario/precio_unitario/descuento se derivan de esos montos ya redondeados
   * solo para mostrar (nunca alimentan el total), para que con cantidad=1 precio_unitario
   * coincida siempre con total: antes se recalculaba desde basePrice crudo (sin descuento
   * y con su propio redondeo), lo que lo desalineaba del total hasta en S/ 0.10.
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
