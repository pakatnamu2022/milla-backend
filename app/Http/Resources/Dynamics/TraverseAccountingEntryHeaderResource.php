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
   * Fecha del comprobante
   */
  public string $fecha;

  /**
   * DNI del creador de la transacción
   */
  public ?string $creatorVat;

  /**
   * Constructor
   */
  public function __construct($resource, int $asientoNumber, bool $isReversal = false, string $fecha = '', ?string $creatorVat = null)
  {
    parent::__construct($resource);
    $this->asientoNumber = $asientoNumber;
    $this->isReversal = $isReversal;
    $this->fecha = $fecha;
    $this->creatorVat = $creatorVat;
  }

  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    /** @var ElectronicDocument $this */

    // Formato: {serie}-{numero} | {fecha} o REVER {serie}-{numero} | {fecha}
    // Límite: 30 caracteres
    $docNumber = "{$this->serie}-{$this->numero}";

    if ($this->isReversal) {
      $referencia = "REVER {$docNumber} | {$this->fecha}";
    } else {
      $referencia = "{$docNumber} | {$this->fecha}";
    }

    // Truncar si excede 30 caracteres
    if (strlen($referencia) > 30) {
      $referencia = substr($referencia, 0, 30);
    }

    // Obtener el DNI del creador de la transacción para LoteId
    $loteId = $this->creatorVat ?? 'SYSTEM';

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