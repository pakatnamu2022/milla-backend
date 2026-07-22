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
   * Precio unitario sin IGV a usar en lugar del valor_unitario del item (opcional)
   */
  public ?float $overrideValorUnitario;

  /**
   * Constructor
   */
  public function __construct($resource, ElectronicDocument $document, ?float $overrideValorUnitario = null)
  {
    parent::__construct($resource);
    $this->document = $document;
    $this->overrideValorUnitario = $overrideValorUnitario;
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
    // - Anticipo: siempre usar la cuenta contable (code_dynamics).
    // - OT o cotización: verificar por item si es regularización de anticipo o no.
    // - Flujo estándar: se usa el code_dynamics de la cuenta contable.
    $hasSpecialOrigin = $this->document->order_quotation_id || $this->document->work_order_id;
    if ($this->document->is_advance_payment == 1) {
      // Si es un anticipo, usar siempre la cuenta contable
      $articuloId = $this->accountPlan->code_dynamics ?? throw new Exception('El ítem de anticipo no tiene una cuenta contable asociada con código Dynamics.');
    } else if ($hasSpecialOrigin) {
      // Si tiene origen especial (OT o cotización), verificar por item:
      // - Si anticipo_regularizacion == 1: usar code_dynamics (cuenta contable)
      // - Si anticipo_regularizacion == 0: usar dyn_code (código del artículo)
      if ($this->anticipo_regularizacion === true) {
        $articuloId = $this->accountPlan->code_dynamics ?? throw new Exception('El ítem de regularización de anticipo no tiene una cuenta contable asociada con código Dynamics.');
      } else {
        $articuloId = $this->dyn_code ?? throw new Exception('El ítem no tiene código Dynamics (dyn_code) definido para OT/cotización.');
      }
    } else {
      $articuloId = $this->accountPlan->code_dynamics ?? throw new Exception('El ítem no tiene una cuenta contable asociada con código Dynamics.');
    }

    // Sitio (almacén) - puede venir del contexto // TODO: Verificar almacen
    $sitioId = $this->document->warehouse() ?? throw new Exception('El documento no tiene un almacén asociado.');

    // Unidad de medida - Si el item tiene unidad de medida (repuesto/producto), usarla; sino usar 'UND' por defecto
    $unidadMedidaId = !empty($this->unidad_medida_dyn) ? $this->unidad_medida_dyn : 'UND';

    // Cantidad
    $cantidad = $this->cantidad > 0 ? $this->cantidad : throw new Exception('El ítem no tiene cantidad definida.');

    // Precio unitario neto sin IGV (después de descuento) — o valor override para vehículos con accesorios
    $valorUnitario = $this->overrideValorUnitario ?? (float)$this->valor_unitario;

    // Descuento por unidad sin IGV pre-calculado. Con override se fuerza a cero.
    $descuentoUnitario = $this->overrideValorUnitario !== null ? 0.0 : (float)($this->descuento_unitario ?? 0);

    // Precio unitario bruto sin IGV (antes de descuento): Dynamics aplica el descuento internamente
    //   PrecioUnitario - DescuentoUnitario = subtotal / cantidad
    $precioUnitario = round($valorUnitario, 2);
    //$precioUnitario = $precioUnitario > 0 ? $precioUnitario : throw new Exception('El ítem no tiene precio unitario definido.');

    // Precio total neto: subtotal almacenado, o cantidad × override cuando aplica precio alternativo
    $precioTotalNeto = $this->overrideValorUnitario !== null
      ? $valorUnitario * $cantidad
      : (float)$this->subtotal;
    $precioTotal = $precioTotalNeto > 0 ? round($precioTotalNeto, 2) : throw new Exception('El ítem no tiene precio total definido.');

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
