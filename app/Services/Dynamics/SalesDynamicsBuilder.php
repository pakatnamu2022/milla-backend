<?php

namespace App\Services\Dynamics;

use App\Http\Resources\Dynamics\SalesDocumentDetailDynamicsResource;
use App\Http\Resources\Dynamics\SalesDocumentDynamicsResource;
use App\Http\Resources\Dynamics\SalesDocumentSerialDynamicsResource;
use App\Models\ap\configuracionComercial\venta\ApAccountingAccountPlan;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\UnitMeasurement;
use App\Models\gp\gestionsistema\Company;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SalesDynamicsBuilder
{
  /**
   * Construye el payload completo (sale + items + series) listo para checkResources o sincronización.
   */
  public function buildAll(ElectronicDocument $document): array
  {
    return [
      'sale' => new SalesDocumentDynamicsResource($document),
      'items' => $this->buildItems($document),
      'series' => new SalesDocumentSerialDynamicsResource($document),
    ];
  }

  /**
   * Construye todas las líneas de detalle: ítems del documento + deducible si aplica.
   */
  public function buildItems(ElectronicDocument $document): Collection
  {
    // Obtener el código de repuestos en travesía para discriminar
    $sparePartsRoadAccount = ApAccountingAccountPlan::find(ApAccountingAccountPlan::SPARE_PARTS_ROAD_ID);
    $sparePartsRoadCode = $sparePartsRoadAccount?->code_dynamics;

    // Variables para acumular repuestos en travesía
    $traversePartsTotal = 0;
    $hasTraverseParts = false;

    // Mapear items normales, filtrando:
    // 1. Repuestos en travesía
    // 2. Items de anticipo regularizado (solo si es NC, para evitar duplicados)
    $isCreditNote = $document->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_CREDITO;

    $normalItems = $document->items
      ->filter(function ($item) use ($sparePartsRoadCode, &$traversePartsTotal, &$hasTraverseParts, $isCreditNote) {
        // Filtrar repuestos en travesía
        if ($sparePartsRoadCode && $item->dyn_code === $sparePartsRoadCode) {
          $traversePartsTotal += (float)($item->subtotal ?? 0);
          $hasTraverseParts = true;
          return false;
        }

        // Filtrar items de anticipo regularizado en NC (se agregarán después con la lógica especial)
        if ($isCreditNote && $item->anticipo_regularizacion) {
          return false;
        }

        return true;
      });

    $nextLine = $normalItems->max('line_number') + 1;

    // Calcular sumatoria de anticipos (si es NC a factura final con anticipos)
    $totalAnticipos = 0;
    if ($isCreditNote && $document->original_document_id) {
      $originalDocument = ElectronicDocument::with('items')->find($document->original_document_id);

      if ($originalDocument && !$originalDocument->is_advance_payment) {
        $totalAnticipos = $originalDocument->items()
          ->where('anticipo_regularizacion', true)
          ->sum('subtotal');
      }
    }

    $items = $normalItems->values()->map(function ($item, $index) use ($document, $totalAnticipos) {
      $resource = new SalesDocumentDetailDynamicsResource($item, $document, null);
      $itemArray = $resource->toArray(request());

      // Si es el primer item y hay anticipos, agregar la suma positiva
      if ($index === 0 && $totalAnticipos > 0) {
        $itemArray['PrecioUnitario'] = round((float)$itemArray['PrecioUnitario'] + $totalAnticipos, 2);
        $itemArray['PrecioTotal'] = round((float)$itemArray['PrecioTotal'] + $totalAnticipos, 2);
      }

      return $itemArray;
    });

    // Agregar ítem de deducible si el documento tiene orden de trabajo con deducible
    if ($document->work_order_id && $document->workOrder) {
      $deductibleItem = $this->buildDeductibleLine($document, $nextLine);
      if ($deductibleItem !== null) {
        $items->push($deductibleItem);
        $nextLine++;
      }
    }

    // Agregar ítem de deducible si el documento tiene cotización de mesón con deducible
    if ($document->order_quotation_id && $document->orderQuotation) {
      $deductibleItem = $this->buildDeductibleLineForQuotation($document, $nextLine);
      if ($deductibleItem !== null) {
        $items->push($deductibleItem);
        $nextLine++;
      }
    }

    // Si hubo repuestos en travesía, agregar UN item consolidado al final
    if ($hasTraverseParts && $traversePartsTotal > 0) {
      $traverseItem = $this->buildTraversePartsLine($document, $nextLine, $traversePartsTotal, $sparePartsRoadAccount);
      if ($traverseItem !== null) {
        $items->push($traverseItem);
        $nextLine++;
      }
    }

    // NUEVA LÓGICA: Si es NC a factura final con anticipos, agregar items de anticipo en negativo
    if ($document->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_CREDITO
        && $document->original_document_id) {

      $originalDocument = ElectronicDocument::with('items.referenceDocument')->find($document->original_document_id);

      // Verificar que el documento original sea factura final (no anticipo)
      if ($originalDocument && !$originalDocument->is_advance_payment) {
        // Buscar items de anticipo regularizado en la factura final original
        $anticipoItems = $originalDocument->items()
          ->where('anticipo_regularizacion', true)
          ->whereNotNull('reference_document_id')
          ->with('referenceDocument')
          ->get();

        // Agregar cada item de anticipo en negativo
        foreach ($anticipoItems as $anticipoItem) {
          $anticipoLine = $this->buildAdvancePaymentReversalLine($document, $anticipoItem, $nextLine);
          if ($anticipoLine !== null) {
            $items->push($anticipoLine);
            $nextLine++;
          }
        }
      }
    }

    return $items;
  }

  /**
   * Construye el array de una línea de deducible para Dynamics (Orden de Trabajo).
   * Retorna null si no hay deducible activo o el monto es 0.
   */
  public function buildDeductibleLine(ElectronicDocument $document, int $linea): ?array
  {
    $workOrder = $document->workOrder;

    // Verificar que haya deducible_amount > 0
    if (!$workOrder->deductible_amount || $workOrder->deductible_amount <= 0) {
      return null;
    }

    // Obtener el primer deducible no eliminado
    $firstDeductible = $workOrder->deductibles()->whereNull('deleted_at')->first();

    // Si no hay deducible registrado, no agregar la línea
    if (!$firstDeductible || !$firstDeductible->electronicDocument) {
      return null;
    }

    // Obtener el código del artículo de deducible
    $deductibleAccountPlan = ApAccountingAccountPlan::find(ApAccountingAccountPlan::CUSTOMER_DEDUCTIBLE_ID);
    if (!$deductibleAccountPlan || !$deductibleAccountPlan->code_dynamics) {
      throw new Exception('No se encontró el plan contable de deducible o no tiene código Dynamics definido.');
    }

    // Obtener la unidad de medida de servicio
    $serviceUnit = UnitMeasurement::find(UnitMeasurement::SERVICE_ID);
    $unidadMedidaId = $serviceUnit?->dyn_code ?? 'UNS';

    // Obtener el deducible sin IGV (en negativo porque es un descuento)
    $deductibleWithoutTax = -round($workOrder->deductible_amount_without_tax, 2);

    // Usar el full_number del documento electrónico del deducible como descripción
    $description = 'DEDUCIBLE - DOC: ' . $firstDeductible->electronicDocument->full_number;

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'DocumentoId' => $document->full_number,
      'Linea' => $linea,
      'ArticuloId' => $deductibleAccountPlan->code_dynamics,
      'ArticuloDescripcionCorta' => Str::upper(Str::limit($description, 60, '')),
      'ArticuloDescripcionLarga' => Str::upper($description),
      'SitioId' => $document->warehouse()
        ?? throw new Exception('El documento no tiene almacén asociado.'),
      'UnidadMedidaId' => $unidadMedidaId,
      'Cantidad' => 1,
      'PrecioUnitario' => $deductibleWithoutTax, // Negativo
      'DescuentoUnitario' => 0,
      'PrecioTotal' => $deductibleWithoutTax, // Negativo
    ];
  }

  /**
   * Construye el array de una línea de deducible para Dynamics (Cotización de Mesón).
   * Retorna null si no hay deducible activo o el monto es 0.
   */
  public function buildDeductibleLineForQuotation(ElectronicDocument $document, int $linea): ?array
  {
    $orderQuotation = $document->orderQuotation;

    // Verificar que haya deducible_amount > 0
    if (!$orderQuotation->deductible_amount || $orderQuotation->deductible_amount <= 0) {
      return null;
    }

    // Obtener el primer deducible no eliminado
    $firstDeductible = $orderQuotation->deductibles()->whereNull('deleted_at')->first();

    // Si no hay deducible registrado, no agregar la línea
    if (!$firstDeductible || !$firstDeductible->electronicDocument) {
      return null;
    }

    // Obtener el código del artículo de deducible
    $deductibleAccountPlan = ApAccountingAccountPlan::find(ApAccountingAccountPlan::CUSTOMER_DEDUCTIBLE_ID);
    if (!$deductibleAccountPlan || !$deductibleAccountPlan->code_dynamics) {
      throw new Exception('No se encontró el plan contable de deducible o no tiene código Dynamics definido.');
    }

    // Obtener la unidad de medida de servicio
    $serviceUnit = UnitMeasurement::find(UnitMeasurement::SERVICE_ID);
    $unidadMedidaId = $serviceUnit?->dyn_code ?? 'UNS';

    // Obtener el deducible sin IGV (en negativo porque es un descuento)
    $deductibleWithoutTax = -round($orderQuotation->deductible_amount_without_tax, 2);

    // Usar el full_number del documento electrónico del deducible como descripción
    $description = 'DEDUCIBLE - DOC: ' . $firstDeductible->electronicDocument->full_number;

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'DocumentoId' => $document->full_number,
      'Linea' => $linea,
      'ArticuloId' => $deductibleAccountPlan->code_dynamics,
      'ArticuloDescripcionCorta' => Str::upper(Str::limit($description, 60, '')),
      'ArticuloDescripcionLarga' => Str::upper($description),
      'SitioId' => $document->warehouse()
        ?? throw new Exception('El documento no tiene almacén asociado.'),
      'UnidadMedidaId' => $unidadMedidaId,
      'Cantidad' => 1,
      'PrecioUnitario' => $deductibleWithoutTax, // Negativo
      'DescuentoUnitario' => 0,
      'PrecioTotal' => $deductibleWithoutTax, // Negativo
    ];
  }

  /**
   * Construye el array de una línea consolidada de repuestos en travesía para Dynamics.
   * Retorna null si no hay cuenta contable configurada.
   */
  public function buildTraversePartsLine(ElectronicDocument $document, int $linea, float $totalAmount, ?ApAccountingAccountPlan $sparePartsRoadAccount): ?array
  {
    if (!$sparePartsRoadAccount || !$sparePartsRoadAccount->code_dynamics) {
      return null;
    }

    $description = Str::upper($sparePartsRoadAccount->description ?? 'REPUESTOS EN TRAVESIA');

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'DocumentoId' => $document->full_number,
      'Linea' => $linea,
      'ArticuloId' => $sparePartsRoadAccount->code_dynamics,
      'ArticuloDescripcionCorta' => Str::upper(Str::limit($description, 60, '')),
      'ArticuloDescripcionLarga' => $description,
      'SitioId' => $document->warehouse()
        ?? throw new Exception('El documento no tiene almacén asociado.'),
      'UnidadMedidaId' => 'UNS', // Unidad de servicio
      'Cantidad' => 1, // Siempre 1
      'PrecioUnitario' => $totalAmount, // Suma de todos los repuestos en travesía (sin IGV)
      'DescuentoUnitario' => 0,
      'PrecioTotal' => $totalAmount, // Igual a PrecioUnitario ya que Cantidad = 1
    ];
  }

  /**
   * Construye el array de una línea de anticipo en negativo para NC a factura final.
   * Se usa cuando se hace NC a una factura final que tenía anticipos regularizados.
   *
   * @param ElectronicDocument $creditNoteDocument La nota de crédito que se está creando
   * @param mixed $anticipoItem El item de anticipo de la factura final original
   * @param int $linea Número de línea para este item
   * @return array|null Array con los datos del item o null si falta información
   */
  public function buildAdvancePaymentReversalLine(ElectronicDocument $creditNoteDocument, $anticipoItem, int $linea): ?array
  {
    // Validar que exista el documento de anticipo referenciado
    if (!$anticipoItem->referenceDocument) {
      return null;
    }

    // Calcular montos en negativo (para reversión en NC)
    $subtotalNegativo = -abs((float)$anticipoItem->subtotal);

    // Descripción del anticipo
    $description = Str::upper($anticipoItem->descripcion ?? 'ANTICIPO');

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'DocumentoId' => $creditNoteDocument->full_number,
      'Linea' => $linea,
      'ArticuloId' => 'V0000002', // Código fijo para anticipos
      'ArticuloDescripcionCorta' => Str::upper(Str::limit($description, 60, '')),
      'ArticuloDescripcionLarga' => $description,
      'SitioId' => $creditNoteDocument->warehouse()
        ?? throw new Exception('El documento no tiene almacén asociado.'),
      'UnidadMedidaId' => $anticipoItem->unidad_medida_dyn ?? 'UNS',
      'Cantidad' => abs((float)$anticipoItem->cantidad),
      'PrecioUnitario' => -abs((float)$anticipoItem->valor_unitario), // Negativo
      'DescuentoUnitario' => (float)($anticipoItem->descuento_unitario ?? 0),
      'PrecioTotal' => $subtotalNegativo, // Negativo
    ];
  }
}
