<?php

namespace App\Http\Resources\Dynamics;

use App\Models\gp\gestionsistema\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountingEntryHeaderDynamicsResource extends JsonResource
{
  protected int $asientoNumber;

  /**
   * Constructor
   */
  public function __construct($resource, int $asientoNumber)
  {
    parent::__construct($resource);
    $this->asientoNumber = $asientoNumber;
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
      'Referencia' => $this->full_number,
      'Fecha' => $this->fecha_de_emision->format('Y-m-d H:i:s'),
      'MonedaId' => $this->currency->iso_code,
      'Error' => '',
      'Estado' => 0,
      'FechaEstado' => null,
    ];
  }
}
