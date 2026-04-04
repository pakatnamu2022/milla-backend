<?php

namespace App\Services\Dynamics;

use App\Http\Resources\Dynamics\SalesDocumentDetailDynamicsResource;
use App\Http\Resources\Dynamics\SalesDocumentDynamicsResource;
use App\Http\Resources\Dynamics\SalesDocumentSerialDynamicsResource;
use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\TypeCurrency;
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
      'sale'   => new SalesDocumentDynamicsResource($document),
      'items'  => $this->buildItems($document),
      'series' => new SalesDocumentSerialDynamicsResource($document),
    ];
  }

  /**
   * Construye todas las líneas de detalle: ítems del documento + líneas de accesorios de posventa.
   */
  public function buildItems(ElectronicDocument $document): Collection
  {
    $postSaleAccessories = $this->getPostSaleAccessories($document);
    $igvDivisor = $this->getIgvDivisor($document);
    $nextLine = $document->items->max('line_number') + 1;

    $items = $document->items->map(function ($item) use ($document, $postSaleAccessories, $igvDivisor) {
      $overridePrice = $this->resolveVehicleBasePrice($item, $postSaleAccessories, $document, $igvDivisor);
      return new SalesDocumentDetailDynamicsResource($item, $document, $overridePrice);
    });

    foreach ($postSaleAccessories as $accessory) {
      $items->push($this->buildAccessoryLine($accessory, $document, $document->full_number, $nextLine++, $igvDivisor));
    }

    return $items;
  }

  /**
   * Retorna los accesorios de posventa del documento (solo si no es anticipo).
   */
  public function getPostSaleAccessories(ElectronicDocument $document): Collection
  {
    if ($document->is_advance_payment) {
      return collect();
    }

    return $document->purchaseRequestQuote?->accessories
      ->filter(fn($a) =>
        $a->type === 'ACCESORIO_ADICIONAL' &&
        $a->approvedAccessory?->type_operation_id === ApMasters::TIPO_OPERACION_POSTVENTA
      ) ?? collect();
  }

  /**
   * Devuelve el divisor IGV (ej. 1.18 para 18%).
   */
  public function getIgvDivisor(ElectronicDocument $document): float
  {
    return 1 + ($document->porcentaje_de_igv / 100);
  }

  /**
   * Devuelve el precio unitario base del vehículo sin IGV, descontando el total de accesorios,
   * o null si no aplica override (anticipo o sin accesorios).
   */
  public function resolveVehicleBasePrice($item, Collection $postSaleAccessories, ElectronicDocument $document, float $igvDivisor): ?float
  {
    if ($postSaleAccessories->isEmpty() || $item->anticipo_regularizacion) {
      return null;
    }

    $totalAccessoriesWithIgv = $postSaleAccessories->sum(
      fn($a) => $this->accessoryUnitGrossInDocCurrency($a, $document) * $a->quantity
    );

    return round(
      ((float)$document->total - $totalAccessoriesWithIgv) / $igvDivisor,
      2
    );
  }

  /**
   * Devuelve el precio unitario bruto (con IGV) del accesorio convertido a la moneda del documento.
   */
  public function accessoryUnitGrossInDocCurrency($accessory, ElectronicDocument $document): float
  {
    $gross = (float)($accessory->price + $accessory->additional_price);
    $docCurrency = $document->currency->iso_code;

    if ($accessory->type_currency_id === TypeCurrency::PEN_ID && $docCurrency === TypeCurrency::USD) {
      return $gross / (float)$document->tipo_de_cambio;
    }

    if ($accessory->type_currency_id === TypeCurrency::USD_ID && $docCurrency === TypeCurrency::PEN) {
      return $gross * (float)$document->tipo_de_cambio;
    }

    return $gross;
  }

  /**
   * Construye el array de una línea de accesorio de posventa para Dynamics.
   */
  public function buildAccessoryLine($accessory, ElectronicDocument $document, string $documentoId, int $linea, float $igvDivisor): array
  {
    $unitPricePreTax = round($this->accessoryUnitGrossInDocCurrency($accessory, $document) / $igvDivisor, 2);
    $description = Str::upper($accessory->approvedAccessory->description);

    return [
      'EmpresaId'               => Company::AP_DYNAMICS,
      'DocumentoId'             => $documentoId,
      'Linea'                   => $linea,
      'ArticuloId'              => $accessory->approvedAccessory->code_dynamics
        ?? throw new Exception("El accesorio '{$accessory->approvedAccessory->code}' no tiene código Dynamics (code_dynamics) definido."),
      'ArticuloDescripcionCorta' => Str::upper(Str::limit($description, 60, '')),
      'ArticuloDescripcionLarga' => $description,
      'SitioId'                 => $document->warehouse()
        ?? throw new Exception('El documento no tiene almacén asociado.'),
      'UnidadMedidaId'          => 'UND',
      'Cantidad'                => $accessory->quantity,
      'PrecioUnitario'          => $unitPricePreTax,
      'DescuentoUnitario'       => 0,
      'PrecioTotal'             => round($accessory->quantity * $unitPricePreTax, 2),
    ];
  }
}
