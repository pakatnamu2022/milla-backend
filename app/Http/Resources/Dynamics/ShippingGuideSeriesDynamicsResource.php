<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\comercial\ShippingGuides;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingGuideSeriesDynamicsResource extends JsonResource
{
  /**
   * El documento padre (ShippingGuides)
   */
  public $shippingGuide;

  /**
   * Constructor
   */
  public function __construct(ShippingGuides $shippingGuide)
  {
    parent::__construct(null);
    $this->shippingGuide = $shippingGuide;
  }

  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    $prefix = $this->shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_VENTA
      ? 'TVEN-'
      : 'TSAL-';

    $transactionId = $prefix . str_pad($this->shippingGuide->correlative, 10, '0', STR_PAD_LEFT);
    $isCancelled = $this->shippingGuide->status === false || $this->shippingGuide->cancelled_at !== null;

    if ($isCancelled) {
      $transactionId .= '*';
    }

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'TransaccionId' => $transactionId,
      'Linea' => 1,
      'Serie' => $this->shippingGuide->vehicleMovement->vehicle->vin ?? "N/A",
      'ArticuloId' => $this->shippingGuide->vehicleMovement->vehicle->model->code ?? "N/A",
      'DatoUsuario1' => $this->shippingGuide->vehicleMovement->vehicle->vin ?? "N/A",
      'DatoUsuario2' => $this->shippingGuide->vehicleMovement->vehicle->vin ?? "N/A",
    ];
  }
}
