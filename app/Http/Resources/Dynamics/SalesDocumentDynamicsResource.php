<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\ExchangeRate;
use App\Models\gp\gestionsistema\Company;
use Carbon\Carbon;
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
    // Determinar el TipoId basado en el tipo de documento
    $tipoId = match ($this->sunat_concept_document_type_id) {
      ElectronicDocument::TYPE_FACTURA => '01',
      ElectronicDocument::TYPE_BOLETA => '03',
      ElectronicDocument::TYPE_NOTA_CREDITO => '07',
      ElectronicDocument::TYPE_NOTA_DEBITO => '08',
      default => '01',
    };

    // Determinar el TipoComprobanteId
    $tipoComprobanteId = match ($this->sunat_concept_document_type_id) {
      ElectronicDocument::TYPE_FACTURA, ElectronicDocument::TYPE_NOTA_CREDITO, ElectronicDocument::TYPE_NOTA_DEBITO => 'FAC',
      ElectronicDocument::TYPE_BOLETA => 'BOL',
      default => 'FAC',
    };

    // Generar el DocumentoId con formato: TipoId-Serie-Correlativo
    $documentoId = "{$tipoId}-{$this->serie}-{$this->numero}";

    // Generar el LoteId basado en la fecha de emisión (formato YYMMDD + secuencia)
    $loteId = $this->fecha_de_emision->format('ymd') . str_pad($this->numero, 4, '0', STR_PAD_LEFT);

    // Obtener el tipo de tasa y tasa de cambio
    $exchangeRate = $this->exchange_rate_id
      ? ExchangeRate::find($this->exchange_rate_id)
      : null;

    $tipoTasaId = $exchangeRate?->type ?? 'COMPRA';
    $tasaCambio = $this->tipo_de_cambio ?? 1.000;

    // Obtener el cliente
    $clienteId = $this->cliente_numero_de_documento;

    // Determinar el plan de impuestos del cliente
    $planImpuestoId = $this->client?->taxClassType?->tax_class ?? 'IVAP';

    // Determinar si es anticipo
    $esAnticipo = $this->isAnticipo() ? 1 : 0;

    // Determinar si aplica anticipo (tiene items con regularización)
    // Los anticipos se manejan en el detalle con valores negativos
    $apAnticipo = $this->items->contains('anticipo_regularizacion', true) ? 1 : 0;

    // Sitio predeterminado (almacén)
    $sitioPredeterminadoId = 'ALM-VN-CIX'; // TODO: Obtener del contexto si es necesario

    // Territorio y Vendedor (pueden venir de la cotización o datos del cliente)
    $territorioId = ''; // TODO: Mapear según lógica de negocio
    $vendedorId = $this->creator?->code ?? 'USUGP';

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
      'MonedaId' => $this->currency?->code ?? 'PEN',
      'TipoTasaId' => $tipoTasaId,
      'TasaCambio' => $tasaCambio,
      'PlanImpuestoId' => $planImpuestoId,
      'TipoOperacionDetraccionId' => $this->sunat_concept_detraction_type_id
        ? str_pad($this->sunat_concept_detraction_type_id, 3, '0', STR_PAD_LEFT)
        : '',
      'CategoriaDetraccionId' => $this->detraccion ? '001' : '',
      'SitioPredeterminadoId' => $sitioPredeterminadoId,
      'UsuarioId' => 'USUGP',
      'Procesar' => 1,
      'ProcesoEstado' => 0,
      'ProcesoError' => '',
      'FechaProceso' => Carbon::now()->format('Y-m-d H:i:s'),
      'Total' => $this->total,
      'Detraccion' => $this->detraccion_total ?? 0,
      'EsAnticipo' => $esAnticipo,
      'ApAnticipo' => $apAnticipo,
    ];
  }
}
