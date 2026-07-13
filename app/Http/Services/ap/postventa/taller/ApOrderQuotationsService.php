<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApOrderQuotationsResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\EmailService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Http\Utils\Constants;
use App\Http\Utils\Helpers;
use App\Http\Utils\PriceRounding;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\DiscountRequestsOrderQuotation;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\gp\gestionsistema\Position;
use App\Models\gp\maestroGeneral\ExchangeRate;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApOrderQuotationsService extends BaseService implements BaseServiceInterface
{
  protected DigitalFileService $digitalFileService;
  protected EmailService $emailService;
  protected ApOrderQuotationDetailsService $quotationDetailsService;

  // Configuración de rutas para archivos
  private const FILE_PATHS = [
    'customer_signature' => '/ap/postventa/taller/cotizaciones/firmas-cliente/',
    'customer_signature_delivery' => '/ap/postventa/taller/cotizaciones/firmas-entrega/',
  ];

  public function __construct(
    DigitalFileService             $digitalFileService,
    EmailService                   $emailService,
    ApOrderQuotationDetailsService $quotationDetailsService
  )
  {
    $this->digitalFileService = $digitalFileService;
    $this->emailService = $emailService;
    $this->quotationDetailsService = $quotationDetailsService;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApOrderQuotations::class,
      $request,
      ApOrderQuotations::filters,
      ApOrderQuotations::sorts,
      ApOrderQuotationsResource::class
    );
  }

  public function listForPurchaseRequestTaller(Request $request): JsonResponse
  {
    // Query base con las condiciones requeridas para solicitudes de compra
    // Filtrar siempre por área taller
    $query = ApOrderQuotations::query()
      ->where('area_id', ApMasters::AREA_TALLER)
      ->where(function ($query) {
        $query->where(function ($q) {
          // Condición 1: Cotización aprobada por jefe O gerente
          $q->whereNotNull('chief_approval_by')
            ->orWhereNotNull('manager_approval_by');
        })
          ->orWhereHas('workOrders', function ($q) {
            // Condición 2: Cotización asociada a OT con factura generada Y anticipo contabilizado
            $q->where('has_invoice_generated', true)
              ->whereHas('advancesWorkOrder', function ($q2) {
                $q2->where('is_advance_payment', 1)
                  ->where('is_accounted', true);
              });
          });
      })
      // Condición 3: La cotización debe tener al menos 1 item con supply_type LOCAL, CENTRAL o IMPORTACION
      ->whereHas('details', function ($q) {
        $q->whereIn('supply_type', ['LOCAL', 'CENTRAL', 'IMPORTACION']);
      });


    return $this->getFilteredResults(
      $query,
      $request,
      ApOrderQuotations::filters,
      ApOrderQuotations::sorts,
      ApOrderQuotationsResource::class
    );
  }

  public function listForPurchaseRequestMeson(Request $request): JsonResponse
  {
    // Query base con las condiciones requeridas para solicitudes de compra
    // Envolver en un where para agrupar correctamente las condiciones con los filtros posteriores
    $query = ApOrderQuotations::query()
      ->where(function ($query) {
        // Condiciones base: área mesón, no tomada y estado por facturar
        $query->where('area_id', ApMasters::AREA_MESON)
          ->where('is_take', false)
          ->where('status', ApOrderQuotations::STATUS_POR_FACTURAR)
          ->where(function ($q) {
            // Al menos una de estas condiciones debe cumplirse:

            // Opción 1: Tiene factura generada Y tiene anticipo contabilizado
            $q->where(function ($q1) {
              $q1->where('has_invoice_generated', true)
                ->whereHas('advancesOrderQuotation', function ($q2) {
                  $q2->where('is_advance_payment', 1)
                    ->where('is_accounted', true);
                });
            })
              // Opción 2: Aprobada por jefe
              ->orWhereNotNull('chief_approval_by')
              // Opción 3: Aprobada por gerente
              ->orWhereNotNull('manager_approval_by')
              // Opción 4: Tiene avance de pago contabilizado (sin factura generada aún)
              ->orWhereHas('advancesOrderQuotation', function ($q2) {
                $q2->where('is_advance_payment', 1)
                  ->where('is_accounted', true);
              });
          });
      })
      // Condición: La cotización debe tener al menos 1 item con supply_type LOCAL, CENTRAL o IMPORTACION
      ->whereHas('details', function ($q) {
        $q->whereIn('supply_type', ['LOCAL', 'CENTRAL', 'IMPORTACION']);
      });

    return $this->getFilteredResults(
      $query,
      $request,
      ApOrderQuotations::filters,
      ApOrderQuotations::sorts,
      ApOrderQuotationsResource::class
    );
  }

  public function find($id)
  {
    $quotation = ApOrderQuotations::with([
      'shippingGuide',
      'invoiceTo',
      'vehicle',
      'createdBy',
      'details'
    ])->where('id', $id)->first();

    if (!$quotation) {
      throw new Exception('Cotización no encontrada');
    }

    return $quotation;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $vehicle = Vehicles::find($data['vehicle_id']);
      $date = Carbon::parse($data['quotation_date'])->format('Y-m-d');

      // Solo validar y guardar el tipo de cambio si la moneda es USD
      if (isset($data['currency_id']) && $data['currency_id'] == TypeCurrency::USD_ID) {
        // Obtener el tipo de cambio óptimo entre la fecha de cotización y la fecha actual
        $optimalExchangeRate = ExchangeRate::getOptimalExchangeRate(
          $date,
          TypeCurrency::PEN_ID,
          TypeCurrency::USD_ID,
          ExchangeRate::TYPE_VENTA
        );

        if (!$optimalExchangeRate) {
          throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de la cotización ni para la fecha actual.');
        }

        $data['exchange_rate_id'] = $optimalExchangeRate->id;
        $data['exchange_rate'] = $optimalExchangeRate->rate;
      } else {
        // Si es PEN u otra moneda, el tipo de cambio es null
        $data['exchange_rate_id'] = null;
        $data['exchange_rate'] = null;
      }

//      if ($vehicle->customer_id === null) {
//        throw new Exception('El vehículo debe estar asociado a un "TITULAR" para crear una cotización');
//      }

      if (auth()->check()) {
        $data['created_by'] = auth()->user()->id;
      }

      $data['quotation_number'] = ApOrderQuotations::generateNextQuotationNumber($data['sede_id']);
      $data['subtotal'] = 0;
      $data['discount_amount'] = 0;
      $data['tax_amount'] = 0;
      $data['total_amount'] = 0;

      // Calculate validity days
      $quotation_date = Carbon::parse($data['quotation_date']);
      $expiration_date = Carbon::parse($data['expiration_date']);
      $validation_days = $quotation_date->diffInDays($expiration_date);
      $data['validity_days'] = $validation_days;

      // Si el usuario no envía mileage o envía 0, obtener el kilometraje de su última inspección vehicular
      if (!isset($data['mileage']) || empty($data['mileage'])) {
        $data['mileage'] = ApWorkOrder::where('vehicle_id', $data['vehicle_id'])
          ->orderBy('created_at', 'desc')
          ->first()?->vehicleInspection?->mileage ?? 0;
      }

      $quotation = ApOrderQuotations::create($data);

      // Actualizar el kilometraje del vehículo si el nuevo kilometraje es mayor
      if (isset($data['mileage']) && $data['mileage'] > 0 && $vehicle->mileage < $data['mileage']) {
        $vehicle->update(['mileage' => $data['mileage']]);
      }

      return new ApOrderQuotationsResource($quotation->load([
        'vehicle',
        'createdBy',
        'details'
      ]));
    });
  }

  public function storeWithProducts(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $date = Carbon::parse($data['quotation_date'])->format('Y-m-d');

      // Solo obtener y validar el tipo de cambio si la moneda es USD
      $exchangeRate = null;
      if (isset($data['currency_id']) && $data['currency_id'] == TypeCurrency::USD_ID) {
        // Obtener el tipo de cambio óptimo entre la fecha de cotización y la fecha actual
        $exchangeRate = ExchangeRate::getOptimalExchangeRate(
          $date,
          TypeCurrency::PEN_ID,
          TypeCurrency::USD_ID,
          ExchangeRate::TYPE_VENTA
        );

        if (!$exchangeRate) {
          throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de la cotización ni para la fecha actual.');
        }
      }

      if (auth()->check()) {
        $data['created_by'] = auth()->user()->id;
      }

      // Validar que no se repita el mismo producto dentro del payload de details
      $this->quotationDetailsService->validateNoDuplicateProductsInDetails($data['details']);

      // Validar precios de venta al público, decimales y marca para cada producto en details
      foreach ($data['details'] as $index => $detail) {
        $productId = $detail['product_id'];
        $unitPrice = $detail['unit_price'];
        $quantity = $detail['quantity'];
        $sedeId = $data['sede_id'];

        // Validar decimales según la unidad de medida del producto
        $product = Products::find($productId);
        if ($product) {
          $product->validateDecimals($quantity);
        }

        // Validar precio de venta al público (solo si hay tipo de cambio, es decir, si es USD)
        $validation = ProductWarehouseStock::validatePublicSalePrice(
          $productId,
          $sedeId,
          $unitPrice,
          $data['currency_id'],
          $exchangeRate ? $exchangeRate->rate : null
        );

        if (!$validation['valid']) {
          throw new Exception(
            "Producto ({$detail['description']}): {$validation['message']}"
          );
        }
      }

      // Validar stock en sistema externo solo para productos con supply_type = 'STOCK'
      $warehouseId = Warehouse::getPhysicalWarehouseForPostsale($data['sede_id'])?->id;
      if (!$warehouseId) {
        throw new Exception('No se encontró un almacén físico asociado a esta sede para postventa. No se puede validar el stock de los productos.');
      }

      $inventoryMovementService = app(InventoryMovementService::class);

      foreach ($data['details'] as $index => $detail) {
        $supplyType = $detail['supply_type'] ?? null;

        // Solo validar stock externo si es tipo STOCK
        if ($supplyType === 'STOCK') {
          $productId = $detail['product_id'];
          $quantity = $detail['quantity'];

          // Obtener el stock del producto en el almacén
          $stock = ProductWarehouseStock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

          if (!$stock) {
            throw new Exception(
              "Producto ({$detail['description']}): No se encontró registro de stock en el almacén seleccionado"
            );
          }

          // Validar stock en sistema externo
          $externalStock = $inventoryMovementService->validateStockInExternalSystem(
            $stock->product->dyn_code,
            $stock->warehouse->dyn_code
          );

          // El SP retorna ArticuloStock como string, convertir a float para comparar
          $availableQuantityExternal = isset($externalStock['ArticuloStock'])
            ? (float)trim($externalStock['ArticuloStock'])
            : 0;

          if ($availableQuantityExternal < $quantity) {
            throw new Exception(
              "Producto ({$detail['description']}): Stock insuficiente en sistema Dynamics. " .
              "Stock disponible en Dynamics: {$availableQuantityExternal}, Cantidad requerida: {$quantity}"
            );
          }
        }
      }

      // Calculate validity days
      $quotation_date = Carbon::parse($data['quotation_date']);
      $expiration_date = Carbon::parse($data['expiration_date']);
      $validation_days = $quotation_date->diffInDays($expiration_date);

      // Prepare quotation data (initialize totals as 0, will be calculated after creating details)
      $quotationData = [
        'area_id' => $data['area_id'],
        'vehicle_id' => $data['vehicle_id'] ?? null,
        'client_id' => $data['client_id'],
        'sede_id' => $data['sede_id'],
        'quotation_date' => $data['quotation_date'],
        'expiration_date' => $data['expiration_date'],
        'observations' => $data['observations'] ?? null,
        'created_by' => $data['created_by'],
        'quotation_number' => ApOrderQuotations::generateNextQuotationNumber($data['sede_id']),
        'subtotal' => 0,
        'discount_percentage' => 0,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 0,
        'validity_days' => $validation_days,
        'exchange_rate_id' => $exchangeRate ? $exchangeRate->id : null,
        'exchange_rate' => $exchangeRate ? $exchangeRate->rate : null,
        'currency_id' => $data['currency_id'],
        'collection_date' => $data['collection_date'] ?? null,
      ];

      // Create quotation
      $quotation = ApOrderQuotations::create($quotationData);

      // Create details
      foreach ($data['details'] as $detail) {
        // unit_price + total_cost/net_amount/tax_amount: única fuente de verdad
        // compartida con ApOrderQuotationDetailsService.
        $discountPercentage = $detail['discount_percentage'] ?? 0;
        $result = PriceRounding::calculateLine((float)$detail['unit_price'], (float)$detail['quantity'], (float)$discountPercentage);

        $quotation->details()->create([
          'item_type' => 'PRODUCT',
          'product_id' => $detail['product_id'],
          'description' => $detail['description'],
          'quantity' => $detail['quantity'],
          'unit_measure' => $detail['unit_measure'],
          'unit_price' => $result['unit_price'],
          'discount_percentage' => $discountPercentage,
          'total_cost' => $result['total_cost'],
          'net_amount' => $result['net_amount'],
          'tax_amount' => $result['tax_amount'],
          'observations' => $detail['observations'] ?? null,
          'retail_price_external' => $detail['retail_price_external'] ?? null,
          'exchange_rate' => $detail['exchange_rate'] ?? null,
          'freight_commission' => $detail['freight_commission'] ?? null,
          'supply_type' => $detail['supply_type'] ?? null,
        ]);
      }

      // Recalculate totals using centralized method in model
      $quotation->calculateTotals();
      $quotation->save();

      return new ApOrderQuotationsResource($quotation->load([
        'vehicle',
        'createdBy',
        'details.product'
      ]));
    });
  }

  public function show($id)
  {
    $quotation = $this->find($id);
    $quotation->load('advancesOrderQuotation');

    $additionalData = [
      'checkStock' => true,
      'includeConfirmationData' => true  // Flag para mostrar datos de confirmación
    ];

    // Incluir cost_man_hours solo si es área de taller
    if ($quotation->area_id === ApMasters::AREA_TALLER) {
      $additionalData['includeCostManHours'] = true;
    }

    return (new ApOrderQuotationsResource($quotation))->additional($additionalData);
  }


  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $quotation = $this->find($data['id']);
      $vehicle = Vehicles::find($data['vehicle_id']);
      $date = Carbon::parse($data['quotation_date'])->format('Y-m-d');

      // Detectar si cambió el tipo de moneda
      $oldCurrencyId = $quotation->currency_id;
      $newCurrencyId = $data['currency_id'] ?? $oldCurrencyId;
      $currencyChanged = $oldCurrencyId !== null && $newCurrencyId !== null && $oldCurrencyId != $newCurrencyId;

      // Solo validar y guardar el tipo de cambio si la moneda es USD
      if (isset($data['currency_id']) && $data['currency_id'] == TypeCurrency::USD_ID) {
        // Obtener el tipo de cambio óptimo entre la fecha de cotización y la fecha actual
        $optimalExchangeRate = ExchangeRate::getOptimalExchangeRate(
          $date,
          TypeCurrency::PEN_ID,
          TypeCurrency::USD_ID,
          ExchangeRate::TYPE_VENTA
        );

        if (!$optimalExchangeRate) {
          throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de la cotización ni para la fecha actual.');
        }

        $data['exchange_rate'] = $optimalExchangeRate->rate;
        $data['exchange_rate_id'] = $optimalExchangeRate->id;
      } else {
        // Si es PEN u otra moneda, el tipo de cambio es null
        $data['exchange_rate'] = null;
        $data['exchange_rate_id'] = null;
      }

      if ($vehicle->customer_id === null) {
        throw new Exception('El vehículo debe estar asociado a un "TITULAR" para crear una cotización');
      }

      if ($quotation->is_take === true) {
        throw new Exception('No se puede actualizar una cotización que ya ha sido tomada en una solicitud de compra / OT.');
      }

      if ($quotation->area_id === ApMasters::AREA_TALLER) {
        if ($quotation->quotation_date) {
          $quotationDate = Carbon::parse($quotation->quotation_date)->startOfDay();
          $today = Carbon::now()->startOfDay();

          // Si la cotización tiene más de DAYS_TO_EDIT_OR_DELETE días de antigüedad, no permitir edición
          if ($quotationDate->diffInDays($today) > ApOrderQuotations::DAYS_TO_EDIT_OR_DELETE) {
            throw new Exception('No se puede editar la cotización porque ya pasaron más de 15 días desde su fecha.');
          }
        }
      }

      // Calculate validity days
      $quotation_date = Carbon::parse($data['quotation_date']);
      $expiration_date = Carbon::parse($data['expiration_date']);
      $validation_days = $quotation_date->diffInDays($expiration_date);
      $data['validity_days'] = $validation_days;

      // Si el usuario no envía mileage o envía 0, obtener el kilometraje de su última inspección vehicular
      if (!isset($data['mileage']) || empty($data['mileage'])) {
        $data['mileage'] = ApWorkOrder::where('vehicle_id', $data['vehicle_id'])
          ->orderBy('created_at', 'desc')
          ->first()?->vehicleInspection?->mileage ?? 0;
      }

      $quotation->update($data);

      // Si cambió el tipo de moneda, recalcular los detalles con el nuevo tipo de cambio
      if ($currencyChanged) {
        $this->handleCurrencyChange($quotation, $oldCurrencyId, $newCurrencyId);

        // Recalcular totales de cabecera (subtotal/tax_amount/total_amount) a partir
        // de los details ya recalculados, igual que en changeCurrency().
        $quotation->load('details');
        $quotation->calculateTotals();
        $quotation->save();
      }

      // Actualizar el kilometraje del vehículo si el nuevo kilometraje es mayor
      if (isset($data['mileage']) && $data['mileage'] > 0 && $vehicle->mileage < $data['mileage']) {
        $vehicle->update(['mileage' => $data['mileage']]);
      }

      $quotation->load([
        'vehicle',
        'createdBy',
        'details'
      ]);

      return new ApOrderQuotationsResource($quotation);
    });
  }

  public function updateWithProducts(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $quotation = $this->find($data['id']);
      $vehicleId = $data['vehicle_id'] ?? null;
      $date = Carbon::parse($data['quotation_date'])->format('Y-m-d');

      // Detectar si cambió el tipo de moneda
      $oldCurrencyId = $quotation->currency_id;
      $newCurrencyId = $data['currency_id'] ?? $oldCurrencyId;
      $currencyChanged = $oldCurrencyId !== null && $newCurrencyId !== null && $oldCurrencyId != $newCurrencyId;

      // Solo obtener y validar el tipo de cambio si la moneda es USD
      $exchangeRate = null;
      if (isset($data['currency_id']) && $data['currency_id'] == TypeCurrency::USD_ID) {
        // Obtener el tipo de cambio óptimo entre la fecha de cotización y la fecha actual
        $exchangeRate = ExchangeRate::getOptimalExchangeRate(
          $date,
          TypeCurrency::PEN_ID,
          TypeCurrency::USD_ID,
          ExchangeRate::TYPE_VENTA,
        );

        if (!$exchangeRate) {
          throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de la cotización ni para la fecha actual.');
        }
      }

      if ($quotation->status === ApOrderQuotations::STATUS_DESCARTADO) {
        throw new Exception('No se puede actualizar una cotización que ha sido descartada.');
      }

      if ($quotation->status === ApOrderQuotations::STATUS_SEGMENTADA) {
        throw new Exception('No se puede actualizar una cotización que ha sido segmentada.');
      }

      if ($quotation->status !== ApOrderQuotations::STATUS_APERTURADO) {
        throw new Exception('Solo se pueden editar cotizaciones en estado "Aperturado".');
      }

      if ($quotation->segmentedQuotations()->count() > 0) {
        throw new Exception('Esta cotización no se puede editar porque ha sido segmentada en otras cotizaciones.');
      }

      if ($quotation->getActiveAdvances()->count() > 0) {
        throw new Exception('No se puede editar una cotización que tiene anticipos registrados');
      }

      if ($quotation->discountRequests->where('status', DiscountRequestsOrderQuotation::STATUS_APPROVED)->isNotEmpty()) {
        throw new Exception('No se puede editar una cotización que tiene solicitudes de descuento aprobadas por gerencia');
      }

      // Validar cambio de moneda si existen pagos registrados
      if ($quotation->getActiveAdvances()->count() > 0 && $quotation->currency_id !== $data['currency_id']) {
        throw new Exception('No se puede cambiar el tipo de moneda porque ya existen pagos registrados para esta cotización.');
      }

      // Validar que no se repita el mismo producto dentro del payload de details
      $this->quotationDetailsService->validateNoDuplicateProductsInDetails($data['details']);

      // Validar precios de venta al público, decimales y marca para cada producto en details
      foreach ($data['details'] as $index => $detail) {
        $productId = $detail['product_id'];
        $unitPrice = $detail['unit_price'];
        $quantity = $detail['quantity'];
        $sedeId = $data['sede_id'];

        // Validar decimales según la unidad de medida del producto
        $product = Products::find($productId);
        if ($product) {
          $product->validateDecimals($quantity);
        }

        // Validar precio de venta al público
        $validation = ProductWarehouseStock::validatePublicSalePrice(
          $productId,
          $sedeId,
          $unitPrice,
          $data['currency_id'],
          $exchangeRate ? $exchangeRate->rate : null
        );

        if (!$validation['valid']) {
          throw new Exception(
            "Producto #{$productId} ({$detail['description']}): {$validation['message']}"
          );
        }
      }

      // Calculate validity days
      $quotation_date = Carbon::parse($data['quotation_date']);
      $expiration_date = Carbon::parse($data['expiration_date']);
      $validation_days = $quotation_date->diffInDays($expiration_date);

      // Update quotation data (totals will be recalculated after updating details)
      $quotation->update([
        'area_id' => $data['area_id'],
        'vehicle_id' => $vehicleId,
        'client_id' => $data['client_id'],
        'sede_id' => $data['sede_id'],
        'quotation_date' => $data['quotation_date'],
        'expiration_date' => $data['expiration_date'],
        'observations' => $data['observations'] ?? null,
        'validity_days' => $validation_days,
        'currency_id' => $data['currency_id'],
        'exchange_rate_id' => $exchangeRate ? $exchangeRate->id : null,
        'exchange_rate' => $exchangeRate ? $exchangeRate->rate : null,
        'collection_date' => $data['collection_date'] ?? null,
      ]);

      // Obtener almacén para liberar stock
      $warehouseId = Warehouse::getPhysicalWarehouseForPostsale($data['sede_id'])?->id;

      // Capturar los detalles originales (moneda anterior) antes de eliminarlos.
      // Se usan como base real para la reconversión de moneda: el unit_price que
      // manda el frontend en 'details' es solo una previsualización (ya dividida/
      // multiplicada por el tipo de cambio del cliente), y el backend recién aquí
      // determina el tipo de cambio definitivo. Si se reconvirtiera ese valor de
      // previsualización, el precio quedaría dividido dos veces.
      $oldDetailsByProduct = $quotation->details->keyBy('product_id');

      // Delete existing details
      $quotation->details()->delete();

      // Si cambió la moneda, calcular el factor de conversión para los precios
      $conversionFactor = 1;
      if ($currencyChanged) {
        // Refresh para obtener el exchange_rate actualizado
        $quotation->refresh();
        $conversionFactor = $this->getConversionFactor($quotation, $oldCurrencyId, $newCurrencyId);
      }

      // Create new details
      foreach ($data['details'] as $detail) {
        $unitPrice = (float)$detail['unit_price'];

        // Si cambió la moneda, ignorar el unit_price de previsualización que envía
        // el frontend y reconvertir desde el precio original (moneda anterior) de
        // ese mismo producto, aplicando el factor una única vez.
        if ($currencyChanged) {
          $oldDetail = $oldDetailsByProduct->get($detail['product_id']);
          $unitPrice = $oldDetail
            ? (float)$oldDetail->unit_price * $conversionFactor
            : $unitPrice;
        }

        // unit_price + total_cost/net_amount/tax_amount: única fuente de verdad
        // compartida con ApOrderQuotationDetailsService.
        $discountPercentage = $detail['discount_percentage'] ?? $detail['discount'] ?? 0;
        $result = PriceRounding::calculateLine($unitPrice, (float)$detail['quantity'], (float)$discountPercentage);

        $quotation->details()->create([
          'item_type' => 'PRODUCT',
          'product_id' => $detail['product_id'],
          'description' => $detail['description'],
          'quantity' => $detail['quantity'],
          'unit_measure' => $detail['unit_measure'],
          'unit_price' => $result['unit_price'],
          'discount_percentage' => $discountPercentage,
          'total_cost' => $result['total_cost'],
          'net_amount' => $result['net_amount'],
          'tax_amount' => $result['tax_amount'],
          'observations' => $detail['observations'] ?? null,
          'retail_price_external' => $detail['retail_price_external'] ?? null,
          'exchange_rate' => $detail['exchange_rate'] ?? null,
          'freight_commission' => $detail['freight_commission'] ?? null,
          'supply_type' => $detail['supply_type'] ?? null,
        ]);
      }

      // Reload details relation to ensure fresh data for calculations
      $quotation->load('details');

      // Recalculate totals using centralized method in model
      $quotation->calculateTotals();
      $quotation->save();

      return new ApOrderQuotationsResource($quotation->load([
        'vehicle',
        'createdBy',
        'details.product'
      ]));
    });
  }

  public function destroy($id)
  {
    $quotation = $this->find($id);

    if ($quotation->status !== ApOrderQuotations::STATUS_APERTURADO) {
      throw new Exception('Solo se pueden eliminar cotizaciones en estado "Aperturado".');
    }

    if ($quotation->getActiveAdvances()->count() > 0) {
      throw new Exception('No se puede editar una cotización que tiene anticipos registrados');
    }

    if ($quotation->status === ApOrderQuotations::STATUS_DESCARTADO) {
      throw new Exception('No se puede eliminar una cotización que ha sido descartada.');
    }

    if ($quotation->is_take === true) {
      throw new Exception('No se puede eliminar una cotización que ya ha sido tomada en una solicitud de compra / OT.');
    }

    if ($quotation->segmentedQuotations()->count() > 0) {
      throw new Exception('Esta cotización no se puede eliminar porque ha sido segmentada en otras cotizaciones.');
    }

    if ($quotation->area_id === ApMasters::AREA_TALLER) {
      if ($quotation->quotation_date) {
        $quotationDate = Carbon::parse($quotation->quotation_date)->startOfDay();
        $today = Carbon::now()->startOfDay();

        // Si la cotización tiene más de DAYS_TO_EDIT_OR_DELETE días de antigüedad, no permitir edición
        if ($quotationDate->diffInDays($today) > ApOrderQuotations::DAYS_TO_EDIT_OR_DELETE) {
          throw new Exception('No se puede editar la cotización porque ya pasaron más de 15 días desde su fecha.');
        }
      }
    }

    DB::transaction(function () use ($quotation) {
      $parentQuotation = $quotation->parent_quotation_id
        ? ApOrderQuotations::find($quotation->parent_quotation_id)
        : null;

      $quotation->delete();

      // Si esta era la última cotización segmentada, reabrir el padre
      if ($parentQuotation && $parentQuotation->segmentedQuotations()->count() === 0) {
        $parentQuotation->update(['status' => ApOrderQuotations::STATUS_APERTURADO]);
      }
    });

    return response()->json(['message' => 'Cotización eliminada correctamente']);
  }

  public function discard(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $quotation = $this->find($data['id']);

      if ($quotation->getActiveAdvances()->count() > 0) {
        throw new Exception('No se puede anular una cotización que tiene anticipos registrados');
      }

      if ($quotation->discarded_at) {
        throw new Exception('Esta cotización ya ha sido descartada previamente.');
      }

      // Obtener almacén para liberar stock
      $warehouseId = Warehouse::getPhysicalWarehouseForPostsale($quotation->sede_id)?->id;

      // Liberar stock de los detalles que son tipo STOCK antes de descartar
      foreach ($quotation->details as $detail) {
        if ($detail->supply_type === 'STOCK' && $detail->product_id && $warehouseId) {
          $stock = ProductWarehouseStock::where('product_id', $detail->product_id)
            ->where('warehouse_id', $warehouseId)
            ->first();

          if ($stock) {
            $stock->releaseReservedStock($detail->quantity);
          }
        }
      }

      // Preparar los datos de descarte
      $discardData = [
        'discard_reason_id' => $data['discard_reason_id'],
        'discarded_note' => $data['discarded_note'] ?? null,
        'discarded_by' => auth()->check() ? auth()->user()->id : null,
        'discarded_at' => Carbon::now(),
      ];

      // Si se proporciona un status, actualizarlo
      if (isset($data['status'])) {
        $discardData['status'] = $data['status'];
      } else {
        // Establecer un status por defecto si no se proporciona
        $discardData['status'] = ApOrderQuotations::STATUS_DESCARTADO;
      }

      $quotation->update($discardData);

      $quotation->load([
        'vehicle',
        'createdBy',
        'details',
        'discardReason',
        'discardedBy'
      ]);

      return new ApOrderQuotationsResource($quotation);
    });
  }

  public function generateQuotationPDF($id, $showCodes = true)
  {
    $quotation = ApOrderQuotations::with([
      'vehicle.model.family.brand',
      'vehicle.color',
      'vehicle.customer.district',
      'createdBy',
      'details.product',
      'typeCurrency'
    ])->find($id);

    if (!$quotation) {
      throw new Exception('Cotización no encontrada');
    }

    // Preparar datos para la vista
    $data = [
      'quotation_number' => $quotation->quotation_number,
      'quotation_date' => $quotation->quotation_date,
      'expiration_date' => $quotation->expiration_date,
      'observations' => $quotation->observations ?? '',
      'validity_days' => $quotation->validity_days,
      'show_codes' => $showCodes,
      'sede' => $quotation->sede,
    ];

    // Datos del cliente
    if ($quotation->client) {
      $customer = $quotation->client;
      $data['customer_name'] = $customer->full_name;
      $data['customer_document'] = $customer->num_doc ?? 'N/A';
      $data['customer_address'] = $customer->direction ?? 'N/A';
      $data['customer_district'] = $customer->district ? $customer->district->name : 'N/A';
      $data['customer_email'] = $customer->email ?? 'N/A';
      $data['customer_phone'] = $customer->phone ?? 'N/A';
    } else {
      $data['customer_name'] = 'N/A';
      $data['customer_document'] = 'N/A';
      $data['customer_address'] = 'N/A';
      $data['customer_district'] = 'N/A';
      $data['customer_email'] = 'N/A';
      $data['customer_phone'] = 'N/A';
    }

    // Datos del asesor
    if ($quotation->createdBy) {
      $data['advisor_name'] = $quotation->createdBy->person->nombre_completo ?? 'N/A';
      $data['advisor_phone'] = $quotation->createdBy->person->tel_referencia_3 ?? 'N/A';
      $data['advisor_email'] = $quotation->createdBy->person->email2 ?? 'N/A';
    } else {
      $data['advisor_name'] = 'N/A';
      $data['advisor_phone'] = 'N/A';
      $data['advisor_email'] = 'N/A';
    }

    // Datos del vehículo
    if ($quotation->vehicle) {
      $vehicle = $quotation->vehicle;
      $data['vehicle_plate'] = $vehicle->plate ?? 'N/A';
      $data['vehicle_vin'] = $vehicle->vin ?? 'N/A';
      $data['vehicle_engine'] = $vehicle->engine_number ?? 'N/A';
      $data['vehicle_model'] = $vehicle->model ? $vehicle->model->version : 'N/A';
      $data['vehicle_brand'] = $vehicle->model && $vehicle->model->family && $vehicle->model->family->brand
        ? $vehicle->model->family->brand->name
        : 'N/A';
      $data['vehicle_color'] = $vehicle->color ? $vehicle->color->description : 'N/A';
      $data['vehicle_km'] = $quotation->mileage;
    } else {
      $data['vehicle_plate'] = 'N/A';
      $data['vehicle_vin'] = 'N/A';
      $data['vehicle_engine'] = 'N/A';
      $data['vehicle_model'] = 'N/A';
      $data['vehicle_brand'] = 'N/A';
      $data['vehicle_color'] = 'N/A';
      $data['vehicle_km'] = 'N/A';
    }

    // Filtrar detalles según el parámetro with_labor
    $details = $quotation->details;

    // Detalles de la cotización
    $data['details'] = $details->map(function ($detail) use ($showCodes) {
      return [
        'code' => $showCodes && $detail->product ? $detail->product->code : '',
        'description' => $detail->description,
        'observations' => $detail->observations ?? '',
        'quantity' => $detail->quantity,
        'unit_price' => $detail->unit_price,
        'discount' => $detail->discount_percentage,
        'total_amount' => $detail->net_amount,
        'item_type' => $detail->item_type,
        'supply_type' => $detail->supply_type,
      ];
    });

    // Calcular totales correctamente
    $totalLabor = 0;
    $totalParts = 0;
    $totalDiscounts = 0;

    foreach ($quotation->details as $detail) {
      $itemSubtotal = $detail->total_cost;
      $itemDiscount = $detail->total_cost - $detail->net_amount;

      // Total mano de obra (LABOR) - sin descuento
      if ($detail->item_type === 'LABOR') {
        $totalLabor += $itemSubtotal;
      }

      // Total recambios/repuestos (PRODUCT) - sin descuento
      if ($detail->item_type === 'PRODUCT') {
        $totalParts += $itemSubtotal;
      }

      // Total descuentos en monto (dinero)
      $totalDiscounts += $itemDiscount;
    }

    // Calcular base imponible (base propuesta): mano de obra + recambios - descuento
    $baseImponible = $totalLabor + $totalParts - $totalDiscounts;

    // Calcular IGV: 18% sobre la base imponible
    $igv_amount = $baseImponible * (Constants::VAT_TAX / 100);

    // Calcular total: base imponible + IGV
    $total_amount = $baseImponible + $igv_amount;

    $data['total_labor'] = $totalLabor;
    $data['total_parts'] = $totalParts;
    $data['total_discounts'] = $totalDiscounts;
    $data['base_imponible'] = $baseImponible;
    $data['tax_amount'] = $igv_amount;
    $data['total_amount'] = $total_amount;
    $data['area'] = $quotation->area ? $quotation->area->description : 'N/A';
    $data['currency_symbol'] = $quotation->typeCurrency ? $quotation->typeCurrency->symbol : 'S/';
    $data['currency_name'] = $quotation->typeCurrency ? $quotation->typeCurrency->name : 'SOLES';

    // Convertir firma del cliente a base64 si existe
    $customerSignature = null;
    if ($quotation->customer_signature_url) {
      $customerSignature = Helpers::convertUrlToBase64($quotation->customer_signature_url);
    }
    $data['customer_signature'] = $customerSignature;

    // Generar PDF
    $pdf = Pdf::loadView('reports.ap.postventa.taller.order-quotation', [
      'quotation' => $data
    ]);

    $pdf->setPaper('a4', 'portrait');

    $fileName = 'Cotizacion_' . $quotation->quotation_number . '.pdf';

    return $pdf->download($fileName);
  }

  public function generateQuotationRepuestoPDF($id, $showCodes = true)
  {
    $quotation = ApOrderQuotations::with([
      'vehicle.model.family.brand',
      'vehicle.color',
      'vehicle.customer.district',
      'createdBy',
      'details.product',
      'advancesOrderQuotation'
    ])->find($id);

    if (!$quotation) {
      throw new Exception('Cotización no encontrada');
    }

    // Preparar datos para la vista
    $data = [
      'quotation_number' => $quotation->quotation_number,
      'quotation_date' => $quotation->quotation_date,
      'expiration_date' => $quotation->expiration_date,
      'observations' => $quotation->observations ?? '',
      'validity_days' => $quotation->validity_days,
      'show_codes' => $showCodes,
      'sede' => $quotation->sede,
      'type_currency' => $quotation->typeCurrency,
      'status' => $quotation->status,
    ];

    // Datos del cliente
    $data['customer_name'] = $quotation->client->full_name ?? 'N/A';
    $data['customer_document'] = $quotation->client->num_doc ?? 'N/A';
    $data['customer_address'] = $quotation->client->direction ?? 'N/A';
    $data['customer_district'] = $quotation->client->district ? $quotation->client->district->name : 'N/A';
    $data['customer_email'] = $quotation->client->email ?? 'N/A';
    $data['customer_phone'] = $quotation->client->phone ?? 'N/A';
    $data['customer_activity'] = $quotation->client->activityEconomic->description ?? 'N/A';

    // Datos del asesor
    if ($quotation->createdBy) {
      $data['advisor_name'] = $quotation->createdBy->person->nombre_completo ?? 'N/A';
      $data['advisor_phone'] = $quotation->createdBy->person->tel_referencia_3 ?? 'N/A';
      $data['advisor_email'] = $quotation->createdBy->person->email2 ?? 'N/A';
    } else {
      $data['advisor_name'] = 'N/A';
      $data['advisor_phone'] = 'N/A';
      $data['advisor_email'] = 'N/A';
    }

    // Datos del vehículo (sin kilometraje)
    if ($quotation->vehicle) {
      $vehicle = $quotation->vehicle;
      $data['vehicle_plate'] = $vehicle->plate ?? 'N/A';
      $data['vehicle_vin'] = $vehicle->vin ?? 'N/A';
      $data['vehicle_engine'] = $vehicle->engine_number ?? 'N/A';

      // Get model version
      if ($vehicle->model) {
        $data['vehicle_model'] = $vehicle->model->version ?? 'N/A';

        // Get brand name through family relationship
        if ($vehicle->model->family) {
          $data['vehicle_brand'] = $vehicle->model->family->brand
            ? $vehicle->model->family->brand->name
            : 'N/A';
        } else {
          $data['vehicle_brand'] = 'N/A';
        }
      } else {
        $data['vehicle_model'] = 'N/A';
        $data['vehicle_brand'] = 'N/A';
      }

      $data['vehicle_color'] = $vehicle->color ? $vehicle->color->description : 'N/A';
    } else {
      $data['vehicle_plate'] = 'N/A';
      $data['vehicle_vin'] = 'N/A';
      $data['vehicle_engine'] = 'N/A';
      $data['vehicle_model'] = 'N/A';
      $data['vehicle_brand'] = 'N/A';
      $data['vehicle_color'] = 'N/A';
    }

    // Filtrar solo repuestos (excluir mano de obra)
    $repuestosDetails = $quotation->details->where('item_type', '!=', 'labor');

    // Detalles de la cotización (solo repuestos)
    $data['details'] = $repuestosDetails->map(function ($detail) use ($showCodes) {
      return [
        'code' => $showCodes && $detail->product ? $detail->product->code : '',
        'description' => $detail->description,
        'unit_measure' => $detail->unit_measure,
        'observations' => $detail->observations ?? '',
        'quantity' => $detail->quantity,
        'unit_price' => $detail->unit_price,
        'discount' => $detail->discount_percentage,
        'total_amount' => $detail->net_amount,
        'total_amount_with_tax' => $detail->net_amount + $detail->tax_amount,
        'item_type' => $detail->item_type,
        'supply_type' => $detail->supply_type,
      ];
    });

    $data['op_gravada'] = $quotation->subtotal - $quotation->discount_amount;
    $data['total_discounts'] = $quotation->discount_amount;
    $data['subtotal'] = $quotation->subtotal;
    $data['tax_amount'] = $quotation->tax_amount;
    $data['total_amount'] = $quotation->total_amount;
    $data['area'] = $quotation->area ? $quotation->area->description : 'N/A';

    // Calcular pagos realizados (anticipos no anulados)
    $totalPagado = $quotation->advancesOrderQuotation
      ->where('anulado', false)
      ->where('aceptada_por_sunat', 1)
      ->sum('total');

    $data['total_pagado'] = $totalPagado;
    $data['saldo_pendiente'] = $quotation->total_amount - $totalPagado;

    // Convertir firma del cliente a base64 si existe
    $customerSignature = null;
    if ($quotation->customer_signature_url) {
      $customerSignature = Helpers::convertUrlToBase64($quotation->customer_signature_url);
    }
    $data['customer_signature'] = $customerSignature;

    // Generar PDF
    $pdf = Pdf::loadView('reports.ap.postventa.taller.order-quotation-repuesto', [
      'quotation' => $data
    ]);

    $pdf->setPaper('a4', 'portrait');

    $fileName = 'Cotizacion_Repuestos_' . $quotation->quotation_number . '.pdf';

    return $pdf->download($fileName);
  }

  public function confirm(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $quotation = $this->find($data['id']);

      // Validaciones
      if ($quotation->status === ApOrderQuotations::STATUS_DESCARTADO) {
        throw new Exception('No se puede confirmar una cotización que ha sido descartada.');
      }

      if ($quotation->getActiveAdvances()->count() > 0) {
        throw new Exception('No se puede confirmar una cotización que tiene anticipos registrados');
      }

      if ($quotation->status === ApOrderQuotations::STATUS_POR_FACTURAR) {
        throw new Exception('Esta cotización ya ha sido confirmada previamente.');
      }

      if ($quotation->segmentedQuotations()->count() > 0) {
        throw new Exception('Esta cotización no se puede confirmar porque ha sido segmentada en otras cotizaciones.');
      }

      // Procesar firma del cliente si existe
      if (isset($data['customer_signature'])) {
        $this->processCustomerSignature($quotation, $data['customer_signature']);
      }

      // Reservar stock para productos de tipo STOCK
      $this->reserveStockForQuotation($quotation);

      // Cambiar el estado a "Por Facturar"
      $quotation->update([
        'confirmed_at' => Carbon::now(),
        'confirmation_channel' => 'presencial',
        'notes' => $data['notes'],
        'status' => ApOrderQuotations::STATUS_POR_FACTURAR,
      ]);

      $quotation->load([
        'vehicle',
        'createdBy',
        'details',
      ]);

      return new ApOrderQuotationsResource($quotation);
    });
  }

  /**
   * Aprueba una cotización según el cargo del usuario autenticado:
   * - Jefe de Taller (69, 99, 143, 246) → chief_approval_by
   * - Gerente (142) → manager_approval_by
   */
  public function approveTaller($data)
  {
    return DB::transaction(function () use ($data) {
      $id = $data['id'];
      $quotation = $this->find($id);
      $user = auth()->user();

      if ($quotation->status === ApOrderQuotations::STATUS_DESCARTADO) {
        throw new Exception('No se puede aprobar una cotización que ha sido descartada.');
      }

      $positionId = $user->person?->position?->id;

      // Validar aprobación de gerente
      if (isset($data['manager_approval_by'])) {
        if (!in_array($positionId, Position::POSITION_GERENTE_PV_IDS)) {
          throw new Exception('Solo el Gerente puede aprobar.');
        }
        if ($quotation->manager_approval_by) {
          throw new Exception('Esta cotización ya fue aprobada por el Gerente.');
        }
        $quotation->update(['manager_approval_by' => $user->id]);
      }

      // Validar aprobación de jefe
      if (isset($data['chief_approval_by'])) {
        if (!in_array($positionId, Position::POSITION_JEFE_TALLER_PVT_IDS)) {
          throw new Exception('Solo el Jefe de Taller puede aprobar.');
        }
        if ($quotation->chief_approval_by) {
          throw new Exception('Esta cotización ya fue aprobada por el Jefe de Taller.');
        }
        $quotation->update(['chief_approval_by' => $user->id]);
      }

      $quotation->load([
        'vehicle',
        'createdBy',
        'details',
        'chiefApprovalBy',
        'managerApprovalBy',
      ]);

//      $this->sendQuotationApprovalEvidenceEmail(
//        $quotation,
//        $data,
//        $user,
//        Position::POSITION_JEFE_TALLER_PVT_IDS,
//        'Jefe de Taller',
//        'Taller'
//      );

      return new ApOrderQuotationsResource($quotation);
    });
  }

  /**
   * Envía correo de evidencia cuando se aprueba una cotización (Taller o Repuesto), a 3 actores:
   * el asesor que creó la cotización, el Jefe del área y el Gerente.
   */
  private function sendQuotationApprovalEvidenceEmail(
    ApOrderQuotations $quotation,
    array             $data,
    User              $approver,
    array             $chiefPositionIds,
    string            $chiefRoleLabel,
    string            $areaLabel
  ): void
  {
    try {
      $quotation->loadMissing(['vehicle', 'client', 'createdBy.person']);

      $approverRole = isset($data['manager_approval_by']) ? 'Gerente' : $chiefRoleLabel;
      $approverName = $approver->person?->nombre_completo ?? $approverRole;

      $emailData = [
        'quotation_number' => $quotation->quotation_number,
        'plate' => $quotation->vehicle?->plate,
        'customer_name' => $quotation->client?->full_name ?? $quotation->vehicle?->customer?->full_name ?? 'N/A',
        'total_amount' => (float)$quotation->total_amount,
        'currency' => $quotation->typeCurrency?->code ?? 'PEN',
        'requester_name' => $quotation->createdBy?->person?->nombre_completo ?? 'Asesor',
        'approver_name' => $approverName,
        'approver_role' => $approverRole,
        'area_label' => $areaLabel,
        'approval_date' => Carbon::now()->format('d/m/Y H:i'),
        'button_url' => config('app.frontend_url') . '/ap/post-venta/taller/cotizacion-taller/aprobar/' . $quotation->id,
      ];

      $subject = 'Cotización de ' . $areaLabel . ' Aprobada - ' . $quotation->quotation_number;

      // 1. Notificar al asesor (usuario al que le aprobaron la cotización)
      if ($quotation->createdBy?->person?->email2) {
        $this->emailService->queue([
          'to' => $quotation->createdBy->person->email2,
          'subject' => $subject,
          'template' => 'emails.quotation-taller-approved',
          'data' => array_merge($emailData, ['recipient_name' => $quotation->createdBy->person->nombre_completo ?? 'Asesor']),
        ]);
      }

      // 2. Notificar a los Jefes del área
      $chiefUsers = User::whereHas('person', function ($query) use ($chiefPositionIds) {
        $query->whereIn('cargo_id', $chiefPositionIds)
          ->where('status_deleted', 1)
          ->where('status_id', 22);
      })->get();

      foreach ($chiefUsers as $chief) {
        if ($chief->person?->email2) {
          $this->emailService->queue([
            'to' => $chief->person->email2,
            'subject' => $subject,
            'template' => 'emails.quotation-taller-approved',
            'data' => array_merge($emailData, ['recipient_name' => $chief->person->nombre_completo ?? $chiefRoleLabel]),
          ]);
        }
      }

      // 3. Notificar a los Gerentes
      $managerUsers = User::whereHas('person', function ($query) {
        $query->whereIn('cargo_id', Position::POSITION_GERENTE_PV_IDS)
          ->where('status_deleted', 1)
          ->where('status_id', 22);
      })->get();

      foreach ($managerUsers as $manager) {
        if ($manager->person?->email2) {
          $this->emailService->queue([
            'to' => $manager->person->email2,
            'subject' => $subject,
            'template' => 'emails.quotation-taller-approved',
            'data' => array_merge($emailData, ['recipient_name' => $manager->person->nombre_completo ?? 'Gerente']),
          ]);
        }
      }
    } catch (Exception $e) {
      \Log::error('Error al enviar notificación de evidencia de aprobación de ' . $areaLabel . ': ' . $e->getMessage());
    }
  }

  /**
   * Aprueba una cotización según el cargo del usuario autenticado:
   * - Jefe de Repuestos (344) → chief_approval_by
   * - Gerente (142) → manager_approval_by
   */
  public function approveRepuesto($data)
  {
    return DB::transaction(function () use ($data) {
      $id = $data['id'];
      $quotation = $this->find($id);
      $user = auth()->user();

      if ($quotation->status === ApOrderQuotations::STATUS_DESCARTADO) {
        throw new Exception('No se puede aprobar una cotización que ha sido descartada.');
      }

      $positionId = $user->person?->position?->id;

      // Validar aprobación de gerente
      if (isset($data['manager_approval_by'])) {
        if (!in_array($positionId, Position::POSITION_GERENTE_PV_IDS)) {
          throw new Exception('Solo el Gerente puede aprobar.');
        }
        if ($quotation->manager_approval_by) {
          throw new Exception('Esta cotización ya fue aprobada por el Gerente.');
        }
        $quotation->update(['manager_approval_by' => $user->id]);
      }

      // Validar aprobación de jefe
      if (isset($data['chief_approval_by'])) {
        if (!in_array($positionId, Position::POSITION_JEFE_REPUESTO_PVT_IDS)) {
          throw new Exception('Solo el Jefe de Repuesto puede aprobar.');
        }
        if ($quotation->chief_approval_by) {
          throw new Exception('Esta cotización ya fue aprobada por el Jefe de Repuesto.');
        }
        $quotation->update(['chief_approval_by' => $user->id]);
      }

      $quotation->load([
        'vehicle',
        'createdBy',
        'details',
        'chiefApprovalBy',
        'managerApprovalBy',
      ]);

//      $this->sendQuotationApprovalEvidenceEmail(
//        $quotation,
//        $data,
//        $user,
//        Position::POSITION_JEFE_REPUESTO_PVT_IDS,
//        'Jefe de Repuesto',
//        'Repuestos'
//      );

      return new ApOrderQuotationsResource($quotation);
    });
  }

  public function updateDeliveryInfo(int $id, array $data)
  {
    return DB::transaction(function () use ($id, $data) {
      $quotation = $this->find($id);

      // Actualizar número de documento de entrega si se proporciona
      if (isset($data['delivery_document_number'])) {
        $quotation->delivery_document_number = $data['delivery_document_number'];
      }

      // Procesar firma de entrega si se proporciona
      if (isset($data['customer_signature_delivery_url'])) {
        $this->processDeliverySignature($quotation, $data['customer_signature_delivery_url']);
      }

      // Guardar cambios si no se procesó firma (si se procesó, ya se guardó en processDeliverySignature)
      if (!isset($data['customer_signature_delivery_url']) && isset($data['delivery_document_number'])) {
        $quotation->save();
      }

      return new ApOrderQuotationsResource($quotation->fresh([
        'vehicle',
        'createdBy',
        'details'
      ]));
    });
  }

  /**
   * Envía notificación por correo al jefe y gerente de taller sobre una cotización
   * que ha sido solicitada por gerencia
   */
  public function sendQuotationNotificationEmail($id)
  {
    return DB::transaction(function () use ($id) {
      $quotation = ApOrderQuotations::with([
        'vehicle.model.family.brand',
        'vehicle.customer',
        'client',
        'createdBy.person',
        'details.product',
        'sede',
        'typeCurrency',
        'area'
      ])->find($id);

      if (!$quotation) {
        throw new Exception('Cotización no encontrada');
      }

      // Validar que la cotización tenga is_requested_by_management = true
      if (!$quotation->is_requested_by_management) {
        throw new Exception('Esta cotización no ha sido solicitada por gerencia.');
      }

      // Obtener usuarios con cargo de Jefe de Taller (143) y Gerente de Taller (142)
      $chiefUsers = User::whereHas('person', function ($query) {
        $query->whereIn('cargo_id', Position::POSITION_JEFE_TALLER_PVT_IDS)
          ->where('status_deleted', 1)
          ->where('status_id', 22);
      })->get();

      $managerUsers = User::whereHas('person', function ($query) {
        $query->whereIn('cargo_id', Position::POSITION_GERENTE_PV_IDS)
          ->where('status_deleted', 1)
          ->where('status_id', 22);
      })->get();

      // Preparar datos para el correo
      $emailData = [
        'quotation_number' => $quotation->quotation_number,
        'quotation_date' => $quotation->quotation_date ? $quotation->quotation_date->format('d/m/Y') : 'N/A',
        'expiration_date' => $quotation->expiration_date ? $quotation->expiration_date->format('d/m/Y') : 'N/A',
        'validity_days' => $quotation->validity_days,
        'observations' => $quotation->observations ?? '',

        // Cliente
        'customer_name' => $quotation->client->full_name ?? $quotation->vehicle?->customer?->full_name ?? 'N/A',
        'customer_document' => $quotation->client->num_doc ?? $quotation->vehicle?->customer?->num_doc ?? 'N/A',
        'customer_phone' => $quotation->client->phone ?? $quotation->vehicle?->customer?->phone ?? 'N/A',
        'customer_email' => $quotation->client->email ?? $quotation->vehicle?->customer?->email ?? 'N/A',

        // Vehículo
        'vehicle_plate' => $quotation->vehicle?->plate ?? 'N/A',
        'vehicle_brand' => $quotation->vehicle?->model?->family?->brand?->name ?? 'N/A',
        'vehicle_model' => $quotation->vehicle?->model?->version ?? 'N/A',

        // Sede y moneda
        'sede_name' => $quotation->sede?->abreviatura ?? 'N/A',
        'currency' => $quotation->typeCurrency?->code ?? 'PEN',
        'area' => $quotation->area?->description ?? 'N/A',

        // Asesor
        'advisor_name' => $quotation->createdBy?->person?->nombre_completo ?? 'N/A',
        'advisor_email' => $quotation->createdBy?->person?->email2 ?? 'N/A',
        'advisor_phone' => $quotation->createdBy?->person?->cel_personal ?? 'N/A',

        // Totales
        'subtotal' => $quotation->subtotal,
        'discount_percentage' => $quotation->discount_percentage,
        'discount_amount' => $quotation->discount_amount,
        'tax_amount' => $quotation->tax_amount,
        'total_amount' => $quotation->total_amount,

        // Detalles de productos/servicios
        'details' => $quotation->details->map(function ($detail) {
          return [
            'code' => $detail->product?->code ?? 'N/A',
            'description' => $detail->description,
            'quantity' => $detail->quantity,
            'unit_measure' => $detail->unit_measure,
            'unit_price' => $detail->unit_price,
            'discount_percentage' => $detail->discount_percentage,
            'total_amount' => $detail->net_amount,
            'item_type' => $detail->item_type,
            'observations' => $detail->observations ?? '',
          ];
        }),

        // URL del frontend
        'button_url' => config('app.frontend_url') . '/ap/post-venta/taller/cotizacion-taller/aprobar/' . $quotation->id,
      ];

      $subject = 'Nueva Cotización solicitada por Gerencia - ' . $quotation->quotation_number;
      $emailsSentCount = 0;

      // Enviar correo a los Jefes de Taller
      foreach ($chiefUsers as $chief) {
        if ($chief->person && $chief->person->email2) {
          try {
//            $this->emailService->queue([
//              'to' => $chief->person->email2,
//              'subject' => $subject,
//              'template' => 'emails.quotation-notification',
//              'data' => array_merge($emailData, [
//                'recipient_name' => $chief->person->nombre_completo ?? 'Jefe de Taller',
//                'recipient_role' => 'Jefe de Taller',
//              ]),
//            ]);
            $emailsSentCount++;
          } catch (Exception $e) {
            \Log::error('Error al enviar correo al Jefe de Taller: ' . $e->getMessage());
          }
        }
      }

      // Enviar correo a los Gerentes de Taller
      foreach ($managerUsers as $manager) {
        if ($manager->person && $manager->person->email2) {
          try {
            $this->emailService->queue([
              'to' => $manager->person->email2,
              'subject' => $subject,
              'template' => 'emails.quotation-notification',
              'data' => array_merge($emailData, [
                'recipient_name' => $manager->person->nombre_completo ?? 'Gerente de Taller',
                'recipient_role' => 'Gerente de Taller',
              ]),
            ]);
            $emailsSentCount++;
          } catch (Exception $e) {
            \Log::error('Error al enviar correo al Gerente de Taller: ' . $e->getMessage());
          }
        }
      }

      // Incrementar el contador de emails enviados
      $quotation->increment('emails_sent_count', $emailsSentCount);

      return [
        'message' => 'Notificaciones enviadas correctamente',
        'emails_sent' => $emailsSentCount,
        'quotation' => new ApOrderQuotationsResource($quotation->fresh())
      ];
    });
  }

  /**
   * Procesa y guarda la firma de entrega del cliente en base64
   */
  private function processDeliverySignature($quotation, string $base64Signature): void
  {
    $signatureFile = Helpers::base64ToUploadedFile($base64Signature, "customer_signature_delivery.png");

    $path = self::FILE_PATHS['customer_signature_delivery'];
    $model = $quotation->getTable();

    $digitalFile = $this->digitalFileService->store($signatureFile, $path, 'public', $model);

    $quotation->customer_signature_delivery_url = $digitalFile->url;
    $quotation->save();
  }

  /**
   * Procesa y guarda la firma del cliente en base64
   */
  private function processCustomerSignature($quotation, string $base64Signature): void
  {
    // Convertir base64 a UploadedFile
    $signatureFile = Helpers::base64ToUploadedFile($base64Signature, "customer_signature.png");

    // Ruta y modelo
    $path = self::FILE_PATHS['customer_signature'];
    $model = $quotation->getTable();

    // Subir archivo usando DigitalFileService
    $digitalFile = $this->digitalFileService->store($signatureFile, $path, 'public', $model);

    // Actualizar la cotización con la URL de la firma
    $quotation->customer_signature_url = $digitalFile->url;
    $quotation->save();
  }

  /**
   * Duplica una cotización existente con todos sus detalles
   * Genera un nuevo correlativo y resetea campos específicos
   */
  public function duplicate($id)
  {
    return DB::transaction(function () use ($id) {
      // 1. Obtener la cotización original con sus detalles
      $originalQuotation = $this->find($id);

      if ($originalQuotation->area_id !== ApMasters::AREA_TALLER) {
        throw new Exception('Solo se pueden duplicar cotizaciones del área de Taller.');
      }

      // 2. Convertir a array y preparar datos para la nueva cotización
      $newQuotationData = $originalQuotation->toArray();

      // 3. Remover campos que no se deben copiar (auto-generados)
      unset($newQuotationData['id']);
      unset($newQuotationData['quotation_number']);
      unset($newQuotationData['created_at']);
      unset($newQuotationData['updated_at']);
      unset($newQuotationData['deleted_at']);

      // 4. Generar nuevo número de cotización
      $newQuotationData['quotation_number'] = ApOrderQuotations::generateNextQuotationNumber($originalQuotation->sede_id);

      // 5. Resetear campos específicos según tus requerimientos
      $newQuotationData['quotation_date'] = Carbon::now()->toDateString();
      $newQuotationData['expiration_date'] = Carbon::now()->addDays(7)->toDateString();
      $newQuotationData['status'] = ApOrderQuotations::STATUS_APERTURADO;
      $newQuotationData['created_by'] = auth()->check() ? auth()->user()->id : null;
      $newQuotationData['chief_approval_by'] = null;
      $newQuotationData['manager_approval_by'] = null;
      $newQuotationData['has_invoice_generated'] = false;
      $newQuotationData['is_take'] = false;
      $newQuotationData['customer_signature_url'] = null;
      $newQuotationData['customer_signature_delivery_url'] = null;
      $newQuotationData['delivery_document_number'] = null;
      $newQuotationData['discard_reason_id'] = null;
      $newQuotationData['discarded_note'] = null;
      $newQuotationData['discarded_by'] = null;
      $newQuotationData['discarded_at'] = null;
      $newQuotationData['is_fully_paid'] = false;
      $newQuotationData['emails_sent_count'] = 0;

      // 6. Crear la nueva cotización
      $newQuotation = ApOrderQuotations::create($newQuotationData);

      // 7. Duplicar todos los detalles
      foreach ($originalQuotation->details as $detail) {
        $newDetailData = $detail->toArray();

        // Remover campos auto-generados del detalle
        unset($newDetailData['id']);
        unset($newDetailData['order_quotation_id']);
        unset($newDetailData['created_at']);
        unset($newDetailData['updated_at']);
        unset($newDetailData['deleted_at']);

        // Crear el nuevo detalle asociado a la nueva cotización
        $newQuotation->details()->create($newDetailData);
      }

      // 8. Retornar la nueva cotización con sus relaciones cargadas
      return new
      ApOrderQuotationsResource($newQuotation->load([
        'vehicle',
        'createdBy',
        'details.product'
      ]));
    });
  }

  /**
   * Genera el token de confirmación virtual y envía el link por correo al cliente
   */
  public function sendVirtualConfirmationLink($id)
  {
    return DB::transaction(function () use ($id) {
      $quotation = ApOrderQuotations::with([
        'vehicle.model.family.brand',
        'client',
        'createdBy.person',
        'sede',
        'typeCurrency',
        'details.product'
      ])->find($id);

      if (!$quotation) {
        throw new Exception('Cotización no encontrada');
      }

      // Validar que la cotización esté en estado válido
      if ($quotation->status === ApOrderQuotations::STATUS_DESCARTADO) {
        throw new Exception('No se puede enviar link de confirmación a una cotización descartada.');
      }

      if ($quotation->getActiveAdvances()->count() > 0) {
        throw new Exception('No se puede enviar link de confirmación a una cotización que tiene anticipos registrados');
      }

      if ($quotation->isConfirmed()) {
        throw new Exception('Esta cotización ya fue confirmada anteriormente.');
      }

      // Validar que haya un email del cliente
      if (!$quotation->client || !$quotation->client->email) {
        throw new Exception('El cliente no tiene un correo electrónico registrado.');
      }

      // Generar o regenerar el token
      $confirmationLink = $quotation->getConfirmationLink();

      // Preparar datos para el correo
      $emailData = [
        'quotation_number' => $quotation->quotation_number,
        'quotation_date' => $quotation->quotation_date ? $quotation->quotation_date->format('d/m/Y') : 'N/A',
        'expiration_date' => $quotation->expiration_date ? $quotation->expiration_date->format('d/m/Y') : 'N/A',
        'total_amount' => $quotation->total_amount,
        'currency' => $quotation->typeCurrency?->code ?? 'PEN',

        // Cliente
        'customer_name' => $quotation->client->full_name,
        'customer_email' => $quotation->client->email,

        // Vehículo
        'vehicle_plate' => $quotation->vehicle?->plate ?? 'N/A',
        'vehicle_brand' => $quotation->vehicle?->model?->family?->brand?->name ?? 'N/A',
        'vehicle_model' => $quotation->vehicle?->model?->version ?? 'N/A',

        // Asesor
        'advisor_name' => $quotation->createdBy?->person?->nombre_completo ?? 'N/A',
        'advisor_email' => $quotation->createdBy?->person?->email2 ?? 'N/A',
        'advisor_phone' => $quotation->createdBy?->person?->cel_personal ?? 'N/A',

        // Sede
        'sede_name' => $quotation->sede?->razon_social ?? 'N/A',

        // Link de confirmación
        'confirmation_link' => $confirmationLink,
        'token_expires_at' => $quotation->confirmation_token_expires_at?->format('d/m/Y H:i'),
      ];

      // Enviar correo al cliente
      $this->emailService->queue([
        'to' => 'wsuclupef@automotorespakatnamu.com', //$quotation->client->email,
        'subject' => 'Confirmación de Cotización - ' . $quotation->quotation_number,
        'template' => 'emails.quotation-virtual-confirmation',
        'data' => $emailData,
      ]);

      // Incrementar contador de emails enviados
      $quotation->increment('emails_sent_count');

      return [
        'success' => true,
        'message' => 'Link de confirmación enviado exitosamente al correo del cliente.',
        'confirmation_link' => $confirmationLink,
        'sent_to' => $quotation->client->email,
        'expires_at' => $quotation->confirmation_token_expires_at,
        'quotation' => new ApOrderQuotationsResource($quotation->fresh())
      ];
    });
  }

  /**
   * Regenera el token de confirmación si ha expirado
   */
  public function regenerateConfirmationToken($id)
  {
    return DB::transaction(function () use ($id) {
      $quotation = $this->find($id);

      if ($quotation->isConfirmed()) {
        throw new Exception('No se puede regenerar el token de una cotización ya confirmada.');
      }

      if ($quotation->status === ApOrderQuotations::STATUS_DESCARTADO) {
        throw new Exception('No se puede regenerar el token de una cotización descartada.');
      }

      // Generar nuevo token
      $quotation->generateConfirmationToken();

      return [
        'message' => 'Token regenerado exitosamente.',
        'confirmation_link' => $quotation->getConfirmationLink(),
        'expires_at' => $quotation->confirmation_token_expires_at,
        'quotation' => new ApOrderQuotationsResource($quotation)
      ];
    });
  }

  public function invoiceTo(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $apOrderQuotations = $this->find($data['id']);

      if (!$apOrderQuotations) {
        throw new Exception('Cotización no encontrada');
      }

      if ($apOrderQuotations->getActiveAdvances()->count() > 0) {
        throw new Exception('No se puede modificar el destinatario de factura porque ya se han registrado anticipos para esta cotización');
      }

      if ($apOrderQuotations->getFinalInvoice()) {
        throw new Exception('No se puede modificar el destinatario de factura porque ya se ha generado una factura final para esta cotización');
      }

      // Update work order
      $apOrderQuotations->update($data);

      return new ApOrderQuotationsResource($apOrderQuotations);
    });
  }

  /**
   * Segmenta una cotización por supply_type de sus items.
   * Crea nuevas cotizaciones agrupando los items según su supply_type.
   *
   * @param int $id ID de la cotización a segmentar
   * @return array Array de cotizaciones segmentadas creadas
   * @throws Exception
   */
  public function segmentBySupplyType(int $id)
  {
    return DB::transaction(function () use ($id) {
      // 1. Obtener la cotización original con sus detalles
      $originalQuotation = $this->find($id);

      if (!$originalQuotation) {
        throw new Exception('Cotización no encontrada');
      }

      // 2. Validar que no tenga cotizaciones segmentadas previas
      if ($originalQuotation->segmentedQuotations()->count() > 0) {
        throw new Exception('Esta cotización ya ha sido segmentada previamente');
      }

      // 3. Validar que tenga detalles
      if ($originalQuotation->details->count() === 0) {
        throw new Exception('La cotización no tiene items para segmentar');
      }

      // 4. Agrupar detalles por supply_type
      $detailsBySupplyType = $originalQuotation->details->groupBy('supply_type');

      // 5. Validar que haya más de un tipo de supply_type
      if ($detailsBySupplyType->count() <= 1) {
        throw new Exception('La cotización solo tiene un tipo de abastecimiento, no es necesario segmentar');
      }

      // 6. Crear una cotización por cada grupo de supply_type
      $segmentedQuotations = [];

      foreach ($detailsBySupplyType as $supplyType => $details) {
        // 6.1. Preparar datos para la nueva cotización
        $newQuotationData = $originalQuotation->toArray();

        // 6.2. Remover campos que no se deben copiar
        unset($newQuotationData['id']);
        unset($newQuotationData['quotation_number']);
        unset($newQuotationData['created_at']);
        unset($newQuotationData['updated_at']);
        unset($newQuotationData['deleted_at']);
        unset($newQuotationData['subtotal']);
        unset($newQuotationData['discount_amount']);
        unset($newQuotationData['discount_percentage']);
        unset($newQuotationData['tax_amount']);
        unset($newQuotationData['total_amount']);

        // 6.3. Generar nuevo número de cotización
        $newQuotationData['quotation_number'] = ApOrderQuotations::generateNextQuotationNumber($originalQuotation->sede_id);

        // 6.4. Configurar campos específicos
        $newQuotationData['parent_quotation_id'] = $originalQuotation->id;
        $newQuotationData['supply_type'] = $supplyType;
        $newQuotationData['quotation_date'] = Carbon::now()->toDateString();
        $newQuotationData['expiration_date'] = $originalQuotation->expiration_date
          ? Carbon::parse($originalQuotation->expiration_date)->toDateString()
          : Carbon::now()->addDays(7)->toDateString();
        $newQuotationData['status'] = ApOrderQuotations::STATUS_APERTURADO;
        $newQuotationData['created_by'] = auth()->check() ? auth()->user()->id : $originalQuotation->created_by;

        // Resetear campos de aprobación y confirmación
        $newQuotationData['chief_approval_by'] = null;
        $newQuotationData['manager_approval_by'] = null;
        $newQuotationData['has_invoice_generated'] = false;
        $newQuotationData['is_take'] = false;
        $newQuotationData['customer_signature_url'] = null;
        $newQuotationData['customer_signature_delivery_url'] = null;
        $newQuotationData['delivery_document_number'] = null;
        $newQuotationData['discard_reason_id'] = null;
        $newQuotationData['discarded_note'] = null;
        $newQuotationData['discarded_by'] = null;
        $newQuotationData['discarded_at'] = null;
        $newQuotationData['is_fully_paid'] = false;
        $newQuotationData['emails_sent_count'] = 0;
        $newQuotationData['confirmation_token'] = null;
        $newQuotationData['confirmation_token_expires_at'] = null;
        $newQuotationData['confirmed_at'] = null;
        $newQuotationData['confirmation_channel'] = null;
        $newQuotationData['confirmation_ip'] = null;
        $newQuotationData['confirmation_metadata'] = null;

        // Actualizar observaciones para indicar que es segmentada
        $newQuotationData['observations'] = $originalQuotation->observations
          ? $originalQuotation->observations . " - SEGMENTADA POR TIPO: " . $supplyType
          : "SEGMENTADA DE COT: " . $originalQuotation->quotation_number . " - TIPO: " . $supplyType;

        // 6.5. Crear la nueva cotización
        $newQuotation = ApOrderQuotations::create($newQuotationData);

        // 6.6. Duplicar los detalles correspondientes a este supply_type
        foreach ($details as $detail) {
          $newDetailData = $detail->toArray();

          // Remover campos auto-generados del detalle
          unset($newDetailData['id']);
          unset($newDetailData['order_quotation_id']);
          unset($newDetailData['created_at']);
          unset($newDetailData['updated_at']);
          unset($newDetailData['deleted_at']);

          // Crear el nuevo detalle asociado a la nueva cotización
          $newQuotation->details()->create($newDetailData);
        }

        // 6.7. Calcular los totales de la nueva cotización
        $newQuotation->calculateTotals();
        $newQuotation->save();

        // 6.8. Agregar a la lista de cotizaciones segmentadas
        $segmentedQuotations[] = $newQuotation;
      }

      // 6.9. Marcar la cotización original como Segmentada (borrador, se excluye de la reportería)
      $originalQuotation->update(['status' => ApOrderQuotations::STATUS_SEGMENTADA]);

      // 7. Cargar relaciones y retornar
      $quotationsWithRelations = ApOrderQuotations::whereIn('id', collect($segmentedQuotations)->pluck('id'))
        ->with([
          'vehicle',
          'client',
          'createdBy',
          'details.product',
          'parentQuotation'
        ])
        ->get();

      return [
        'message' => 'Cotización segmentada exitosamente',
        'parent_quotation' => new ApOrderQuotationsResource($originalQuotation->load(['segmentedQuotations'])),
        'segmented_quotations' => ApOrderQuotationsResource::collection($quotationsWithRelations),
        'total_segmented' => count($segmentedQuotations)
      ];
    });
  }

  /**
   * Asocia una guía de remisión a una cotización
   *
   * @param int $quotationId ID de la cotización
   * @param int $shippingGuideId ID de la guía de remisión
   * @return array
   * @throws Exception
   */
  public function associateShippingGuide(int $quotationId, int $shippingGuideId): array
  {
    return DB::transaction(function () use ($quotationId, $shippingGuideId) {
      $quotation = ApOrderQuotations::find($quotationId);

      if (!$quotation) {
        throw new Exception('La cotización no existe');
      }

      // Usar el método del modelo que ya tiene las validaciones
      $quotation->associateShippingGuide($shippingGuideId);

      // Cargar la relación y retornar
      $quotation->load('shippingGuide');

      return [
        'message' => 'Guía de remisión asociada exitosamente',
        'quotation' => new ApOrderQuotationsResource($quotation),
      ];
    });
  }

  /**
   * Desasocia la guía de remisión de una cotización
   *
   * @param int $quotationId ID de la cotización
   * @return array
   * @throws Exception
   */
  public function dissociateShippingGuide(int $quotationId): array
  {
    return DB::transaction(function () use ($quotationId) {
      $quotation = ApOrderQuotations::find($quotationId);

      if (!$quotation) {
        throw new Exception('La cotización no existe');
      }

      if (!$quotation->shipping_guide_id) {
        throw new Exception('La cotización no tiene una guía de remisión asociada');
      }

      // Usar el método del modelo
      $quotation->dissociateShippingGuide();

      return [
        'message' => 'Guía de remisión desasociada exitosamente',
        'quotation' => new ApOrderQuotationsResource($quotation),
      ];
    });
  }

  /**
   * Cambiar el tipo de moneda de una cotización
   *
   * @param mixed $data
   * @return ApOrderQuotationsResource
   * @throws Exception
   */
  public function changeCurrency(mixed $data): ApOrderQuotationsResource
  {
    return DB::transaction(function () use ($data) {
      $quotation = ApOrderQuotations::with(['advancesOrderQuotation'])->find($data['id']);

      if (!$quotation) {
        throw new Exception('Cotización no encontrada');
      }

      // Validar que no esté descartada
      if ($quotation->status === ApOrderQuotations::STATUS_DESCARTADO) {
        throw new Exception('No se puede cambiar la moneda de una cotización descartada');
      }

      // Validar que no tenga anticipos activos
      if ($quotation->getActiveAdvances()->count() > 0) {
        throw new Exception("Esta cotización no puede cambiar de moneda. Ya se han registrado anticipos para esta cotización.");
      }

      // Validar que no esté tomada
      if ($quotation->is_take === true) {
        throw new Exception('No se puede cambiar la moneda de una cotización que ya ha sido tomada en una solicitud de compra / OT.');
      }

      // Verificar que la moneda sea diferente
      $oldCurrencyId = $quotation->currency_id;
      $newCurrencyId = $data['currency_id'];

      if ($oldCurrencyId == $newCurrencyId) {
        throw new Exception('La moneda seleccionada es la misma que la actual');
      }

      // Obtener la fecha de la cotización para comparar tipos de cambio
      $quotationDate = Carbon::parse($quotation->quotation_date)->format('Y-m-d');

      // Obtener el tipo de cambio óptimo si la nueva moneda es USD
      if ($newCurrencyId == TypeCurrency::USD_ID) {
        // Obtener el tipo de cambio óptimo entre la fecha de cotización y la fecha actual
        $optimalExchangeRate = ExchangeRate::getOptimalExchangeRate(
          $quotationDate,
          TypeCurrency::PEN_ID,
          TypeCurrency::USD_ID,
          ExchangeRate::TYPE_VENTA,
        );

        if (!$optimalExchangeRate) {
          throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de la cotización ni para la fecha actual.');
        }

        $quotation->exchange_rate = $optimalExchangeRate->rate;
        $quotation->exchange_rate_id = $optimalExchangeRate->id;
      } else {
        // Si es PEN u otra moneda, el tipo de cambio es null
        $quotation->exchange_rate = null;
        $quotation->exchange_rate_id = null;
      }

      // Actualizar la moneda
      $quotation->currency_id = $newCurrencyId;
      $quotation->save();

      // Recalcular detalles con el nuevo tipo de cambio
      $this->handleCurrencyChange($quotation, $oldCurrencyId, $newCurrencyId);

      // Recalcular totales de la cotización
      $quotation->calculateTotals();
      $quotation->save();

      // Recargar relaciones
      $quotation->load([
        'vehicle',
        'createdBy',
        'details',
        'typeCurrency'
      ]);

      return new ApOrderQuotationsResource($quotation);
    });
  }

  /**
   * Maneja el cambio de moneda recalculando los precios de los detalles
   *
   * @param ApOrderQuotations $quotation
   * @param int $oldCurrencyId
   * @param int $newCurrencyId
   * @return void
   * @throws Exception
   */
  private function handleCurrencyChange(ApOrderQuotations $quotation, int $oldCurrencyId, int $newCurrencyId): void
  {
    // Obtener el factor de conversión
    $factor = $this->getConversionFactor($quotation, $oldCurrencyId, $newCurrencyId);

    // Recalcular los precios de los detalles
    $this->recalculateDetailItemsWithFactor($quotation, $factor);
  }

  /**
   * Obtiene el factor de conversión entre dos monedas
   *
   * @param ApOrderQuotations $quotation
   * @param int $oldCurrencyId
   * @param int $newCurrencyId
   * @return float
   * @throws Exception
   */
  private function getConversionFactor(ApOrderQuotations $quotation, int $oldCurrencyId, int $newCurrencyId): float
  {
    // Obtener el tipo de cambio
    $exchangeRate = $this->getExchangeRate($quotation);

    // De soles a dólares: dividir por tipo de cambio
    if ($oldCurrencyId === TypeCurrency::PEN_ID && $newCurrencyId === TypeCurrency::USD_ID) {
      return 1 / $exchangeRate;
    }

    // De dólares a soles: multiplicar por tipo de cambio
    if ($oldCurrencyId === TypeCurrency::USD_ID && $newCurrencyId === TypeCurrency::PEN_ID) {
      return $exchangeRate;
    }

    throw new Exception('Conversión de moneda no soportada');
  }

  /**
   * Obtiene el tipo de cambio para la cotización
   *
   * @param ApOrderQuotations $quotation
   * @return float
   * @throws Exception
   */
  private function getExchangeRate(ApOrderQuotations $quotation): float
  {
    // Si la cotización ya tiene un tipo de cambio, usarlo
    if ($quotation->exchange_rate) {
      return (float)$quotation->exchange_rate;
    }

    // Si no tiene, obtener el del día actual
    $exchangeRate = ExchangeRate::where('date', Carbon::now()->format('Y-m-d'))->first();
    if (!$exchangeRate) {
      throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy.');
    }

    return (float)$exchangeRate->rate;
  }

  /**
   * Recalcula los precios de los detalles aplicando un factor de conversión
   *
   * @param ApOrderQuotations $quotation
   * @param float $factor
   * @return void
   */
  private function recalculateDetailItemsWithFactor(ApOrderQuotations $quotation, float $factor): void
  {
    foreach ($quotation->details as $detail) {
      // Convertir el precio unitario
      $newUnitPrice = $detail->unit_price * $factor;

      // Recalcular usando PriceRounding para mantener consistencia
      $result = PriceRounding::calculateLine(
        (float)$newUnitPrice,
        (float)$detail->quantity,
        (float)($detail->discount_percentage ?? 0)
      );

      $detail->update([
        'unit_price' => $result['unit_price'],
        'total_cost' => $result['total_cost'],
        'net_amount' => $result['net_amount'],
        'tax_amount' => $result['tax_amount'],
      ]);
    }
  }

  /**
   * Recalcula total_cost/net_amount/tax_amount de cada detalle a partir de sus
   * campos base (unit_price, quantity, discount_percentage), usando la misma
   * fuente de verdad (PriceRounding) que se usa al crear/editar el detalle.
   */
  private function recalculateDetailItems(ApOrderQuotations $quotation): void
  {
    foreach ($quotation->details as $detail) {
      $result = PriceRounding::calculateLine(
        (float)$detail->unit_price,
        (float)$detail->quantity,
        (float)($detail->discount_percentage ?? 0)
      );

      $detail->update([
        'unit_price' => $result['unit_price'],
        'total_cost' => $result['total_cost'],
        'net_amount' => $result['net_amount'],
        'tax_amount' => $result['tax_amount'],
      ]);
    }
  }

  /**
   * Recalcula y persiste los totales de la cotización a partir de sus detalles,
   * igual que WorkOrderService::recalculateTotals() para ApWorkOrder. Útil para
   * normalizar cotizaciones antiguas al nuevo redondeo en cadena a 1 decimal.
   *
   * @param int $id
   * @return ApOrderQuotationsResource
   * @throws Exception
   */
  public function recalculateTotals($id)
  {
    return DB::transaction(function () use ($id) {
      // find() ya carga 'details', necesarios para calculateTotals()
      $quotation = $this->find($id);

      // Recalcular primero los detalles (hijos) desde sus campos base, con el mismo
      // redondeo en cadena a 1 decimal usado al crearlos (PriceRounding), para que
      // el total de la cotización sea siempre una suma consistente de hijos ya
      // redondeados y no arrastre valores desalineados (datos antiguos, ediciones
      // directas, etc.), igual que en WorkOrderService::recalculateTotals().
      $this->recalculateDetailItems($quotation);
      $quotation->refresh();
      $quotation->load('details');

      // Recalcular totales del padre a partir de los hijos ya recalculados
      $quotation->calculateTotals();
      $quotation->save();

      // Recargar relaciones para mostrar, igual que show()
      $quotation->load('advancesOrderQuotation');

      $additionalData = [
        'checkStock' => true,
        'includeConfirmationData' => true,
      ];

      if ($quotation->area_id === ApMasters::AREA_TALLER) {
        $additionalData['includeCostManHours'] = true;
      }

      return (new ApOrderQuotationsResource($quotation))->additional($additionalData);
    });
  }

  /**
   * Reserva stock para los productos de tipo STOCK de una cotización
   *
   * @param ApOrderQuotations $quotation
   * @return void
   * @throws Exception
   */
  public function reserveStockForQuotation(ApOrderQuotations $quotation): void
  {
    // Obtener el almacén físico de la sede de la cotización
    $warehouseId = Warehouse::getPhysicalWarehouseForPostsale($quotation->sede_id)?->id;

    if (!$warehouseId) {
      throw new Exception('No se encontró un almacén físico asociado a esta sede para reservar el stock.');
    }

    // Cargar los detalles si no están cargados
    if (!$quotation->relationLoaded('details')) {
      $quotation->load('details');
    }

    // Filtrar solo los productos con supply_type = 'STOCK'
    $stockDetails = $quotation->details->where('supply_type', ApOrderQuotations::STOCK);

    foreach ($stockDetails as $detail) {
      if (!$detail->product_id) {
        continue;
      }

      $stock = ProductWarehouseStock::where('product_id', $detail->product_id)
        ->where('warehouse_id', $warehouseId)
        ->first();

      if (!$stock) {
        throw new Exception(
          "Producto ({$detail->description}): No se encontró registro de stock en el almacén."
        );
      }

      $reserveSuccess = $stock->reserveStock($detail->quantity);
      if (!$reserveSuccess) {
        throw new Exception(
          "Producto ({$detail->description}): No se pudo reservar el stock. Stock insuficiente disponible."
        );
      }
    }
  }
}
