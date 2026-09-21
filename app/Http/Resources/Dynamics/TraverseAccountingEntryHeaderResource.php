<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TraverseAccountingEntryHeaderResource extends JsonResource
{
  /**
   * Número de asiento
   */
  public int $asientoNumber;

  /**
   * Indica si es una reversión
   */
  public bool $isReversal;

  /**
   * Constructor
   */
  public function __construct($resource, int $asientoNumber, bool $isReversal = false)
  {
    parent::__construct($resource);
    $this->asientoNumber = $asientoNumber;
    $this->isReversal = $isReversal;
  }

  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    /** @var ElectronicDocument $this */

    // NOTA: Este resource ahora retorna UN ARRAY DE HEADERS, uno por cada fecha de compra única
    // Formato: TRV-{serie}-{numero} o TRV-{serie}-{numero}-REV
    $referencia = "TRV-{$this->serie}-{$this->numero}";
    if ($this->isReversal) {
      $referencia .= '-REV';
    }

    // Obtener el DNI del creador del documento para LoteId
    $loteId = $this->creator?->person?->vat ?? 'SYSTEM';

    // Obtener el código de moneda
    $monedaId = $this->currency?->iso_code ?? 'PEN';

    // Obtener el tipo de tasa desde exchangeRate
    $tipoTasaId = $this->exchangeRate?->type ?? 'VENDER';

    // Obtener el tipo de cambio
    $tipoCambio = (float)($this->tipo_de_cambio ?? 1.000);

    // Retornamos el header base SIN la fecha específica
    // La fecha se asignará en el controlador según la purchase_date
    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'Asiento' => $this->asientoNumber,
      'LoteId' => $loteId,
      'Referencia' => $referencia,
      // 'Fecha' se asignará dinámicamente por fecha de compra
      'MonedaId' => $monedaId,
      'TipoTasaId' => $tipoTasaId,
      'TipoCambio' => $tipoCambio,
      'Error' => '',
      'Estado' => 0,
      'FechaEstado' => now()->format('Y-m-d H:i:s'),
    ];
  }
}