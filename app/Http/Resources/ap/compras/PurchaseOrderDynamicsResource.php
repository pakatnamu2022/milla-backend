<?php

namespace App\Http\Resources\ap\compras;

use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\gp\gestionsistema\Company;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderDynamicsResource extends JsonResource
{
  /**
   * @throws Exception
   */
  public function toArray(Request $request): array
  {
    $supplierNumber = BusinessPartners::find($this->supplier_id)->num_doc;
    $supplierTaxClassType = PurchaseOrder::find($this->id)->supplier?->supplierTaxClassType?->tax_class;
    $typeCurrency = TypeCurrency::find($this->currency_id)->code;
    $exchangeRate = PurchaseOrder::find($this->id)->exchangeRate;

    if (!$supplierNumber) throw new Exception("Supplier not found for PO {$this->id}");
    if (!$supplierTaxClassType) throw new Exception("Supplier or TaxClassType not found for PO {$this->id}");
    if (!$typeCurrency) throw new Exception("Currency not found for PO {$this->id}");
    if (!$exchangeRate) throw new Exception("Exchange Rate not found for PO {$this->id}");

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'OrdenCompraId' => $this->number,
      'ProveedorId' => $supplierNumber,
      'FechaEmision' => $this->emission_date?->format('Y-m-d'),
      'MonedaId' => $typeCurrency,
      'TipoTasaId' => $exchangeRate->type,
      'TasaCambio' => $exchangeRate->rate,
      'PlanImpuestoId' => $supplierTaxClassType,
      'UsuarioId' => 'USUGP',
      'Procesar' => 1,
      'ProcesoEstado' => 0,
      'ProcesoError' => '',
    ];
  }
}
