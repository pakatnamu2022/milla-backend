<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderBillingResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $includeInvoicePreview = $this->relationLoaded('labours') && $this->relationLoaded('parts');
    $invoicePreviewData = $includeInvoicePreview ? $this->getInvoicePreview() : null;

    return [
      'id' => $this->id,
      'correlative' => $this->correlative,
      'sede_id' => $this->sede_id,
      'status_id' => $this->status_id,
      'final_amount' => (float)$this->final_amount,
      'total_labor_cost' => (float)$this->total_labor_cost,
      'total_parts_cost' => (float)$this->total_parts_cost,
      'subtotal' => (float)$this->subtotal,
      'discount_amount' => (float)$this->discount_amount,
      'tax_amount' => (float)$this->tax_amount,
      'deductible_amount_without_tax' => round((float)$this->deductible_amount_without_tax, 2),
      'is_invoiced' => (bool)$this->is_invoiced,
      'is_invalid_with_quote' => $this->orderQuotation
        ? $this->orderQuotation->details->contains('status', ApOrderQuotationDetails::STATUS_PENDING)
        : false,

      // Relaciones simplificadas
      'items' => WorkOrderItemResource::collection($this->whenLoaded('items')),

      'vehicle' => [
        'plate' => $this->vehicle?->plate,
      ],

      'type_currency' => [
        'id' => $this->typeCurrency?->id,
        'symbol' => $this->typeCurrency?->symbol,
      ],

      'invoice_to_client' => $this->invoiceTo ? [
        'id' => $this->invoiceTo->id,
        'full_name' => $this->invoiceTo->full_name,
        'num_doc' => $this->invoiceTo->num_doc,
        'tax_class_type_igv' => (float)$this->invoiceTo->taxClassType?->igv,
        'document_type_id' => $this->invoiceTo->document_type_id,
      ] : null,

      // Mano de obra
      'labours' => $this->whenLoaded('labours', function () {
        return $this->labours->map(function ($labour) {
          return [
            'id' => $labour->id,
            'description' => $labour->description,
            'hourly_rate' => $labour->hourly_rate,
            'discount_percentage' => $labour->discount_percentage,
            'time_spent_decimal' => $labour->time_spent_decimal,
          ];
        });
      }),

      // Repuestos
      'parts' => $this->whenLoaded('parts', function () {
        return $this->parts->map(function ($part) {
          return [
            'product_id' => $part->product_id,
            'product_name' => $part->product?->name,
            'product_code' => $part->product?->code,
            'unit_price' => (float)$part->unit_price,
            'discount_percentage' => (float)$part->discount_percentage,
            'quantity_used' => (float)$part->quantity_used,
          ];
        });
      }),

      // Items para facturación
      'items_invoice' => $this->when($includeInvoicePreview, fn() => $invoicePreviewData['items_invoice']),

      // Preview de factura
      'invoice_preview' => $this->when($includeInvoicePreview, fn() => $invoicePreviewData['invoice_preview']),

      // Vouchers y pagos
      'vouchers' => $this->when(
        $this->relationLoaded('advancesWorkOrder'),
        fn() => $this->getDocumentsTree()
      ),

      'payment_summary' => $this->when(
        $this->relationLoaded('advancesWorkOrder'),
        fn() => $this->getPaymentSummary()
      ),

      'has_draft_final_invoice' => $this->when(
        $this->relationLoaded('advancesWorkOrder'),
        fn() => $this->hasDraftFinalInvoice()
      ),

      'has_draft_advance' => $this->when(
        $this->relationLoaded('advancesWorkOrder'),
        fn() => $this->hasDraftAdvance()
      ),
    ];
  }
}
