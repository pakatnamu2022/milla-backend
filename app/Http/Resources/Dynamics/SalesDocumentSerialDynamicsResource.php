<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Exception;

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
    // Generar el DocumentoId con formato: TipoId-Serie-Correlativo
    $documentoId = $this->document->full_number ?? throw new Exception('El documento no tiene número completo definido.');

    $vin = $this->document->area_id == ApMasters::AREA_COMERCIAL &&
    $this->document->purchaseRequestQuote->has_vehicle ?
      ($this->document->vehicle->vin ?? throw new Exception('El documento no tiene vehículo asociado con VIN.'))
      : 'SERIE-DEFAULT';

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'DocumentoId' => $documentoId,
      // TODO: SOLO SE MANDA UNA LÍNEA POR AHORA
//      'Linea' => $this->lineNumber,
      'Linea' => 1,
      'Serie' => $vin,
    ];
  }
}
