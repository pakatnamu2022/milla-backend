<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\PurchaseRequestQuoteResource;
use App\Http\Resources\ap\comercial\VehiclesResource;
use App\Http\Resources\ap\facturacion\ElectronicDocumentResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\gp\gestionhumana\personal\WorkerService;
use App\Http\Utils\Constants;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\DetailsApprovedAccessoriesQuote;
use App\Models\ap\comercial\DiscountCoupons;
use App\Models\ap\comercial\PurchaseRequestQuote;
use App\Models\ap\configuracionComercial\venta\ApAssignmentLeadership;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\repuestos\ApprovedAccessories;
use App\Models\gp\maestroGeneral\ExchangeRate;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use PHPUnit\TextUI\Configuration\Constant;
use Throwable;
use function dd;
use function json_encode;

class PurchaseRequestQuoteService extends BaseService implements BaseServiceInterface
{
  /**
   * @throws Exception
   */
  public function list(Request $request): JsonResponse
  {
    $workerService = new WorkerService();
    $worker = $workerService->getAuthenticatedWorkerWithArea();
    $purchaseRequestQuoteQuery = $this->getPurchaseRequestQuoteQuery($worker, $request);

//    throw new Exception(json_encode($purchaseRequestQuoteQuery->pluck('id')->toArray()));

    return $this->getFilteredResults(
      $purchaseRequestQuoteQuery,
      $request,
      PurchaseRequestQuote::filters,
      PurchaseRequestQuote::sorts,
      PurchaseRequestQuoteResource::class,
    );
  }

  /**
   * Get purchase request quote query based on worker role and assignments
   * @param mixed $worker
   * @param Request $request
   * @return string|\Illuminate\Database\Eloquent\Builder
   * @throws Exception
   */
  private function getPurchaseRequestQuoteQuery($worker, Request $request)
  {
    $user = $request->user();
//    throw new Exception($user->role->id);
    // Si es del área de TICS, ver todo
    if ($user->role->id === Constants::TICS_ROL_ID) {
      return PurchaseRequestQuote::class;
    }

    if ($worker->position->hierarchicalCategory->id === Constants::SALE_COORDINATOR_CATEGORY_ID) {
      $sedes = $user->sedes()->pluck('config_sede.id')->toArray();
      return PurchaseRequestQuote::whereIn('sede_id', $sedes);
    }

    // Buscar si el trabajador es jefe (tiene consultores asignados en ApAssignmentLeadership)
    $consultantIds = $this->getAllConsultantIds($worker->id);

    // Si tiene consultores asignados, mostrar las quotes de esos consultores
    if ($consultantIds->isNotEmpty()) {
      return PurchaseRequestQuote::query()
        ->whereHas('opportunity', function ($query) use ($consultantIds) {
          $query->whereIn('worker_id', $consultantIds);
        });
    }

    // Por defecto, mostrar solo las quotes del propio trabajador
    return PurchaseRequestQuote::query()
      ->whereHas('opportunity', function ($query) use ($worker) {
        $query->where('worker_id', $worker->id);
      });
  }

  /**
   * Get all consultant IDs assigned to a boss across all months/years
   * @param int $bossId
   * @return \Illuminate\Support\Collection
   */
  private function getAllConsultantIds(int $bossId)
  {
    return ApAssignmentLeadership::where('boss_id', $bossId)
      ->distinct()
      ->pluck('worker_id');
  }

  public function find($id)
  {
    $PurchaseRequestQuote = PurchaseRequestQuote::where('id', $id)->first();
    if (!$PurchaseRequestQuote) {
      throw new Exception('Registro no encontrado');
    }
    return $PurchaseRequestQuote;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      // Obtener el exchange_rate_id según la moneda del documento
      $exchangeRateId = $this->getExchangeRateId($data['doc_type_currency_id']);

      // Generar el correlativo
      $correlative = $this->nextCorrelativeField(PurchaseRequestQuote::class, 'correlative', 8);

      // Preparar datos para crear el PurchaseRequestQuote
      $quoteData = [
        'correlative' => $correlative,
        'type_document' => $data['type_document'],
        'opportunity_id' => $data['opportunity_id'],
        'comment' => $data['comment'] ?? null,
        'warranty' => $data['warranty'] ?? null,
        'holder_id' => $data['holder_id'],
        'vehicle_color_id' => $data['vehicle_color_id'],
        'ap_models_vn_id' => $data['ap_models_vn_id'],
        'ap_vehicle_id' => $data['ap_vehicle_id'] ?? null,
        'type_currency_id' => $data['type_currency_id'],
        'doc_type_currency_id' => $data['doc_type_currency_id'],
        'exchange_rate_id' => $exchangeRateId,
        'base_selling_price' => $data['base_selling_price'],
        'sale_price' => $data['sale_price'],
        'doc_sale_price' => $data['doc_sale_price'],
        'sede_id' => $data['sede_id'] ?? null,
      ];

      // Crear el registro principal
      $purchaseRequestQuote = PurchaseRequestQuote::create($quoteData);

      // Guardar bonus_discounts en DiscountCoupons
      if (isset($data['bonus_discounts']) && is_array($data['bonus_discounts'])) {
        $this->saveBonusDiscounts($purchaseRequestQuote->id, $data['bonus_discounts'], $data['sale_price']);
        // Aplicar descuentos negativos al sale_price
        $this->applyNegativeDiscounts($purchaseRequestQuote->id);
      }

      // Guardar accessories en DetailsApprovedAccessoriesQuote
      if (isset($data['accessories']) && is_array($data['accessories'])) {
        $this->saveAccessories($purchaseRequestQuote->id, $data['accessories']);
      }

      return new PurchaseRequestQuoteResource($purchaseRequestQuote);
    });
  }

  public function show($id)
  {
    return new PurchaseRequestQuoteResource($this->find($id));
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $purchaseRequestQuote = $this->find($data['id']);

      // Si se actualiza la moneda del documento, actualizar el exchange_rate_id
      if (isset($data['doc_type_currency_id'])) {
        $data['exchange_rate_id'] = $this->getExchangeRateId($data['doc_type_currency_id']);
      }

      // Actualizar el registro principal
      $purchaseRequestQuote->update($data);

      // Si se envían bonus_discounts, reemplazar los existentes
      if (isset($data['bonus_discounts'])) {
        // Eliminar los descuentos existentes
        DiscountCoupons::where('purchase_request_quote_id', $purchaseRequestQuote->id)->delete();

        // Crear los nuevos descuentos si el array no está vacío
        if (is_array($data['bonus_discounts']) && count($data['bonus_discounts']) > 0) {
          $salePrice = $data['sale_price'];
          $this->saveBonusDiscounts($purchaseRequestQuote->id, $data['bonus_discounts'], $salePrice);
          // Aplicar descuentos negativos al sale_price
          $this->applyNegativeDiscounts($purchaseRequestQuote->id);
        }
      }

      // Si se envían accessories, reemplazar los existentes
      if (isset($data['accessories'])) {
        // Eliminar los accesorios existentes
        DetailsApprovedAccessoriesQuote::where('purchase_request_quote_id', $purchaseRequestQuote->id)->delete();

        // Crear los nuevos accesorios si el array no está vacío
        if (is_array($data['accessories']) && count($data['accessories']) > 0) {
          $this->saveAccessories($purchaseRequestQuote->id, $data['accessories']);
        }
      }

      return new PurchaseRequestQuoteResource($purchaseRequestQuote->fresh());
    });
  }


  public function assignVehicle(mixed $data): JsonResource
  {
    DB::beginTransaction();
    try {
      //ap_vehicle_id
      $purchaseRequestQuote = $this->find($data['id']);
      $purchaseRequestQuote->update($data);
      DB::commit();
      return PurchaseRequestQuoteResource::make($purchaseRequestQuote);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function unassignVehicle(int $id): JsonResource
  {
    DB::beginTransaction();
    try {
      $purchaseRequestQuote = $this->find($id);
      $vehicle = $purchaseRequestQuote->vehicle;
      if (!$vehicle) {
        throw new Exception('No hay un vehículo asignado a esta cotización.');
      }

      $electronicDocuments = $vehicle->electronicDocuments()
        ->where('status', ElectronicDocument::STATUS_ACCEPTED)
        ->where('aceptada_por_sunat', 1)
        ->where('anulado', 0)
        ->whereNull('ap_billing_electronic_documents.deleted_at');

      if ($electronicDocuments->exists()) {
        throw new Exception('No se puede desasignar el vehículo porque tiene documentos electrónicos aceptados asociados.'
          . $electronicDocuments->get()->pluck('serie', 'numero')->map(function ($serie, $numero) {
            return " {$serie}-{$numero} ";
          })->implode(', '));
      }

      $purchaseRequestQuote->ap_vehicle_id = null;
      $purchaseRequestQuote->save();

      $movementService = new VehicleMovementService();
      $isInInventory = $vehicle->vehicleMovements()
        ->where('ap_vehicle_status_id', ApVehicleStatus::INVENTARIO_VN)
        ->whereNull('deleted_at')
        ->exists();

      $isInTransit = $vehicle->vehicleMovements()
        ->where('ap_vehicle_status_id', ApVehicleStatus::VEHICULO_EN_TRAVESIA)
        ->whereNull('deleted_at')
        ->exists();

      if ($isInInventory) {
        // Registrar movimiento de regreso a inventario
        $movementService->storeInventoryVehicleMovement($vehicle->id);
      } elseif ($isInTransit) {
        $movementService->storeInTransitVehicleMovement($vehicle->id);
      }

      $purchaseRequestQuote->desactivate();
      $opportunity = $purchaseRequestQuote->opportunity;
      $opportunityService = new OpportunityService();
      $opportunityService->close($opportunity->id, 'Cierre automático al desasignar vehículo de cotización de solicitud de compra.');

      DB::commit();
      return PurchaseRequestQuoteResource::make($purchaseRequestQuote);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    } catch (Throwable $e) {
      DB::rollBack();
      throw $e;
    }
  }


  public function destroy($id)
  {
    $PurchaseRequestQuote = $this->find($id);
    DB::transaction(function () use ($PurchaseRequestQuote) {
      $PurchaseRequestQuote->delete();
    });
    return response()->json(['message' => 'Registro eliminado correctamente']);
  }

  public function generateReportPDF($data)
  {
    $purchaseRequestQuote = $this->find($data['id']);
    $dataResource = new PurchaseRequestQuoteResource($purchaseRequestQuote);
    $dataArray = $dataResource->resolve();
    $isPersonJuridica = $purchaseRequestQuote->opportunity->client->type_person_id === ApMasters::TYPE_PERSON_JURIDICA_ID;
    // Agregar datos adicionales directamente al array
    $dataArray['num_doc_client'] = $purchaseRequestQuote->opportunity->client->num_doc ?? null;
    $dataArray['birth_date'] = ($isPersonJuridica) ? '- / - / -' : ($purchaseRequestQuote->opportunity->client->birth_date ?? '- / - / -');
    $dataArray['marital_status'] = ($isPersonJuridica) ? '-' : ($purchaseRequestQuote->opportunity->client->maritalStatus->description ?? '-');
    $dataArray['spouse_full_name'] = ($isPersonJuridica) ? '-' : ($purchaseRequestQuote->opportunity->client->spouse_full_name ?? '-');
    $dataArray['spouse_num_doc'] = ($isPersonJuridica) ? '-' : ($purchaseRequestQuote->opportunity->client->spouse_num_doc ?? '-');
    $dataArray['legal_representative'] = $purchaseRequestQuote->opportunity->client->legal_representative_full_name ?? '-';
    $dataArray['dni_legal_representative'] = $purchaseRequestQuote->opportunity->client->legal_representative_num_doc ?? '-';
    $dataArray['address'] = $purchaseRequestQuote->opportunity->client->direction ?? null;
    $dataArray['email'] = $purchaseRequestQuote->opportunity->client->email ?? null;
    $dataArray['phone'] = $purchaseRequestQuote->opportunity->client->phone ?? null;
    $dataArray['class'] = $purchaseRequestQuote->apModelsVn->classArticle->description ?? null;
    $dataArray['brand'] = $purchaseRequestQuote->apModelsVn->family->brand->name ?? null;
    $dataArray['engine_number'] = $purchaseRequestQuote->vehiclePurchaseOrders->engine_number ?? null;
    $dataArray['vin'] = $purchaseRequestQuote->vehiclePurchaseOrders->vin ?? null;
    $dataArray['model_year'] = $purchaseRequestQuote->apModelsVn->model_year ?? null;
    $dataArray['selling_price_soles'] = round($purchaseRequestQuote->sale_price * ($purchaseRequestQuote->exchangeRate->rate ?? 1), 2);

    // Definir el título según el type_document
    $dataArray['document_title'] = ($purchaseRequestQuote->type_document === 'COTIZACION')
      ? 'COTIZACIÓN'
      : 'SOLICITUD DE COMPRA';

    // Obtener bancos filtrados por sede_id con account_number
    $banks = \App\Models\ap\configuracionComercial\venta\ApBank::with(['bank', 'currency'])
      ->where('sede_id', $purchaseRequestQuote->sede_id)
      ->where('status', 1)
      ->whereNotNull('account_number')
      ->where('account_number', '!=', '')
      ->orderBy('bank_id')
      ->orderBy('currency_id')
      ->get();

    $pdf = PDF::loadView('reports.ap.comercial.request-purchase-quote', ['quote' => $dataArray, 'banks' => $banks]);

    // Configurar PDF
    $pdf->setOptions([
      'defaultFont' => 'Arial',
      'isHtml5ParserEnabled' => true,
      'isRemoteEnabled' => false,
      'dpi' => 96,
    ]);

    return $pdf;
  }

  /**
   * Obtiene el exchange_rate_id según la moneda del documento
   */
  private function getExchangeRateId($docTypeCurrencyId)
  {
    // Si la moneda es PEN (Sol Peruano), no necesita exchange_rate
    if ($docTypeCurrencyId == TypeCurrency::PEN_ID) {
      return null;
    }

    // Para USD, buscar el exchange_rate de hoy de PEN a USD
    if ($docTypeCurrencyId == TypeCurrency::USD_ID) {
      $exchangeRate = ExchangeRate::todayUSD();
      if (!$exchangeRate) {
        throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy.');
      }
      return $exchangeRate->id;
    }

    // Para EUR u otras monedas, buscar el exchange_rate de hoy
    $exchangeRate = ExchangeRate::where('from_currency_id', TypeCurrency::PEN_ID)
      ->where('to_currency_id', $docTypeCurrencyId)
      ->where('date', date('Y-m-d'))
      ->where('type', ExchangeRate::TYPE_VENTA)
      ->orderBy('created_at', 'desc')
      ->first();

    if (!$exchangeRate) {
      throw new Exception('No se ha registrado la tasa de cambio para la moneda seleccionada en la fecha de hoy.');
    }

    return $exchangeRate->id;
  }

  /**
   * Guarda los bonus_discounts en la tabla DiscountCoupons
   */
  private function saveBonusDiscounts($purchaseRequestQuoteId, $bonusDiscounts, $salePrice)
  {
    foreach ($bonusDiscounts as $discount) {
      $percentage = 0;
      $amount = 0;
      $precioUnitario = 0;
      $valorUnitario = 0;
      $igv = 0;

      if ($discount['type'] === 'FIJO') {
        // Si es monto fijo, guardar en amount y calcular el porcentaje
        $amount = $discount['value'];
        $percentage = ($salePrice > 0) ? ($amount / $salePrice) * 100 : 0;

        // Calcular IGV y valores unitarios
        $precioUnitario = $amount; // El valor ingresado ya incluye IGV
        $valorUnitario = $precioUnitario / 1.18;
        $igv = $precioUnitario - $valorUnitario;
      } elseif ($discount['type'] === 'PORCENTAJE') {
        // Si es porcentaje, guardar en percentage y calcular el monto
        $percentage = $discount['value'];
        $amount = ($salePrice * $percentage) / 100;

        // Calcular IGV y valores unitarios
        $precioUnitario = $amount; // El monto calculado incluye IGV
        $valorUnitario = $precioUnitario / 1.18;
        $igv = $precioUnitario - $valorUnitario;
      }

      DiscountCoupons::create([
        'description' => $discount['description'],
        'type' => $discount['type'],
        'percentage' => $percentage,
        'amount' => $amount,
//        'igv' => $igv,
        'valor_unitario' => $valorUnitario,
        'precio_unitario' => $precioUnitario,
        'is_negative' => $discount['is_negative'] ?? false,
        'concept_code_id' => $discount['concept_id'],
        'purchase_request_quote_id' => $purchaseRequestQuoteId,
      ]);
    }
  }

  /**
   * Aplica los descuentos negativos al sale_price del PurchaseRequestQuote
   */
  private function applyNegativeDiscounts($purchaseRequestQuoteId)
  {
    // Obtener todos los descuentos con is_negative = true
    $negativeDiscounts = DiscountCoupons::where('purchase_request_quote_id', $purchaseRequestQuoteId)
      ->where('is_negative', true)
      ->get();

    // Si no hay descuentos negativos, no hacer nada
    if ($negativeDiscounts->isEmpty()) {
      return;
    }

    // Sumar todos los montos de descuentos negativos
    $totalDiscount = $negativeDiscounts->sum('precio_unitario');

    // Obtener el PurchaseRequestQuote y actualizar el sale_price
    $purchaseRequestQuote = PurchaseRequestQuote::find($purchaseRequestQuoteId);
    $newSalePrice = $purchaseRequestQuote->sale_price - $totalDiscount;

    $purchaseRequestQuote->update(['sale_price' => $newSalePrice]);
  }

  /**
   * Guarda los accesorios en la tabla DetailsApprovedAccessoriesQuote
   */
  private function saveAccessories($purchaseRequestQuoteId, $accessories)
  {
    foreach ($accessories as $accessory) {
      // Obtener el accesorio aprobado para obtener su precio y moneda
      $approvedAccessory = ApprovedAccessories::find($accessory['accessory_id']);

      if (!$approvedAccessory) {
        throw new Exception('Accesorio con ID ' . $accessory['accessory_id'] . ' no encontrado.');
      }

      $type = $accessory['type'];
      $quantity = $accessory['quantity'];
      $price = $approvedAccessory->price;
      $total = $quantity * $price;

      DetailsApprovedAccessoriesQuote::create([
        'approved_accessory_id' => $accessory['accessory_id'],
        'type' => $type,
        'quantity' => $quantity,
        'price' => $price,
        'total' => $total,
        'purchase_request_quote_id' => $purchaseRequestQuoteId,
      ]);
    }
  }

  /**
   * Obtiene las facturas (documentos electrónicos) asociadas a una cotización de solicitud de compra
   * @param int $purchaseRequestQuoteId
   * @return \Illuminate\Http\JsonResponse
   * @throws Exception
   */
  public function getInvoices(int $purchaseRequestQuoteId)
  {
    $purchaseRequestQuote = $this->find($purchaseRequestQuoteId);

    // Obtener los documentos electrónicos con sus relaciones
    $documents = $purchaseRequestQuote->electronicDocuments()
      ->with([
        'documentType',
        'transactionType',
        'identityDocumentType',
        'currency',
        'vehicleMovement',
        'items',
        'creator'
      ])
      ->whereNull('credit_note_id')
      ->where(function ($query) use ($purchaseRequestQuoteId) {
        $query->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_FACTURA)
          ->orWhere('sunat_concept_document_type_id', ElectronicDocument::TYPE_BOLETA);
      })
      ->where('anulado', false)
      ->where('aceptada_por_sunat', true)
      ->orderBy('fecha_de_emision', 'desc')
      ->get();

    $vehicle = $purchaseRequestQuote->vehicle ?? null;

    return response()->json([
      'vehicle' => $vehicle ? VehiclesResource::make($vehicle) : null,
      'documents' => ElectronicDocumentResource::collection($documents),
      'total_documents' => $documents->count(),
      'total_amount' => $documents->sum('total'),
    ]);
  }
}
