<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApOrderQuotationsResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Http\Utils\Constants;
use App\Http\Utils\Helpers;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\gp\gestionsistema\DigitalFile;
use App\Models\gp\maestroGeneral\ExchangeRate;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApOrderQuotationsService extends BaseService implements BaseServiceInterface
{
  protected DigitalFileService $digitalFileService;

  // Configuración de rutas para archivos
  private const FILE_PATHS = [
    'customer_signature' => '/ap/postventa/taller/cotizaciones/firmas-cliente/',
  ];

  public function __construct(DigitalFileService $digitalFileService)
  {
    $this->digitalFileService = $digitalFileService;
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

      $data['quotation_number'] = $this->generateNextQuotationNumber();
      $data['subtotal'] = 0;
      $data['discount_amount'] = 0;
      $data['tax_amount'] = Constants::VAT_TAX;
      $data['total_amount'] = 0;
      $data['exchange_rate'] = $exchangeRate->rate;

      // Calculate validity days
      $quotation_date = Carbon::parse($data['quotation_date']);
      $expiration_date = Carbon::parse($data['expiration_date']);
      $validation_days = $quotation_date->diffInDays($expiration_date);
      $data['validity_days'] = $validation_days;

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

      // Calculate validity days
      $quotation_date = Carbon::parse($data['quotation_date']);
      $expiration_date = Carbon::parse($data['expiration_date']);
      $validation_days = $quotation_date->diffInDays($expiration_date);

      // Calculate totals from details
      $subtotal = 0;
      $discount_amount = 0;

      foreach ($data['details'] as $detail) {
        $itemSubtotal = $detail['quantity'] * $detail['unit_price'];
        $itemDiscount = isset($detail['discount']) ? ($itemSubtotal * $detail['discount'] / 100) : 0;

        $subtotal += $itemSubtotal;
        $discount_amount += $itemDiscount;
      }

      $total_amount = $subtotal - $discount_amount;

      // Prepare quotation data
      $quotationData = [
        'area_id' => $data['area_id'],
        'vehicle_id' => $data['vehicle_id'],
        'sede_id' => $data['sede_id'],
        'quotation_date' => $data['quotation_date'],
        'expiration_date' => $data['expiration_date'],
        'observations' => $data['observations'] ?? null,
        'created_by' => $data['created_by'],
        'quotation_number' => $this->generateNextQuotationNumber(),
        'subtotal' => $subtotal,
        'discount_amount' => $discount_amount,
        'tax_amount' => Constants::VAT_TAX,
        'total_amount' => $total_amount,
        'validity_days' => $validation_days,
        'exchange_rate' => $exchangeRate->rate,
        'currency_id' => $data['currency_id'],
        'supply_type' => $data['supply_type'] ?? null,
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
          'discount' => $detail['discount'] ?? 0,
          'total_amount' => $detail['total_amount'],
          'observations' => $detail['observations'] ?? null,
          'retail_price_external' => $detail['retail_price_external'] ?? null,
          'exchange_rate' => $detail['exchange_rate'] ?? null,
          'freight_commission' => $detail['freight_commission'] ?? null,
        ]);
      }

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
    return (new ApOrderQuotationsResource($quotation))->additional(['checkStock' => true]);
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

      // Calculate validity days
      $quotation_date = Carbon::parse($data['quotation_date']);
      $expiration_date = Carbon::parse($data['expiration_date']);
      $validation_days = $quotation_date->diffInDays($expiration_date);
      $data['validity_days'] = $validation_days;
      $data['exchange_rate'] = $exchangeRate->rate;

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
      $vehicle = Vehicles::find($data['vehicle_id']);
      $date = Carbon::parse($data['quotation_date'])->format('Y-m-d');

      $exchangeRate = ExchangeRate::where('date', $date)->first();
      if (!$exchangeRate) {
        throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy.');
      }

      if ($quotation->status === ApOrderQuotations::STATUS_DESCARTADO) {
        throw new Exception('No se puede actualizar una cotización que ha sido descartada.');
      }

      if ($quotation->status !== ApOrderQuotations::STATUS_APERTURADO) {
        throw new Exception('Solo se pueden eliminar cotizaciones en estado "Aperturado".');
      }

      if ($quotation->has_invoice_generated) {
        throw new Exception('No se puede actualizar una cotización que ya tiene una factura generada.');
      }

      if ($vehicle->customer_id === null) {
        throw new Exception('El vehículo debe estar asociado a un "TITULAR" para actualizar una cotización');
      }

      // Calculate validity days
      $quotation_date = Carbon::parse($data['quotation_date']);
      $expiration_date = Carbon::parse($data['expiration_date']);
      $validation_days = $quotation_date->diffInDays($expiration_date);

      // Calculate totals from details
      $subtotal = 0;
      $discount_amount = 0;

      foreach ($data['details'] as $detail) {
        $itemSubtotal = $detail['quantity'] * $detail['unit_price'];
        $itemDiscount = isset($detail['discount']) ? ($itemSubtotal * $detail['discount'] / 100) : 0;

        $subtotal += $itemSubtotal;
        $discount_amount += $itemDiscount;
      }

      $total_amount = $subtotal - $discount_amount;

      // Update quotation data
      $quotation->update([
        'area_id' => $data['area_id'],
        'vehicle_id' => $data['vehicle_id'],
        'sede_id' => $data['sede_id'],
        'quotation_date' => $data['quotation_date'],
        'expiration_date' => $data['expiration_date'],
        'observations' => $data['observations'] ?? null,
        'subtotal' => $subtotal,
        'discount_amount' => $discount_amount,
        'tax_amount' => Constants::VAT_TAX,
        'total_amount' => $total_amount,
        'validity_days' => $validation_days,
        'currency_id' => $data['currency_id'],
        'exchange_rate' => $exchangeRate->rate,
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
          'discount' => $detail['discount'] ?? 0,
          'total_amount' => $detail['total_amount'],
          'observations' => $detail['observations'] ?? null,
          'retail_price_external' => $detail['retail_price_external'] ?? null,
          'exchange_rate' => $detail['exchange_rate'] ?? null,
          'freight_commission' => $detail['freight_commission'] ?? null,
        ]);
      }

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
   * Genera el siguiente número de cotización en formato COT-YYYY-MM-XXXX
   *
   * @return string
   */
  public function generateNextQuotationNumber(): string
  {
    $year = date('Y');
    $month = date('m');

    $lastQuotation = ApOrderQuotations::withTrashed()
      ->where('quotation_number', 'like', "COT-{$year}-{$month}-%")
      ->orderBy('quotation_number', 'desc')
      ->first();

    if ($lastQuotation) {
      $lastNumber = (int)substr($lastQuotation->quotation_number, -4);
      $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    } else {
      $newNumber = '0001';
    }

    return "COT-{$year}-{$month}-{$newNumber}";
  }

  public function generateQuotationPDF($id)
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
    ];

    // Datos del cliente
    if ($quotation->vehicle && $quotation->vehicle->customer) {
      $customer = $quotation->vehicle->customer;
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
      $data['advisor_email'] = $quotation->createdBy->person->email ?? 'N/A';
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
      $data['vehicle_km'] = 0;
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
    $data['details'] = $details->map(function ($detail) {
      return [
        'code' => $detail->product ? $detail->product->code : '-',
        'description' => $detail->description,
        'observations' => $detail->observations ?? '',
        'quantity' => $detail->quantity,
        'unit_price' => $detail->unit_price,
        'discount' => $detail->discount,
        'total_amount' => $detail->total_amount,
        'item_type' => $detail->item_type,
      ];
    });

    // Calcular totales según el parámetro with_labor
    $totalLabor = $quotation->details->where('item_type', 'labor')->sum('total_amount');
    $totalParts = $quotation->details->where('item_type', 'part')->sum('total_amount');
    $totalDiscounts = $quotation->details->sum('discount');

    // Recalcular subtotal y total si no se incluye mano de obra
    $subtotal = $quotation->subtotal;
    $total_amount = $quotation->total_amount;

    $data['total_labor'] = $totalLabor;
    $data['total_parts'] = $totalParts;
    $data['total_discounts'] = $totalDiscounts;
    $data['subtotal'] = $subtotal;
    $data['tax_amount'] = $quotation->tax_amount;
    $data['total_amount'] = $total_amount;

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
    if ($quotation->vehicle && $quotation->vehicle->customer) {
      $customer = $quotation->vehicle->customer;
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
      $data['advisor_email'] = $quotation->createdBy->person->email ?? 'N/A';
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
        'observations' => $detail->observations ?? '',
        'quantity' => $detail->quantity,
        'unit_price' => $detail->unit_price,
        'discount' => $detail->discount,
        'total_amount' => $detail->total_amount,
        'item_type' => $detail->item_type,
      ];
    });

    // Calcular totales solo con repuestos
    $totalParts = $repuestosDetails->sum('total_amount');
    $totalDiscounts = $repuestosDetails->sum(function ($detail) {
      return ($detail->quantity * $detail->unit_price) * ($detail->discount / 100);
    });

    // Calcular subtotal con repuestos
    $subtotal = $repuestosDetails->sum(function ($detail) {
      return $detail->quantity * $detail->unit_price;
    });

    // Total después de descuentos
    $total_amount = $subtotal - $totalDiscounts;

    $data['total_parts'] = $totalParts;
    $data['total_discounts'] = $totalDiscounts;
    $data['subtotal'] = $subtotal;
    $data['tax_amount'] = $quotation->tax_amount;
    $data['total_amount'] = $total_amount;

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

  /**
   * Confirma una cotización guardando la firma del cliente y cambiando el estado a "Por Facturar"
   */
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
