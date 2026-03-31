<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApOrderQuotationsResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\EmailService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Http\Utils\Constants;
use App\Http\Utils\Helpers;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\gp\gestionsistema\Position;
use App\Models\gp\maestroGeneral\ExchangeRate;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
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

  public function listForPurchaseRequest(Request $request)
  {
    // Query base con las condiciones requeridas para solicitudes de compra
    $query = ApOrderQuotations::query()
      ->where(function ($query) {
        // Condición 1: Cotizaciones aprobadas por jefe y gerente en área taller
        $query->where('area_id', ApMasters::AREA_TALLER)
          ->whereNotNull('chief_approval_by')
          ->whereNotNull('manager_approval_by');
      })
      ->orWhereHas('workOrders', function ($query) {
        // Condición 2: Cotizaciones asociadas a OT con factura generada
        $query->where('has_invoice_generated', true);
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

      $exchangeRate = ExchangeRate::where('date', $date)->first();
      if (!$exchangeRate) {
        throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy.');
      }

      if ($vehicle->customer_id === null) {
        throw new Exception('El vehículo debe estar asociado a un "TITULAR" para crear una cotización');
      }

      if (auth()->check()) {
        $data['created_by'] = auth()->user()->id;
      }

      $data['quotation_number'] = $this->generateNextQuotationNumber($data['sede_id']);
      $data['subtotal'] = 0;
      $data['discount_amount'] = 0;
      $data['tax_amount'] = 0;
      $data['total_amount'] = 0;
      $data['exchange_rate'] = $exchangeRate->rate;

      // Calculate validity days
      $quotation_date = Carbon::parse($data['quotation_date']);
      $expiration_date = Carbon::parse($data['expiration_date']);
      $validation_days = $quotation_date->diffInDays($expiration_date);
      $data['validity_days'] = $validation_days;

      //Obtenemos el kilometraje de su ultima OT
      $data['mileage'] = ApWorkOrder::where('vehicle_id', $data['vehicle_id'])->orderBy('created_at', 'desc')->first()?->vehicleInspection?->mileage ?? 0;

      $quotation = ApOrderQuotations::create($data);

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
      $vehicle = Vehicles::find($data['vehicle_id']);

      $exchangeRate = ExchangeRate::where('date', $date)->first();
      if (!$exchangeRate) {
        throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy.');
      }

      if ($vehicle->customer_id === null) {
        throw new Exception('El vehículo debe estar asociado a un "TITULAR" para crear una cotización');
      }

      if (auth()->check()) {
        $data['created_by'] = auth()->user()->id;
      }

      // Validar precios de venta al público para cada producto en details
      foreach ($data['details'] as $index => $detail) {
        $productId = $detail['product_id'];
        $unitPrice = $detail['unit_price'];
        $sedeId = $data['sede_id'];

        $validation = ProductWarehouseStock::validatePublicSalePrice(
          $productId,
          $sedeId,
          $unitPrice
        );

        if (!$validation['valid']) {
          throw new Exception(
            "Producto ({$detail['description']}): {$validation['message']}"
          );
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
        'quotation_number' => $this->generateNextQuotationNumber($data['sede_id']),
        'subtotal' => 0,
        'discount_percentage' => 0,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 0,
        'validity_days' => $validation_days,
        'exchange_rate' => $exchangeRate->rate,
        'currency_id' => $data['currency_id'],
        'supply_type' => $data['supply_type'] ?? null,
        'collection_date' => $data['collection_date'] ?? null,
      ];

      // Create quotation
      $quotation = ApOrderQuotations::create($quotationData);

      // Create details
      foreach ($data['details'] as $detail) {
        $quotation->details()->create([
          'item_type' => 'PRODUCT',
          'product_id' => $detail['product_id'],
          'description' => $detail['description'],
          'quantity' => $detail['quantity'],
          'unit_measure' => $detail['unit_measure'],
          'unit_price' => $detail['unit_price'],
          'discount_percentage' => $detail['discount_percentage'] ?? 0,
          'total_amount' => $detail['total_amount'],
          'observations' => $detail['observations'] ?? null,
          'retail_price_external' => $detail['retail_price_external'] ?? null,
          'exchange_rate' => $detail['exchange_rate'] ?? null,
          'freight_commission' => $detail['freight_commission'] ?? null,
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

    $additionalData = ['checkStock' => true];

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

      $exchangeRate = ExchangeRate::where('date', $date)->first();
      if (!$exchangeRate) {
        throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy.');
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
      $data['exchange_rate'] = $exchangeRate->rate;

      //Obtenemos el kilometraje de su ultima OT
      $data['mileage'] = ApWorkOrder::where('vehicle_id', $data['vehicle_id'])->orderBy('created_at', 'desc')->first()?->vehicleInspection?->mileage ?? 0;

      $quotation->update($data);

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
      $vehicle = $vehicleId ? Vehicles::find($vehicleId) : null;
      $date = Carbon::parse($data['quotation_date'])->format('Y-m-d');

      $exchangeRate = ExchangeRate::where('date', $date)->first();
      if (!$exchangeRate) {
        throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy.');
      }

      if ($quotation->status === ApOrderQuotations::STATUS_DESCARTADO) {
        throw new Exception('No se puede actualizar una cotización que ha sido descartada.');
      }

      if ($quotation->status !== ApOrderQuotations::STATUS_APERTURADO) {
        throw new Exception('Solo se pueden editar cotizaciones en estado "Aperturado".');
      }

      if ($quotation->has_invoice_generated) {
        throw new Exception('No se puede actualizar una cotización que ya tiene una factura generada.');
      }

      if ($vehicle && $vehicle->customer_id === null) {
        throw new Exception('El vehículo debe estar asociado a un "TITULAR" para actualizar una cotización');
      }

      // Validar cambio de moneda si existen pagos registrados
      if ($quotation->has_invoice_generated && $quotation->currency_id !== $data['currency_id']) {
        throw new Exception('No se puede cambiar el tipo de moneda porque ya existen pagos registrados para esta cotización.');
      }

      // Validar precios de venta al público para cada producto en details
      foreach ($data['details'] as $index => $detail) {
        $productId = $detail['product_id'];
        $unitPrice = $detail['unit_price'];
        $sedeId = $data['sede_id'];

        $validation = ProductWarehouseStock::validatePublicSalePrice(
          $productId,
          $sedeId,
          $unitPrice
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
        'exchange_rate' => $exchangeRate->rate,
        'collection_date' => $data['collection_date'] ?? null,
      ]);

      // Delete existing details
      $quotation->details()->delete();

      // Create new details
      foreach ($data['details'] as $detail) {
        $quotation->details()->create([
          'item_type' => 'PRODUCT',
          'product_id' => $detail['product_id'],
          'description' => $detail['description'],
          'quantity' => $detail['quantity'],
          'unit_measure' => $detail['unit_measure'],
          'unit_price' => $detail['unit_price'],
          'discount_percentage' => $detail['discount_percentage'] ?? $detail['discount'] ?? 0,
          'total_amount' => $detail['total_amount'],
          'observations' => $detail['observations'] ?? null,
          'retail_price_external' => $detail['retail_price_external'] ?? null,
          'exchange_rate' => $detail['exchange_rate'] ?? null,
          'freight_commission' => $detail['freight_commission'] ?? null,
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

    if ($quotation->has_invoice_generated) {
      throw new Exception('No se puede eliminar una cotización que ya tiene una factura generada.');
    }

    if ($quotation->status === ApOrderQuotations::STATUS_DESCARTADO) {
      throw new Exception('No se puede eliminar una cotización que ha sido descartada.');
    }

    if ($quotation->is_take === true) {
      throw new Exception('No se puede eliminar una cotización que ya ha sido tomada en una solicitud de compra / OT.');
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
      $quotation->delete();
    });

    return response()->json(['message' => 'Cotización eliminada correctamente']);
  }

  public function discard(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $quotation = $this->find($data['id']);

      if ($quotation->has_invoice_generated) {
        throw new Exception('No se puede descartar una cotización que ya tiene una factura generada.');
      }

      if ($quotation->discarded_at) {
        throw new Exception('Esta cotización ya ha sido descartada previamente.');
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

  /**
   * Genera el siguiente número de cotización en formato COT-{dyn_code}-{YYYYMM}{XXXX}
   *
   * @param int $sedeId
   * @return string
   */
  public function generateNextQuotationNumber(int $sedeId): string
  {
    $sede = Sede::find($sedeId);
    if (!$sede) {
      throw new Exception('Sede no encontrada');
    }

    $dynCode = $sede->dyn_code;
    $year = date('Y');
    $month = date('m');
    $prefix = "COT-{$dynCode}-{$year}{$month}";

    $lastQuotation = ApOrderQuotations::withTrashed()
      ->where('quotation_number', 'like', "{$prefix}%")
      ->orderBy('quotation_number', 'desc')
      ->first();

    if ($lastQuotation) {
      $lastNumber = (int)substr($lastQuotation->quotation_number, -4);
      $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    } else {
      $newNumber = '0001';
    }

    return "{$prefix}{$newNumber}";
  }

  public function generateQuotationPDF($id, $showCodes = true)
  {
    $quotation = ApOrderQuotations::with([
      'vehicle.model.family.brand',
      'vehicle.color',
      'vehicle.customer.district',
      'createdBy',
      'details.product'
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
      $data['advisor_phone'] = $quotation->createdBy->person->cel_personal ?? 'N/A';
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
        'total_amount' => $detail->total_amount,
        'item_type' => $detail->item_type,
        'supply_type' => $detail->supply_type,
      ];
    });

    // Calcular totales correctamente
    $totalLabor = 0;
    $totalParts = 0;
    $totalDiscounts = 0;

    foreach ($quotation->details as $detail) {
      $itemSubtotal = $detail->quantity * $detail->unit_price;
      $itemDiscount = $itemSubtotal - $detail->total_amount;

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
      'supply_type' => $quotation->supply_type,
      'status' => $quotation->status,
    ];

    // Datos del cliente
    $data['customer_name'] = $quotation->client->full_name ?? 'N/A';
    $data['customer_document'] = $quotation->client->num_doc ?? 'N/A';
    $data['customer_address'] = $quotation->client->direction ?? 'N/A';
    $data['customer_district'] = $quotation->client->district ? $quotation->client->district->name : 'N/A';
    $data['customer_email'] = $quotation->client->email ?? 'N/A';
    $data['customer_phone'] = $quotation->client->phone ?? 'N/A';

    // Datos del asesor
    if ($quotation->createdBy) {
      $data['advisor_name'] = $quotation->createdBy->person->nombre_completo ?? 'N/A';
      $data['advisor_phone'] = $quotation->createdBy->person->cel_personal ?? 'N/A';
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
      $data['vehicle_model'] = $vehicle->model ? $vehicle->model->version : 'N/A';
      $data['vehicle_brand'] = $vehicle->model && $vehicle->model->family && $vehicle->model->family->brand
        ? $vehicle->model->family->brand->name
        : 'N/A';
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
        'total_amount' => $detail->total_amount,
        'total_amount_with_tax' => round($detail->total_amount * (1 + Constants::VAT_TAX / 100), 2),
        'item_type' => $detail->item_type,
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

      if ($quotation->has_invoice_generated) {
        throw new Exception('No se puede confirmar una cotización que ya tiene una factura generada.');
      }

      if ($quotation->status === ApOrderQuotations::STATUS_POR_FACTURAR) {
        throw new Exception('Esta cotización ya ha sido confirmada previamente.');
      }

      // Procesar firma del cliente si existe
      if (isset($data['customer_signature'])) {
        $this->processCustomerSignature($quotation, $data['customer_signature']);
      }

      // Cambiar el estado a "Por Facturar"
      $quotation->update([
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
   * - Jefe de Taller (143) → chief_approval_by
   * - Gerente de Taller (142) → manager_approval_by
   */
  public function approve($data)
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
          throw new Exception('Solo los Gerentes de Taller pueden aprobar.');
        }
        if ($quotation->manager_approval_by) {
          throw new Exception('Esta cotización ya fue aprobada por el Gerente de Taller.');
        }
        $quotation->update(['manager_approval_by' => $user->id]);
      }

      // Validar aprobación de jefe
      if (isset($data['chief_approval_by'])) {
        if (!in_array($positionId, Position::POSITION_JEFE_PVT_IDS)) {
          throw new Exception('Solo los Jefes de Taller pueden aprobar.');
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
        $query->whereIn('cargo_id', Position::POSITION_JEFE_PVT_IDS)
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
            'total_amount' => $detail->total_amount,
            'item_type' => $detail->item_type,
            'observations' => $detail->observations ?? '',
          ];
        }),

        // URL del frontend
        'button_url' => config('app.frontend_url') . '/ap/post-venta/taller/cotizacion-taller/aprobar/' . $quotation->id,
      ];

      $subject = 'Nueva Cotización Solicitada por Gerencia - ' . $quotation->quotation_number;
      $emailsSentCount = 0;

      // Enviar correo a los Jefes de Taller
      foreach ($chiefUsers as $chief) {
        if ($chief->person && $chief->person->email2) {
          try {
            $this->emailService->queue([
              'to' => $chief->person->email2,
              'subject' => $subject,
              'template' => 'emails.quotation-notification',
              'data' => array_merge($emailData, [
                'recipient_name' => $chief->person->nombre_completo ?? 'Jefe de Taller',
                'recipient_role' => 'Jefe de Taller',
              ]),
            ]);
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
}
