<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\ExchangeRate;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesDocumentDynamicsResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    // Determinar el TipoComprobanteId
    $tipoComprobanteId = match ($this->sunat_concept_document_type_id) {
      ElectronicDocument::TYPE_FACTURA, ElectronicDocument::TYPE_NOTA_CREDITO, ElectronicDocument::TYPE_NOTA_DEBITO => 'FAC',
      ElectronicDocument::TYPE_BOLETA => 'BOL',
      default => 'FAC',
    };

    $tipoId = $this->serie;

    // Generar el DocumentoId con formato: TipoId-Serie-Correlativo
    $documentoId = "{$this->full_number}";

    // Generar el LoteId (usando el VAT del usuario autenticado) o de TODO:quien creo el documento
    $loteId = $this->creator->person->vat;

    // Obtener el cliente
    $clienteId = $this->cliente_numero_de_documento;

    // Obtener el tipo de tasa y tasa de cambio
    $exchangeRate = $this->exchange_rate_id
      ? ExchangeRate::find($this->exchange_rate_id)
      : null;

    $tipoTasaId = $exchangeRate?->type ?? throw new Exception('El documento no tiene una tasa de cambio asociada.');
    $tasaCambio = $this->tipo_de_cambio ?? 1.000;


    // Determinar el plan de impuestos del cliente
    $planImpuestoId = $this->client?->taxClassType?->tax_class ?? 'IVAP';

    // Determinar si es anticipo
    $esAnticipo = $this->is_advance_payment;

    // Determinar si aplica anticipo (tiene items con regularización)
    // Los anticipos se manejan en el detalle con valores negativos
    $apAnticipo = $this->is_advance_payment;

    // Sitio predeterminado (almacén)
    $sitioPredeterminadoId = 'ALM-VN-CIX'; // TODO: Obtener del contexto si es necesario

    // Territorio y Vendedor (pueden venir de la cotización o datos del cliente)
    $territorioId = ''; // TODO: Mapear según lógica de negocio
    $vendedorId = ''; // TODO: Mapear según lógica de negocio

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'TipoId' => $tipoId,
      'DocumentoId' => $documentoId,
      'LoteId' => $loteId,
      'ClienteId' => $clienteId,
      'TerritorioId' => $territorioId,
      'VendedorId' => $vendedorId,
      'FechaEmision' => $this->fecha_de_emision->format('Y-m-d H:i:s'),
      'FechaContable' => $this->fecha_de_emision->format('Y-m-d H:i:s'),
      'TipoComprobanteId' => $tipoComprobanteId,
      'Serie' => $this->serie,
      'Correlativo' => $this->numero,
      'MonedaId' => $this->currency?->iso_code ?? throw new Exception('El documento no tiene una moneda asociada.'),
      'TipoTasaId' => $tipoTasaId,
      'TasaCambio' => $tasaCambio,
      'PlanImpuestoId' => $planImpuestoId,
//      TODO: PREGUNTAR BIEN
      'TipoOperacionDetraccionId' => $this->sunat_concept_detraction_type_id
        ? str_pad($this->sunat_concept_detraction_type_id, 2, '0', STR_PAD_LEFT)
        : '01',
      'CategoriaDetraccionId' => $this->detraccion ? '001' : '',
      'SitioPredeterminadoId' => $sitioPredeterminadoId,
      'UsuarioId' => 'USUGP',
      'Procesar' => 1,
      'ProcesoEstado' => 0, // TODO: es 0
      'ProcesoError' => '',
      'FechaProceso' => '',
      'Total' => (float)$this->total ?? throw new Exception('El documento no tiene total definido.'),
      'Detraccion' => $this->detraccion_total ?? 0,
      'EsAnticipo' => $esAnticipo,
      'ApAnticipo' => $apAnticipo,
    ];
  }
}
