<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\gestionsistema\Company;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use function round;

class SalesDocumentDetailDynamicsResource extends JsonResource
{
  /**
   * El documento padre (ElectronicDocument)
   */
  public $document;

  /**
   * Constructor
   */
  public function __construct($resource, ElectronicDocument $document)
  {
    parent::__construct($resource);
    $this->document = $document;
  }

  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    // Generar el DocumentoId con formato: TipoId-Serie-Correlativo
    $documentoId = $this->document->full_number;

    // Línea del detalle
    $linea = $this->line_number > 0 ? $this->line_number : throw new Exception('El ítem no tiene número de línea definido.');

    // Obtener el código del artículo:
    // - OT o cotización: el frontend envía `codigo` con el dyn_code del producto/repuesto (o 'DHF-00122' para mano de obra).
    // - Flujo estándar: se usa el code_dynamics de la cuenta contable.
    $hasSpecialOrigin = $this->document->order_quotation_id || $this->document->work_order_id;
    if ($hasSpecialOrigin) {
      $articuloId = $this->codigo ?? throw new Exception('El ítem no tiene código Dynamics (codigo) definido para OT/cotización.');
    } else {
      $articuloId = $this->accountPlan->code_dynamics ?? throw new Exception('El ítem no tiene una cuenta contable asociada con código Dynamics.');
    }

    // Sitio (almacén) - puede venir del contexto // TODO: Verificar almacen
    $sitioId = $this->document->warehouse() ?? throw new Exception('El documento no tiene un almacén asociado.');

    // Unidad de medida
    $unidadMedidaId = 'UND'; // TODO: Mapear desde el item si tiene información de unidad

    // Cantidad
    $cantidad = $this->cantidad > 0 ? $this->cantidad : throw new Exception('El ítem no tiene cantidad definida.');

    // Precio unitario (puede ser precio_unitario o valor_unitario dependiendo del caso)
    $precioUnitario = $this->valor_unitario > 0 ? $this->valor_unitario : throw new Exception('El ítem no tiene precio unitario definido.');

    // Descuento
    $descuentoUnitario = (float)$this->descuento;

    // Precio total
    $precioTotal = ($cantidad * $precioUnitario) - $descuentoUnitario > 0 ? round(($cantidad * $precioUnitario) - $descuentoUnitario, 2) : throw new Exception('El ítem no tiene precio total definido.');

    // Si es un anticipo regularizado, enviar valores en negativo para Dynamics
    if ($this->anticipo_regularizacion === true) {
      $precioUnitario = -abs($precioUnitario);
      $precioTotal = -abs($precioTotal);
    } else {
      $precioUnitario = abs($precioUnitario);
      $precioTotal = abs($precioTotal);
    }

    if ($this->document->vehicle) {
      if ($this->anticipo_regularizacion === true) {
        $descripcionCorta = "Regularización de Anticipo " . $documentoId;
        $descripcionLarga = "Regularización de Anticipo para el documento {$documentoId} asociado al vehículo {$this->document->vehicle->vin}";
      } else {
        $descripcionCorta = "Venta de Vehículo";
        $descripcionLarga = "Venta del vehículo con VIN {$this->document->vehicle->vin}";
      }
    } else {
      $descripcionCorta = substr($this->descripcion, 0, 100);
      $descripcionLarga = $this->descripcion;
    }

    if ($descripcionCorta === '') throw new Exception('El ítem no tiene descripción corta definida.');
    if ($descripcionLarga === '') throw new Exception('El ítem no tiene descripción larga definida.');

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'DocumentoId' => $documentoId,
      'Linea' => $linea,
      'ArticuloId' => $articuloId,
      'ArticuloDescripcionCorta' => Str::upper(Str::limit($descripcionCorta, 60, '')),
      'ArticuloDescripcionLarga' => Str::upper($descripcionLarga),
      'SitioId' => $sitioId,
      'UnidadMedidaId' => $unidadMedidaId,
      'Cantidad' => $cantidad,
      'PrecioUnitario' => $precioUnitario,
      'DescuentoUnitario' => $descuentoUnitario,
      'PrecioTotal' => $precioTotal,
    ];
  }
}
