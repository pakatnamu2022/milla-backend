<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ShippingGuideDetailDynamicsResource extends JsonResource
{
  /**
   * El documento padre (ShippingGuides)
   */
  public $shippingGuide;

  /**
   * Constructor
   */
  public function __construct($resource, ShippingGuides $shippingGuide)
  {
    parent::__construct($resource);
    $this->shippingGuide = $shippingGuide;
  }

  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    // Usar dyn_series si existe, sino generar uno nuevo
    if (!empty($this->shippingGuide->dyn_series)) {
      $transactionId = $this->shippingGuide->dyn_series;
    } else {
      $prefix = $this->shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_VENTA
        ? 'TVEN-'
        : 'TSAL-';

      $transactionId = $prefix . str_pad($this->shippingGuide->correlative, 8, '0', STR_PAD_LEFT);
    }

    // Agregar asterisco si está cancelada
    $isCancelled = $this->shippingGuide->status === false || $this->shippingGuide->cancelled_at !== null;
    if ($isCancelled && !str_ends_with($transactionId, '*')) {
      $transactionId .= '*';
    }

    $vehicleVn = Vehicles::findOrFail(
      $this->shippingGuide->vehicleMovement?->vehicle?->id
      ?? throw new Exception("El vehículo asociado a la guía de remisión no tiene un ID válido.")
    );

    $type_operation_id = $vehicleVn->type_operation_id ?? null;
    $class_id = $vehicleVn->model->class_id ?? null;

    // Lógica para obtener el almacén de origen (venta)
    $sede = $this->shippingGuide->sedeTransmitter ?? null;

    $baseQuery = Warehouse::where('sede_id', $sede->id)
      ->where('type_operation_id', $type_operation_id)
      ->where('article_class_id', $class_id);

    $warehouse = (clone $baseQuery)->where('is_received', true)->first();

    if (!$warehouse) {
      throw new Exception("No se encontró el almacén para la guía de remisión.");
    }

    // Si está cancelada, la cantidad es positiva (para revertir la salida)
    // Si no está cancelada, la cantidad es negativa (salida de inventario)
    $cantidad = $isCancelled ? 1 : -1;

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'TransaccionId' => $transactionId,
      'Linea' => 1,
      'ArticuloId' => $this->shippingGuide->vehicleMovement?->vehicle?->model->code ?? 'N/A',
      'Motivo' => '',
      'UnidadMedidaId' => 'UND',
      'Cantidad' => $cantidad,
      'AlmacenId' => $warehouse->dyn_code ?? '',
      'CostoUnitario' => 0,
      'CuentaInventario' => $warehouse->inventory_account . '-' . $sede->dyn_code ?? '',
      'CuentaContrapartida' => $warehouse->counterparty_account . '-' . $sede->dyn_code ?? '',
    ];
  }
}
