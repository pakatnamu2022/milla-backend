<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesDocumentSerialDynamicsResource extends JsonResource
{
  /**
   * El documento padre (ElectronicDocument)
   */
  public $document;

  /**
   * El número de línea
   */
//  public $lineNumber;

  /**
   * Constructor
   */
//  public function __construct(ElectronicDocument $document, int $lineNumber)
  public function __construct(ElectronicDocument $document)
  {
    parent::__construct(null);
    $this->document = $document;
//    $this->lineNumber = $lineNumber;
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
    $documentoId = $this->document->full_number ?? throw new \Exception('El documento no tiene número completo definido.');

    $vin = $this->document->vehicle->vin ?? throw new \Exception('El documento no tiene vehículo asociado con VIN.');

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'DocumentoId' => $documentoId,
//      'Linea' => $this->lineNumber,
      'Linea' => 1,
      'Serie' => $vin,
    ];
  }
}
