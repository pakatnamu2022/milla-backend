<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApOrderQuotationDetailsResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Utils\PriceRounding;
use App\Models\ap\ApMasters;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\ApWorkOrder;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApOrderQuotationDetailsService extends BaseService implements BaseServiceInterface
{

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApOrderQuotationDetails::class,
      $request,
      ApOrderQuotationDetails::filters,
      ApOrderQuotationDetails::sorts,
      ApOrderQuotationDetailsResource::class
    );
  }

  public function find($id)
  {
    $apOrderQuotationDetails = ApOrderQuotationDetails::with([
      'orderQuotation',
      'product',
    ])->where('id', $id)->first();

    if (!$apOrderQuotationDetails) {
      throw new Exception('Detalle de cotización no encontrado');
    }

    return $apOrderQuotationDetails;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      // Validate if quotation is already associated with a work order
      $this->validateQuotationNotAssociatedWithWorkOrder($data['order_quotation_id']);

      $quotation = ApOrderQuotations::findOrFail($data['order_quotation_id']);
      $sedeId = $quotation->sede_id;
      $vehicleId = $quotation->vehicle_id;

      // Only validate price for products, not for labor
      if (isset($data['item_type']) && $data['item_type'] === 'PRODUCT' && isset($data['product_id'])) {
        // Validar decimales según la unidad de medida del producto
        $product = Products::find($data['product_id']);
        if ($product) {
          $product->validateDecimals($data['quantity']);
        }

        // Validar que el producto tenga la misma marca que el vehículo
        //Products::validateProductBrandMatchesVehicle($vehicleId, $data['product_id'], $data['description']);

        $validation = ProductWarehouseStock::validatePublicSalePrice(
          $data['product_id'],
          $sedeId,
          $data['unit_price']
        );

        if (!$validation['valid']) {
          throw new Exception(
            "Producto ({$data['description']}): {$validation['message']}"
          );
        }
      }

      // Set created_at
      if (auth()->check()) {
        $data['created_by'] = auth()->user()->id;
      }

      // total_cost/net_amount/tax_amount: misma fórmula (redondeo en cadena a 1 decimal)
      // que repuestos y mano de obra de la OT, única fuente de verdad compartida.
      $this->calculatePricesAndTotals($data);

      // Create quotation detail
      $apOrderQuotationDetails = ApOrderQuotationDetails::create($data);

      // Recalculate quotation totals using centralized method in model
      $quotation->calculateTotals();
      $quotation->save();

      // Recalculate work order totals if quotation is associated with one
      $this->recalculateWorkOrderTotals($apOrderQuotationDetails->order_quotation_id);

      return new ApOrderQuotationDetailsResource($apOrderQuotationDetails->load([
        'orderQuotation',
        'product',
      ]));
    });
  }

  public function show($id)
  {
    return new ApOrderQuotationDetailsResource($this->find($id));
  }

  /**
   * total_cost/net_amount/tax_amount: redondeo en cadena a 1 decimal (S/ 0.10) vía
   * PriceRounding, la misma fórmula usada en ApWorkOrderPartsService y
   * WorkOrderLabourService, para que un detalle de cotización facture igual sin
   * importar si termina como repuesto, mano de obra o queda pendiente en la cotización.
   */
  private function calculatePricesAndTotals(array &$data): void
  {
    $data['unit_price'] = PriceRounding::roundUnitPrice((float)$data['unit_price']);
    $quantity = (float)$data['quantity'];
    $discountPercentage = (float)($data['discount_percentage'] ?? 0);

    $totals = PriceRounding::calculateLineTotals($data['unit_price'], $quantity, $discountPercentage);
    $data['total_cost'] = $totals['total_cost'];
    $data['net_amount'] = $totals['net_amount'];
    $data['tax_amount'] = $totals['tax_amount'];
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $apOrderQuotationDetails = $this->find($data['id']);

      // Validate if quotation is already associated with a work order
      $this->validateQuotationNotAssociatedWithWorkOrder($apOrderQuotationDetails->order_quotation_id);

      // Validar decimales para productos
      if (isset($data['item_type']) && $data['item_type'] === 'PRODUCT' && isset($data['product_id'])) {
        $product = Products::find($data['product_id']);
        if ($product) {
          $product->validateDecimals($data['quantity']);
        }
      }

      // total_cost/net_amount/tax_amount: recalcular siempre con los valores finales
      // (nuevos o los ya existentes si no vinieron en este update parcial), para no
      // dividir por/multiplicar contra un unit_price o quantity ausente en el payload.
      $data['unit_price'] = $data['unit_price'] ?? $apOrderQuotationDetails->unit_price;
      $data['quantity'] = $data['quantity'] ?? $apOrderQuotationDetails->quantity;
      $data['discount_percentage'] = $data['discount_percentage'] ?? $apOrderQuotationDetails->discount_percentage;
      $this->calculatePricesAndTotals($data);

      // Update quotation detail
      $apOrderQuotationDetails->update($data);

      // Recalculate quotation totals using centralized method in model
      $quotation = ApOrderQuotations::find($apOrderQuotationDetails->order_quotation_id);
      $quotation->calculateTotals();
      $quotation->save();

      // Recalculate work order totals if quotation is associated with one
      $this->recalculateWorkOrderTotals($apOrderQuotationDetails->order_quotation_id);

      // Reload relations
      $apOrderQuotationDetails->load([
        'orderQuotation',
        'product',
      ]);

      return new ApOrderQuotationDetailsResource($apOrderQuotationDetails);
    });
  }

  public function destroy($id)
  {
    $apOrderQuotationDetails = $this->find($id);
    $quotationId = $apOrderQuotationDetails->order_quotation_id;

    // Validate if quotation is already associated with a work order
    $this->validateQuotationNotAssociatedWithWorkOrder($quotationId);

    DB::transaction(function () use ($apOrderQuotationDetails, $quotationId) {
      $apOrderQuotationDetails->delete();

      // Recalculate quotation totals using centralized method in model
      $quotation = ApOrderQuotations::find($quotationId);
      $quotation->calculateTotals();
      $quotation->save();

      // Recalculate work order totals if quotation is associated with one
      $this->recalculateWorkOrderTotals($quotationId);
    });

    return response()->json(['message' => 'Detalle de cotización eliminado correctamente.']);
  }

  /**
   * Validate if quotation is already associated with a work order
   *
   * @param int $quotationId
   * @return void
   * @throws Exception
   */
  private function validateQuotationNotAssociatedWithWorkOrder(int $quotationId): void
  {
    $workOrder = ApWorkOrder::where('order_quotation_id', $quotationId)
      ->whereHas('advancesWorkOrder')
      ->first();

    if ($workOrder) {
      throw new Exception("Esta cotización no puede ser modificada. La orden de trabajo {$workOrder->correlative} al que se encuentra asociada ya tiene avances registrados.");
    }

    $quotation = ApOrderQuotations::find($quotationId);

    if ($quotation->area_id === ApMasters::AREA_TALLER && !$quotation->is_take) {
      $quotationDate = Carbon::parse($quotation->quotation_date)->startOfDay();
      $today = Carbon::now()->startOfDay();

      // Si la cotización tiene más de DAYS_TO_EDIT_OR_DELETE días de antigüedad, no permitir edición
      if ($quotationDate->diffInDays($today) > ApOrderQuotations::DAYS_TO_EDIT_OR_DELETE) {
        throw new Exception('No se puede editar la cotización porque ya pasaron más de 15 días desde su fecha.');
      }
    }
  }

  /**
   * Recalculate work order totals if the quotation is associated with one
   *
   * @param int $quotationId
   * @return void
   */
  private function recalculateWorkOrderTotals(int $quotationId): void
  {
    // Find work order associated with this quotation
    $workOrder = ApWorkOrder::where('order_quotation_id', $quotationId)->first();

    if ($workOrder) {
      // Recalculate and save work order totals
      $workOrder->calculateTotals();
      $workOrder->save();
    }
  }
}
