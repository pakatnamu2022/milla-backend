<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
    // Determinar el TipoId basado en el tipo de documento
    $tipoId = match ($this->document->sunat_concept_document_type_id) {
      ElectronicDocument::TYPE_FACTURA => '01',
      ElectronicDocument::TYPE_BOLETA => '03',
      ElectronicDocument::TYPE_NOTA_CREDITO => '07',
      ElectronicDocument::TYPE_NOTA_DEBITO => '08',
      default => '01',
    };

    // Generar el DocumentoId con formato: TipoId-Serie-Correlativo
    $documentoId = "{$this->document->serie}-{$this->document->numero}";

    // Obtener el código del artículo
    $articuloId = $this->codigo;

    // Descripción corta (máximo 60 caracteres)
    $descripcionCorta = substr($this->descripcion, 0, 60);

    // Descripción larga (máximo 4000 caracteres)
    $descripcionLarga = substr($this->descripcion, 0, 4000);

    // Sitio (almacén) - puede venir del contexto
    $sitioId = $this->vehicle? $this->vehicle->warehouse->

    // Unidad de medida
    $unidadMedidaId = 'UND'; // TODO: Mapear desde el item si tiene información de unidad

    // Precio unitario (puede ser precio_unitario o valor_unitario dependiendo del caso)
    $precioUnitario = $this->precio_unitario ?? 0;

    // Precio total
    $precioTotal = $this->cantidad * $precioUnitario;

    // Si es un anticipo regularizado, enviar valores en negativo para Dynamics
    if ($this->anticipo_regularizacion === true) {
      $precioUnitario = -abs($precioUnitario);
      $precioTotal = -abs($precioTotal);
    }


    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'DocumentoId' => $documentoId,
      'Linea' => $this->line_number,
      'ArticuloId' => $articuloId,
      'ArticuloDescripcionCorta' => $descripcionCorta,
      'ArticuloDescripcionLarga' => $descripcionLarga,
      'SitioId' => $sitioId,
      'UnidadMedidaId' => $unidadMedidaId,
      'Cantidad' => $this->cantidad,
      'PrecioUnitario' => $precioUnitario,
      'PrecioTotal' => $precioTotal,
    ];
  }
}
