<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesDocumentAdvanceDynamicsResource extends JsonResource
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
    $documentoId = "{$tipoId}-{$this->document->serie}-{$this->document->numero}";

    // Generar el Anticipo_DocumentoId con formato similar
    $anticipoDocumentoId = "01-{$this->anticipo_documento_serie}-{$this->anticipo_documento_numero}";

    // Calcular el monto aplicado (puede venir del item)
    $montoAplicado = abs($this->precio_unitario * $this->cantidad);

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'DocumentoId' => $documentoId,
      'Anticipo_DocumentoId' => $anticipoDocumentoId,
      'MontoAplicado' => $montoAplicado,
    ];
  }
}
