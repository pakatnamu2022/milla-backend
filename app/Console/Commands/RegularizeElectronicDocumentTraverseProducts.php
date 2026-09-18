<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\facturacion\ElectronicDocumentItem;
use App\Models\ap\postventa\taller\ApWorkOrderParts;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\Product;

class RegularizeElectronicDocumentTraverseProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'regularize:electronic-document-traverse-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regulariza los campos product_id, is_traverse y has_product_traverse en documentos electrónicos de postventa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando regularización de productos en travesía...');

        // Obtener documentos de postventa que tengan work_order_id o order_quotation_id
        // Solo facturas (29), boletas (30) y notas de crédito (31)
        $documents = ElectronicDocument::where(function($query) {
                $query->whereNotNull('work_order_id')
                    ->orWhereNotNull('order_quotation_id');
            })
            ->whereIn('sunat_concept_document_type_id', [
                ElectronicDocument::TYPE_FACTURA,
                ElectronicDocument::TYPE_BOLETA,
                ElectronicDocument::TYPE_NOTA_CREDITO
            ])
            ->with(['items', 'workOrder.parts.product', 'orderQuotation.details.product'])
            ->get();

        $this->info("Se encontraron {$documents->count()} documentos para procesar.");

        $processedCount = 0;
        $updatedCount = 0;

        foreach ($documents as $document) {
            $this->line("Procesando documento: {$document->serie}-{$document->correlativo}");

            $hasTraverseProduct = false;
            $itemsUpdated = 0;

            // Determinar de dónde obtener los datos
            if ($document->work_order_id && $document->workOrder) {
                // Es de orden de trabajo
                $parts = $document->workOrder->parts;

                foreach ($document->items as $item) {
                    // Buscar el part que coincida por código de producto
                    $matchingPart = $parts->first(function ($part) use ($item) {
                        return $part->product && $part->product->code === $item->codigo;
                    });

                    if ($matchingPart) {
                        $updated = $item->update([
                            'product_id' => $matchingPart->product_id,
                            'is_traverse' => $matchingPart->is_traverse ?? false,
                        ]);

                        if ($updated) {
                            $itemsUpdated++;
                        }

                        if ($matchingPart->is_traverse) {
                            $hasTraverseProduct = true;
                        }
                    }
                }
            } elseif ($document->order_quotation_id && $document->orderQuotation) {
                // Es de cotización
                $details = $document->orderQuotation->details;

                foreach ($document->items as $item) {
                    // Buscar el detail que coincida por código de producto
                    $matchingDetail = $details->first(function ($detail) use ($item) {
                        return $detail->product && $detail->product->code === $item->codigo;
                    });

                    if ($matchingDetail) {
                        $updated = $item->update([
                            'product_id' => $matchingDetail->product_id,
                            'is_traverse' => $matchingDetail->is_traverse ?? false,
                        ]);

                        if ($updated) {
                            $itemsUpdated++;
                        }

                        if ($matchingDetail->is_traverse) {
                            $hasTraverseProduct = true;
                        }
                    }
                }
            }

            // Actualizar has_product_traverse en el documento
            $document->update(['has_product_traverse' => $hasTraverseProduct]);

            if ($itemsUpdated > 0) {
                $updatedCount++;
                $this->info("  ✓ Actualizado: {$itemsUpdated} items | has_product_traverse: " . ($hasTraverseProduct ? 'true' : 'false'));
            } else {
                $this->line("  - Sin cambios");
            }

            $processedCount++;
        }

        $this->newLine();
        $this->info("Regularización completada.");
        $this->info("Documentos procesados: {$processedCount}");
        $this->info("Documentos actualizados: {$updatedCount}");

        return Command::SUCCESS;
    }
}
