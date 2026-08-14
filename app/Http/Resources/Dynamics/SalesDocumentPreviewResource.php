<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\configuracionComercial\venta\ApAccountingAccountPlan;
use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\UnitMeasurement;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\gp\gestionsistema\Company;
use App\Services\Dynamics\SalesDynamicsBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SalesDocumentPreviewResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   * Previsualiza los datos que se enviarían a Dynamics para facturas masivas Y simples
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    /** @var ElectronicDocument $document */
    $document = $this->resource;

    // Cargar relaciones necesarias según el tipo
    if ($document->consolidation_type === ElectronicDocument::CONSOLIDATION_MASSIVE) {
      $document->load([
        'client',
        'currency',
        'creator',
        'internalNotes.workOrder.parts.product.unitMeasurement',
        'internalNotes.workOrder.labours'
      ]);

      // Validación: Debe tener notas internas si es masiva
      if ($document->internalNotes->isEmpty()) {
        return [
          'error' => true,
          'message' => 'El documento masivo no tiene notas internas (electronic_document_internal_notes) asociadas',
          'document_id' => $document->id,
          'consolidation_type' => $document->consolidation_type,
        ];
      }
    } else {
      // Para simples, cargar relaciones estándar
      $document->load([
        'client',
        'currency',
        'creator',
        'items',
        'purchaseRequestQuote.accessories.approvedAccessory',
        'workOrder.deductibles.electronicDocument'
      ]);
    }

    // Construir la previsualización según el tipo
    $details = $this->buildDetailsPreview($document);

    return [
      'error' => false,
      'document_info' => [
        'id' => $document->id,
        'full_number' => $document->full_number,
        'consolidation_type' => $document->consolidation_type ?? 'simple',
        'internal_notes_count' => $document->consolidation_type === ElectronicDocument::CONSOLIDATION_MASSIVE
          ? $document->internalNotes->count()
          : null,
      ],
      'preview' => [
        'client' => $this->buildClientPreview($document),
        'header' => $this->buildHeaderPreview($document),
        'details' => $details,
      ],
      'summary' => [
        'total_items' => count($details),
        'total_work_orders' => $document->consolidation_type === ElectronicDocument::CONSOLIDATION_MASSIVE
          ? $document->internalNotes->count()
          : null,
      ],
    ];
  }

  /**
   * Construye la previsualización de los datos del cliente
   */
  private function buildClientPreview(ElectronicDocument $document): array
  {
    if (!$document->client) {
      return [
        'will_sync' => false,
        'reason' => 'Documento sin relación BusinessPartner',
        'cliente_numero_documento' => $document->cliente_numero_de_documento,
      ];
    }

    return [
      'will_sync' => true,
      'table' => 'neInTbCliente',
      'data' => [
        'id' => $document->client->id,
        'num_doc' => $document->client->num_doc,
        'full_name' => $document->client->full_name,
        'document_type_id' => $document->client->document_type_id,
        'tax_class_type_id' => $document->client->tax_class_type_id,
        'type_person_id' => $document->client->type_person_id,
        'paternal_surname' => $document->client->paternal_surname,
        'maternal_surname' => $document->client->maternal_surname,
        'first_name' => $document->client->first_name,
        'middle_name' => $document->client->middle_name,
      ],
    ];
  }

  /**
   * Construye la previsualización de la cabecera del documento
   */
  private function buildHeaderPreview(ElectronicDocument $document): array
  {
    $resource = new SalesDocumentDynamicsResource($document);

    return [
      'table' => 'neInTbVenta',
      'data' => $resource->toArray(request()),
    ];
  }

  /**
   * Construye la previsualización de los detalles (items) del documento
   * Soporta tanto consolidación massive como simple
   */
  private function buildDetailsPreview(ElectronicDocument $document): array
  {
    // Si es masiva, usar la lógica de internal notes
    if ($document->consolidation_type === ElectronicDocument::CONSOLIDATION_MASSIVE) {
      return $this->buildDetailsMassive($document);
    }

    // Si es simple, usar el SalesDynamicsBuilder
    return $this->buildDetailsSimple($document);
  }

  /**
   * Construye los detalles para facturas simples usando SalesDynamicsBuilder
   */
  private function buildDetailsSimple(ElectronicDocument $document): array
  {
    $builder = new SalesDynamicsBuilder();
    $items = [];

    // Obtener los items construidos por el builder
    $builtItems = $builder->buildItems($document);

    foreach ($builtItems as $index => $item) {
      // Convertir el Resource a array si es necesario
      $itemData = is_array($item) ? $item : $item->toArray(request());

      // Determinar el tipo de item
      $type = 'item';
      if (isset($itemData['ArticuloId'])) {
        $articleId = $itemData['ArticuloId'];
        $sparePartsRoadAccount = ApAccountingAccountPlan::find(ApAccountingAccountPlan::SPARE_PARTS_ROAD_ID);
        $deductibleAccount = ApAccountingAccountPlan::find(ApAccountingAccountPlan::CUSTOMER_DEDUCTIBLE_ID);

        if ($sparePartsRoadAccount && $articleId === $sparePartsRoadAccount->code_dynamics) {
          $type = 'traverse_parts_consolidated';
        } elseif ($deductibleAccount && $articleId === $deductibleAccount->code_dynamics) {
          $type = 'deductible';
        } elseif (strpos($itemData['ArticuloDescripcionLarga'] ?? '', 'ACCESORIO') !== false) {
          $type = 'accessory';
        }
      }

      $items[] = [
        'type' => $type,
        'table' => 'neInTbVentaDt',
        'data' => $itemData,
      ];
    }

    return $items;
  }

  /**
   * Construye los detalles para facturas masivas desde internal notes
   * Replica la lógica de syncDocumentItemsForMassiveConsolidation
   */
  private function buildDetailsMassive(ElectronicDocument $document): array
  {
    $items = [];
    $lineNumber = 1;
    $totalTraversePartsForAllOTs = 0;

    // Detectar si es nota de crédito massive
    if ($document->sunat_concept_credit_note_type_id !== null && $document->original_document_id) {
      // Obtener el documento original con sus notas internas
      $originalDocument = ElectronicDocument::with([
        'internalNotes.workOrder.parts.product.unitMeasurement',
        'internalNotes.workOrder.labours'
      ])->find($document->original_document_id);

      if ($originalDocument) {
        $internalNotes = $originalDocument->internalNotes;
      } else {
        return [
          [
            'error' => 'Documento original no encontrado',
            'original_document_id' => $document->original_document_id
          ]
        ];
      }
    } else {
      // Flujo normal: usar las notas internas del documento actual
      $internalNotes = $document->internalNotes;
    }

    // Procesar cada nota interna y su orden de trabajo
    foreach ($internalNotes as $internalNote) {
      $workOrder = $internalNote->workOrder;

      if (!$workOrder) {
        $items[] = [
          'warning' => 'Nota interna sin orden de trabajo',
          'internal_note_id' => $internalNote->id,
        ];
        continue;
      }

      // Procesar items de esta work order
      [$newLineNumber, $traverseParts, $workOrderItems] = $this->processWorkOrderItemsPreview(
        $workOrder,
        $document,
        $lineNumber
      );

      $lineNumber = $newLineNumber;
      $totalTraversePartsForAllOTs += $traverseParts;
      $items = array_merge($items, $workOrderItems);
    }

    // Agregar item consolidado de repuestos en travesía si existe
    if ($totalTraversePartsForAllOTs > 0) {
      $items[] = $this->buildTraversePartsConsolidatedItem($document, $lineNumber, $totalTraversePartsForAllOTs);
    }

    return $items;
  }

  /**
   * Procesa los items de una orden de trabajo
   * Replica la lógica de processWorkOrderItems del Job
   *
   * @return array [int $nextLineNumber, float $traversePartsTotal, array $items]
   */
  private function processWorkOrderItemsPreview(
    ApWorkOrder        $workOrder,
    ElectronicDocument $document,
    int                $lineNumber
  ): array
  {
    $items = [];
    $traversePartsTotal = 0;

    // Códigos de cuenta contable
    $labourCode = ApAccountingAccountPlan::find(ApAccountingAccountPlan::LABOUR_ACCOUNT_ID)?->code_dynamics ?? 'V0000011';
    $materialsCode = ApAccountingAccountPlan::find(ApAccountingAccountPlan::LABOUR_ACCOUNT_MATERIAL_ID)?->code_dynamics ?? 'V0000012';

    // Obtener el almacén según la sede de la orden de trabajo
    // Esto permite manejar consolidaciones masivas con órdenes de trabajo de distintas sedes
    try {
      $warehouse = $this->getWarehouseForWorkOrder($workOrder);
    } catch (\Exception $e) {
      // Si no se puede obtener el almacén de la work order, retornar error
      $items[] = [
        'error' => $e->getMessage(),
        'work_order_id' => $workOrder->id,
        'sede_id' => $workOrder->sede_id ?? null,
      ];
      return [$lineNumber, $traversePartsTotal, $items];
    }

    // ===== PROCESAR PARTS (REPUESTOS) =====
    foreach ($workOrder->parts as $part) {
      if (!$part->product) {
        $items[] = [
          'warning' => 'Part sin producto',
          'part_id' => $part->id,
        ];
        continue;
      }

      if (!$part->product->dyn_code) {
        $items[] = [
          'warning' => 'Producto sin código Dynamics',
          'product_id' => $part->product->id,
        ];
        continue;
      }

      $quantity = (float)($part->quantity_used ?? $part->assigned_quantity ?? 0);

      if ($quantity <= 0) {
        continue;
      }

      // Si es repuesto en travesía, acumular y no agregar individualmente
      if ($part->is_traverse) {
        $totalPrice = (float)($part->net_amount ?? $part->total_cost ?? 0);
        $traversePartsTotal += $totalPrice;
        continue;
      }

      // Construir item normal
      $unitPrice = (float)$part->unit_price;
      $discountPercentage = (float)($part->discount_percentage ?? 0);
      $totalPrice = (float)($part->net_amount ?? $part->total_cost ?? 0);
      $description = Str::upper($part->product->name ?? $part->product->description ?? 'PRODUCTO');
      $unitMeasurementCode = $part->product->unitMeasurement->dyn_code ?? 'UND';

      $items[] = [
        'type' => 'part',
        'table' => 'neInTbVentaDt',
        'work_order_id' => $workOrder->id,
        'part_id' => $part->id,
        'data' => [
          'EmpresaId' => Company::COMPANY_GPAUP_ID,
          'DocumentoId' => $document->full_number,
          'Linea' => $lineNumber,
          'ArticuloId' => $part->product->dyn_code,
          'ArticuloDescripcionCorta' => Str::upper(Str::limit($description, 60, '')),
          'ArticuloDescripcionLarga' => $description,
          'SitioId' => $warehouse,
          'UnidadMedidaId' => $unitMeasurementCode,
          'Cantidad' => $quantity,
          'PrecioUnitario' => $unitPrice,
          'DescuentoUnitario' => $discountPercentage,
          'PrecioTotal' => $totalPrice,
        ],
      ];

      $lineNumber++;
    }

    // ===== PROCESAR LABOURS (MANO DE OBRA) =====
    foreach ($workOrder->labours as $labour) {
      $timeSpentHours = round($labour->time_spent_decimal ?? 0, 2);

      if ($timeSpentHours <= 0) {
        continue;
      }

      $unitPrice = (float)$labour->hourly_rate;
      $discountPercentage = (float)($labour->discount_percentage ?? 0);
      $totalPrice = (float)($labour->net_amount ?? $labour->total_cost ?? 0);
      $description = Str::upper($labour->description ?? 'MANO DE OBRA');

      // Determinar código según tipo
      $descripcionNormalizada = trim(strtolower($labour->description ?? ''));
      $articuloId = ($descripcionNormalizada === 'materiales') ? $materialsCode : $labourCode;
      $unidadMedidaDyn = UnitMeasurement::find(UnitMeasurement::SERVICE_ID)?->dyn_code ?? 'UNS';

      $items[] = [
        'type' => 'labour',
        'table' => 'neInTbVentaDt',
        'work_order_id' => $workOrder->id,
        'labour_id' => $labour->id,
        'data' => [
          'EmpresaId' => Company::COMPANY_GPAUP_ID,
          'DocumentoId' => $document->full_number,
          'Linea' => $lineNumber,
          'ArticuloId' => $articuloId,
          'ArticuloDescripcionCorta' => Str::upper(Str::limit($description, 60, '')),
          'ArticuloDescripcionLarga' => $description,
          'SitioId' => $warehouse,
          'UnidadMedidaId' => $unidadMedidaDyn,
          'Cantidad' => $timeSpentHours,
          'PrecioUnitario' => $unitPrice,
          'DescuentoUnitario' => $discountPercentage,
          'PrecioTotal' => $totalPrice,
        ],
      ];

      $lineNumber++;
    }

    return [$lineNumber, $traversePartsTotal, $items];
  }

  /**
   * Construye el item consolidado de repuestos en travesía
   */
  private function buildTraversePartsConsolidatedItem(
    ElectronicDocument $document,
    int                $lineNumber,
    float              $totalAmount
  ): array
  {
    $sparePartsRoadAccount = ApAccountingAccountPlan::find(ApAccountingAccountPlan::SPARE_PARTS_ROAD_ID);

    if (!$sparePartsRoadAccount || !$sparePartsRoadAccount->code_dynamics) {
      return [
        'type' => 'traverse_parts_consolidated',
        'error' => 'No se encontró cuenta contable para repuestos en travesía',
      ];
    }

    $warehouse = $document->warehouse();
    $description = Str::upper($sparePartsRoadAccount->description ?? 'REPUESTOS EN TRAVESIA');

    return [
      'type' => 'traverse_parts_consolidated',
      'table' => 'neInTbVentaDt',
      'data' => [
        'EmpresaId' => Company::COMPANY_GPAUP_ID,
        'DocumentoId' => $document->full_number,
        'Linea' => $lineNumber,
        'ArticuloId' => $sparePartsRoadAccount->code_dynamics,
        'ArticuloDescripcionCorta' => Str::upper(Str::limit($description, 60, '')),
        'ArticuloDescripcionLarga' => $description,
        'SitioId' => $warehouse,
        'UnidadMedidaId' => 'UNS',
        'Cantidad' => 1,
        'PrecioUnitario' => $totalAmount,
        'DescuentoUnitario' => 0,
        'PrecioTotal' => $totalAmount,
      ],
    ];
  }

  /**
   * Obtiene el código de almacén (SitioId) para una orden de trabajo
   * basado en su sede_id
   *
   * @param ApWorkOrder $workOrder Orden de trabajo
   * @return string Código de almacén en Dynamics
   * @throws \Exception Si no se encuentra el almacén
   */
  private function getWarehouseForWorkOrder(ApWorkOrder $workOrder): string
  {
    // Obtener el sede_id de la orden de trabajo
    $sedeId = $workOrder->sede_id;

    if (!$sedeId) {
      Log::warning('Orden de trabajo sin sede_id para previsualización', [
        'work_order_id' => $workOrder->id
      ]);
      throw new \Exception("Orden de trabajo ID {$workOrder->id} sin sede_id definida");
    }

    // Buscar el almacén de postventa activo y recibido para esta sede
    $warehouse = Warehouse::where('sede_id', $sedeId)
      ->postSale()
      ->active()
      ->received()
      ->first();

    if (!$warehouse || !$warehouse->dyn_code) {
      Log::error('No se encontró almacén para la sede de la orden de trabajo (previsualización)', [
        'work_order_id' => $workOrder->id,
        'sede_id' => $sedeId
      ]);
      throw new \Exception("No se encontró almacén de postventa para sede_id: {$sedeId}");
    }

    return $warehouse->dyn_code;
  }
}
