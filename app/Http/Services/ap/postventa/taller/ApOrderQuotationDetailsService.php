<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApOrderQuotationDetailsResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Utils\Constants;
use App\Http\Utils\PriceRounding;
use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
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

      // Only validate price for products, not for labor
      if (isset($data['item_type']) && $data['item_type'] === 'PRODUCT' && isset($data['product_id'])) {
        // Validar que el producto no esté ya agregado como otro detalle de esta cotización
        $this->validateProductNotAlreadyInQuotation($data['order_quotation_id'], $data['product_id']);

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
          $data['unit_price'],
          $quotation->currency_id,
          $quotation->exchange_rate
        );

        if (!$validation['valid']) {
          throw new Exception(
            "Producto ({$data['description']}): {$validation['message']}"
          );
        }
      }

      // Si es MANO DE OBRA y la cotización está en dólares, convertir el precio de soles a dólares
      if (isset($data['item_type']) && $data['item_type'] === 'LABOR') {
        if ($quotation->currency_id === TypeCurrency::USD_ID) {
          $data['unit_price'] = $data['unit_price'] / $quotation->exchange_rate;
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
    $quantity = (float)$data['quantity'];
    $discountPercentage = (float)($data['discount_percentage'] ?? 0);

    $result = PriceRounding::calculateLine((float)$data['unit_price'], $quantity, $discountPercentage);
    $data['unit_price'] = $result['unit_price'];
    $data['total_cost'] = $result['total_cost'];
    $data['net_amount'] = $result['net_amount'];
    $data['tax_amount'] = $result['tax_amount'];
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $apOrderQuotationDetails = $this->find($data['id']);

      // Validate if quotation is already associated with a work order
      $this->validateQuotationNotAssociatedWithWorkOrder($apOrderQuotationDetails->order_quotation_id);

      $quotation = ApOrderQuotations::findOrFail($apOrderQuotationDetails->order_quotation_id);

      // total_cost/net_amount/tax_amount: recalcular siempre con los valores finales
      // (nuevos o los ya existentes si no vinieron en este update parcial), para no
      // dividir por/multiplicar contra un unit_price o quantity ausente en el payload.
      $data['unit_price'] = $data['unit_price'] ?? $apOrderQuotationDetails->unit_price;
      $data['quantity'] = $data['quantity'] ?? $apOrderQuotationDetails->quantity;
      $data['discount_percentage'] = $data['discount_percentage'] ?? $apOrderQuotationDetails->discount_percentage;

      $itemType = $data['item_type'] ?? $apOrderQuotationDetails->item_type;
      $productId = $data['product_id'] ?? $apOrderQuotationDetails->product_id;

      // Only validate price for products, not for labor
      if ($itemType === 'PRODUCT' && $productId) {
        // Validar que el producto no esté ya agregado como otro detalle de esta cotización
        $this->validateProductNotAlreadyInQuotation(
          $apOrderQuotationDetails->order_quotation_id,
          $productId,
          $apOrderQuotationDetails->id
        );

        // Validar decimales según la unidad de medida del producto
        $product = Products::find($productId);
        if ($product) {
          $product->validateDecimals($data['quantity']);
        }

        // Validar que el precio unitario respete el precio mínimo de venta al público
        $validation = ProductWarehouseStock::validatePublicSalePrice(
          $productId,
          $quotation->sede_id,
          $data['unit_price'],
          $quotation->currency_id,
          $quotation->exchange_rate
        );

        if (!$validation['valid']) {
          $description = $data['description'] ?? $apOrderQuotationDetails->description;
          throw new Exception(
            "Producto ({$description}): {$validation['message']}"
          );
        }
      }

      $this->calculatePricesAndTotals($data);

      // Update quotation detail
      $apOrderQuotationDetails->update($data);

      // Recalculate quotation totals using centralized method in model
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

    // Validar restricciones de antigüedad (15 días)
    $this->validateQuotationAge($quotationId);

    // Validar que el monto proyectado no sea menor al pagado en anticipos si hay OT asociada
    $this->validateMinimumAmountIfWorkOrderHasAdvances($quotationId, $apOrderQuotationDetails);

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
   * Valida que no se repita el mismo product_id entre los detalles de tipo PRODUCT
   * de un mismo payload (creación/actualización en bloque de una cotización, vía
   * ApOrderQuotationsService::storeWithProducts/updateWithProducts).
   *
   * @param array $details
   * @return void
   * @throws Exception
   */
  public function validateNoDuplicateProductsInDetails(array $details): void
  {
    $seenProductIds = [];

    foreach ($details as $detail) {
      if (($detail['item_type'] ?? 'PRODUCT') !== 'PRODUCT' || !isset($detail['product_id'])) {
        continue;
      }

      $productId = $detail['product_id'];

      if (isset($seenProductIds[$productId])) {
        throw new Exception(
          "Producto ({$detail['description']}): ya fue agregado en esta cotización, no se puede repetir el mismo producto."
        );
      }

      $seenProductIds[$productId] = true;
    }
  }

  /**
   * Valida que el producto no exista ya como otro detalle PRODUCT dentro de la misma
   * cotización, usado por store/update individual (ApOrderQuotationDetailsService).
   *
   * @param int $quotationId
   * @param int $productId
   * @param int|null $excludeDetailId Id del detalle actual, para excluirlo en update
   * @return void
   * @throws Exception
   */
  private function validateProductNotAlreadyInQuotation(int $quotationId, int $productId, ?int $excludeDetailId = null): void
  {
    $query = ApOrderQuotationDetails::where('order_quotation_id', $quotationId)
      ->where('item_type', 'PRODUCT')
      ->where('product_id', $productId);

    if ($excludeDetailId) {
      $query->where('id', '!=', $excludeDetailId);
    }

    if ($query->exists()) {
      throw new Exception('Este producto ya se encuentra agregado en esta cotización, no se puede repetir el mismo producto.');
    }
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

  /**
   * Valida restricciones de antigüedad de la cotización (15 días)
   *
   * @param int $quotationId
   * @return void
   * @throws Exception
   */
  private function validateQuotationAge(int $quotationId): void
  {
    $quotation = ApOrderQuotations::find($quotationId);

    if ($quotation->area_id === ApMasters::AREA_TALLER && !$quotation->is_take) {
      $quotationDate = Carbon::parse($quotation->quotation_date)->startOfDay();
      $today = Carbon::now()->startOfDay();

      // Si la cotización tiene más de DAYS_TO_EDIT_OR_DELETE días de antigüedad, no permitir edición
      if ($quotationDate->diffInDays($today) > ApOrderQuotations::DAYS_TO_EDIT_OR_DELETE) {
        throw new Exception('No se puede eliminar el detalle porque la cotización tiene más de 15 días desde su fecha.');
      }
    }
  }

  /**
   * Valida que el monto proyectado de la OT no sea menor al pagado en anticipos
   * Similar a la lógica de ApWorkOrderPartsService::destroy
   *
   * @param int $quotationId
   * @param ApOrderQuotationDetails $quotationDetail
   * @return void
   * @throws Exception
   */
  private function validateMinimumAmountIfWorkOrderHasAdvances(int $quotationId, ApOrderQuotationDetails $quotationDetail): void
  {
    // Buscar si hay una OT asociada con anticipos
    $workOrder = ApWorkOrder::where('order_quotation_id', $quotationId)
      ->whereHas('advancesWorkOrder')
      ->first();

    if ($workOrder) {
      // Validar que el nuevo monto no sea menor al monto pagado en anticipos
      $workOrder->refresh();
      $currentTotals = $workOrder->getTotalsArray();

      // Calcular el monto del detalle de cotización con IGV incluido
      // (usar la misma lógica que ApWorkOrder::getTotalsArray)
      $itemNetAmount = $quotationDetail->net_amount;
      $itemWithTax = $itemNetAmount * (1 + Constants::VAT_TAX / 100);

      // Proyectar el nuevo total (total_amount incluye IGV)
      $projectedFinalAmount = $currentTotals['total_amount'] - $itemWithTax;

      // Validar usando el método del modelo ApWorkOrder
      $workOrder->validateMinimumAmount($projectedFinalAmount);
    }
  }
}
