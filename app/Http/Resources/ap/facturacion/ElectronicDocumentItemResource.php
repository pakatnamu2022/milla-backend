<?php

namespace App\Http\Resources\ap\facturacion;

use App\Http\Resources\gp\maestroGeneral\SunatConceptsResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ElectronicDocumentItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ap_billing_electronic_document_id' => $this->ap_billing_electronic_document_id,
            'unidad_de_medida' => $this->unidad_de_medida,
            'codigo' => $this->codigo,
            'codigo_producto_sunat' => $this->codigo_producto_sunat,
            'descripcion' => $this->descripcion,
            'cantidad' => $this->cantidad,
            'valor_unitario' => $this->valor_unitario,
            'precio_unitario' => $this->precio_unitario,
            'descuento' => $this->descuento,
            'subtotal' => $this->subtotal,
            'sunat_concept_igv_type_id' => $this->sunat_concept_igv_type_id,
            'igv_type' => new SunatConceptsResource($this->whenLoaded('igvType')),
            'igv' => $this->igv,
            'total' => $this->total,
            'anticipo_regularizacion' => $this->anticipo_regularizacion,
            'anticipo_documento_serie' => $this->anticipo_documento_serie,
            'anticipo_documento_numero' => $this->anticipo_documento_numero,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
