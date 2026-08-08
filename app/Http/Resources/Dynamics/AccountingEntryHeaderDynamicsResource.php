<?php

namespace App\Http\Resources\Dynamics;

use App\Models\gp\gestionsistema\Company;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountingEntryHeaderDynamicsResource extends JsonResource
{
  protected int $asientoNumber;
  protected $date;

  /**
   * Constructor
   */
  public function __construct($resource, $date, int $asientoNumber)
  {
    parent::__construct($resource);
    $this->asientoNumber = $asientoNumber;
    $this->date = $date;
  }

  /**
   * Transform the resource into an array.
   */
  public function toArray(Request $request): array
  {
    return [
      'Asiento' => $this->asientoNumber,
      'EmpresaId' => Company::AP_DYNAMICS, // 'CTEST'
      'LoteId' => $this->creator->person->vat,
      'Referencia' => self::buildReferencia($this->full_number, $this->vehicle->vin),
      'Fecha' => $this->date->format('Y-m-d H:i:s'),
      'MonedaId' => $this->currency->iso_code,
      'TipoTasaId' => 'NEGOCIAR',
      'TipoCambio' => (float) ($this->tipo_de_cambio ?? throw new Exception('Tipo de cambio no existe en la factura ' . $this->full_number)),
      'Error' => '',
      'Estado' => 0,
      'FechaEstado' => null,
    ];
  }

  /**
   * Construye la Referencia para Dynamics: quita ceros del correlativo, agrega VIN y trunca a 30 chars.
   * Ejemplo: BXX1-00181911 + LS5A3ABE9VD910530 → "BXX1-181911|LS5A3ABE9VD910530"
   */
  public static function buildReferencia(string $fullNumber, string $vin): string
  {
    $parts = explode('-', $fullNumber, 2);
    $serie = $parts[0];
    $correlativo = ltrim($parts[1] ?? '', '0') ?: '0';
    return substr("{$serie}-{$correlativo}|{$vin}", 0, 30);
  }
}
