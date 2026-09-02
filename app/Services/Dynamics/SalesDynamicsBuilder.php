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

    // Mapear items normales, filtrando los repuestos en travesía
    $normalItems = $document->items
      ->filter(function ($item) use ($sparePartsRoadCode, &$traversePartsTotal, &$hasTraverseParts) {
        if ($sparePartsRoadCode && $item->dyn_code === $sparePartsRoadCode) {
          $traversePartsTotal += (float)($item->subtotal ?? 0);
          $hasTraverseParts = true;
          return false;
        }
        return true;
      });

    $nextLine = $normalItems->max('line_number') + 1;

    $items = $normalItems->map(function ($item) use ($document) {
      return new SalesDocumentDetailDynamicsResource($item, $document, null);
    });

    // Agregar ítem de deducible si el documento tiene orden de trabajo con deducible
    if ($document->work_order_id && $document->workOrder) {
      $deductibleItem = $this->buildDeductibleLine($document, $nextLine);
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
      }
    }

    return $items;
  }

  /**
   * Construye el array de una línea de deducible para Dynamics.
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
}
