<?php

namespace App\Http\Controllers\ap\facturacion;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dynamics\AccountingEntryHeaderDynamicsResource;
use App\Http\Services\ap\facturacion\AccountingEntryService;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingEntryController extends Controller
{
  protected AccountingEntryService $accountingService;

  public function __construct(AccountingEntryService $accountingService)
  {
    $this->accountingService = $accountingService;
  }

  /**
   * Preview del resource de asiento contable antes de sincronizar
   *
   * @param int $shippingGuideId
   * @return JsonResponse
   */
  public function preview(int $shippingGuideId): JsonResponse
  {
    try {
      // 1. Cargar ShippingGuide
      $shippingGuide = ShippingGuides::with([
        'vehicleMovement.vehicle.model.classArticle',
        'vehicleMovement',
      ])->find($shippingGuideId);

      if (!$shippingGuide) {
        return response()->json([
          'error' => 'ShippingGuide no encontrada',
          'shipping_guide_id' => $shippingGuideId
        ], 404);
      }

      // 2. Validar que sea guía de VENTA
      if ($shippingGuide->transfer_reason_id !== SunatConcepts::TRANSFER_REASON_VENTA) {
        return response()->json([
          'error' => 'La guía no es de tipo VENTA',
          'shipping_guide_id' => $shippingGuide->id,
          'transfer_reason_id' => $shippingGuide->transfer_reason_id
        ], 400);
      }

      // 3. Obtener ElectronicDocument
      $electronicDocument = ElectronicDocument::with([
        'items',
        'creator.person',
        'currency',
        'seriesModel.sede',
        'vehicleMovement.vehicle.model.classArticle',
        'vehicle',
      ])
        ->whereHas('vehicle', function ($query) use ($shippingGuide) {
          $query->where('vin', $shippingGuide->vehicleMovement->vehicle->vin);
        })
        ->first();

      if (!$electronicDocument) {
        return response()->json([
          'error' => 'No se encontró factura asociada a la guía',
          'shipping_guide_id' => $shippingGuide->id,
          'vehicle_movement_id' => $shippingGuide->vehicle_movement_id
        ], 404);
      }

      // 4. Validar que tenga items
      if ($electronicDocument->items->count() === 0) {
        return response()->json([
          'error' => 'La factura no tiene items',
          'electronic_document_id' => $electronicDocument->id
        ], 400);
      }

      // 5. Generar número de asiento (solo para preview, no se guarda)
      $asientoNumber = $this->accountingService->getNextAsientoNumber();

      // 6. Generar resource de cabecera
      $headerResource = new AccountingEntryHeaderDynamicsResource($electronicDocument, $asientoNumber);
      $headerData = $headerResource->toArray(request());

      // 7. Generar líneas de detalle
      $detailLines = $this->accountingService->generateAccountingLines($electronicDocument, $asientoNumber);

      // 8. Validar balance
      $this->accountingService->validateBalance($detailLines);

      // 9. Calcular totales
      $totalDebito = array_sum(array_column($detailLines, 'Debito'));
      $totalCredito = array_sum(array_column($detailLines, 'Credito'));

      return response()->json([
        'success' => true,
        'shipping_guide' => [
          'id' => $shippingGuide->id,
          'series' => $shippingGuide->series,
          'document_number' => $shippingGuide->document_number,
          'issue_date' => $shippingGuide->issue_date,
          'transfer_reason_id' => $shippingGuide->transfer_reason_id,
        ],
        'electronic_document' => [
          'id' => $electronicDocument->id,
          'full_number' => $electronicDocument->full_number,
          'fecha_emision' => $electronicDocument->fecha_de_emision,
          'total' => $electronicDocument->total,
          'items_count' => $electronicDocument->items->count(),
        ],
        'accounting_entry' => [
          'header' => $headerData,
          'details' => $detailLines,
          'summary' => [
            'total_lines' => count($detailLines),
            'total_debito' => round($totalDebito, 2),
            'total_credito' => round($totalCredito, 2),
            'balance' => round($totalDebito - $totalCredito, 2),
            'is_balanced' => abs($totalDebito - $totalCredito) <= 0.01,
          ]
        ],
        'message' => 'Preview generado exitosamente. Este asiento NO ha sido guardado.'
      ], 200);

    } catch (\Exception $e) {
      return response()->json([
        'error' => 'Error generando preview',
        'message' => $e->getMessage(),
        'trace' => config('app.debug') ? $e->getTraceAsString() : null
      ], 500);
    }
  }

  /**
   * Lista los mapeos de cuentas contables por clase de artículo
   */
  public function accountMappings(): JsonResponse
  {
    try {
      $mappings = \App\Models\ap\configuracionComercial\vehiculo\ApClassArticleAccountMapping::with('classArticle')
        ->where('status', true)
        ->get()
        ->groupBy('ap_class_article_id')
        ->map(function ($items) {
          $classArticle = $items->first()->classArticle;
          return [
            'class_article' => [
              'id' => $classArticle->id,
              'dyn_code' => $classArticle->dyn_code,
              'description' => $classArticle->description,
            ],
            'mappings' => $items->map(function ($mapping) {
              return [
                'account_type' => $mapping->account_type,
                'account_origin' => $mapping->account_origin,
                'account_destination' => $mapping->account_destination,
                'is_debit_origin' => $mapping->is_debit_origin,
                'example_full_origin' => $mapping->getFullAccountOrigin('01'),
                'example_full_destination' => $mapping->getFullAccountDestination('01'),
              ];
            })->values()
          ];
        });

      return response()->json([
        'success' => true,
        'mappings' => $mappings->values(),
        'total' => $mappings->count()
      ], 200);

    } catch (\Exception $e) {
      return response()->json([
        'error' => 'Error obteniendo mapeos',
        'message' => $e->getMessage()
      ], 500);
    }
  }
}
