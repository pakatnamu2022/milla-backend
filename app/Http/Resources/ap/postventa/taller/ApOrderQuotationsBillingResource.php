<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApOrderQuotationsBillingResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $includeInvoicePreview = $this->relationLoaded('details');
    $invoicePreviewData = $includeInvoicePreview ? $this->getInvoicePreview() : null;

    return [
      'id' => $this->id,
      'sede_id' => $this->sede_id,
      'quotation_number' => $this->quotation_number,
      'subtotal' => (float)$this->subtotal,
      'discount_amount' => (float)$this->discount_amount,
      'tax_amount' => (float)$this->tax_amount,
      'total_amount' => (float)$this->total_amount,
      'deductible_amount_without_tax' => round((float)$this->deductible_amount_without_tax, 2),
      'notes' => $this->notes,
      'is_fully_paid' => (bool)$this->is_fully_paid,

      // Relaciones simplificadas
      'vehicle' => [
        'plate' => $this->vehicle?->plate,
        'owner' => [
          'tax_class_type_igv' => $this->vehicle?->customer?->tax_class_type_igv,
        ],
      ],

      'type_currency' => [
        'id' => $this->typeCurrency?->id,
        'symbol' => $this->typeCurrency?->symbol,
      ],

      'can_generate_final_receipt' => $this->when(
        $this->relationLoaded('details'),
        fn() => $this->canGenerateFinalReceipt()
      ),

      'invoice_to_client' => $this->invoiceTo ? [
        'id' => $this->invoiceTo->id,
        'full_name' => $this->invoiceTo->full_name,
        'num_doc' => $this->invoiceTo->num_doc,
        'tax_class_type_igv' => (float)$this->invoiceTo->taxClassType?->igv,
        'document_type_id' => $this->invoiceTo->document_type_id,
      ] : null,

      // Detalles de cotización
      'details' => $this->whenLoaded('details', function () {
        return $this->details->map(function ($detail) {
          return [
            'id' => $detail->id,
            'description' => $detail->description,
            'quantity' => $detail->quantity,
            'unit_price' => $detail->unit_price,
            'net_amount' => $detail->net_amount,
            'product_id' => $detail->product_id,
            'product' => $detail->product ? [
              'id' => $detail->product->id,
              'code' => $detail->product->code,
            ] : null,
          ];
        });
      }),

      // Items para facturación
      'items_invoice' => $this->when($includeInvoicePreview, fn() => $invoicePreviewData['items_invoice']),

      // Preview de factura
      'invoice_preview' => $this->when($includeInvoicePreview, fn() => $invoicePreviewData['invoice_preview']),

      // Vouchers y pagos
      'vouchers' => $this->when(
        $this->relationLoaded('advancesOrderQuotation'),
        fn() => $this->getDocumentsTree()
      ),

      'payment_summary' => $this->when(
        $this->relationLoaded('advancesOrderQuotation'),
        fn() => $this->getPaymentSummary()
      ),

      'has_draft_final_invoice' => $this->when(
        $this->relationLoaded('advancesOrderQuotation'),
        fn() => $this->hasDraftFinalInvoice()
      ),

      'has_draft_advance' => $this->when(
        $this->relationLoaded('advancesOrderQuotation'),
        fn() => $this->hasDraftAdvance()
      ),
    ];
  }
}
