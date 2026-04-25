<?php

namespace App\Http\Services\ap\facturacion;

use App\Http\Resources\ap\facturacion\ElectronicDocumentResource;
use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Http\Services\gp\maestroGeneral\ExchangeRateService;
use App\Jobs\SyncSalesDocumentJob;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\PurchaseRequestQuote;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\configuracionComercial\venta\ApAccountingAccountPlan;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\facturacion\ElectronicDocumentItem;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\ap\ApMasters;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\GeneralMaster;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use App\Http\Utils\Constants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ElectronicDocumentService extends BaseService implements BaseServiceInterface
{
  protected NubefactApiService $nubefactService;
  protected DigitalFileService $digitalFileService;

  public function __construct()
  {
    $this->nubefactService = new NubefactApiService();
    $this->digitalFileService = new DigitalFileService();
  }

  /**
   * Despacha manualmente el job de sincronización para un documento (útil para reintentar fallidos).
   *
   * Antes de despachar:
   * - Resetea a "pending" los logs que no estén completados.
   * - Si la cabecera (sales_document) ya existe en GPIN y está fallida,
   *   hace un UPDATE en la tabla intermedia para que GPIN la vuelva a procesar.
   */
  public function dispatchMigration(int $id): array
  {
    $document = ElectronicDocument::find($id);

    if (!$document) {
      throw new Exception('Documento electrónico no encontrado');
    }

    if ($document->migration_status === 'completed') {
      throw new Exception('El documento ya está sincronizado completamente');
    }

    $documentoId = $document->full_number;
    $resetActions = [];

    // Revisar los logs y preparar el terreno antes de redespachar
    $logs = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $id)->get();

    foreach ($logs as $log) {
      if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
        continue;
      }

      // Resetear el log a pending para que el job lo reintente limpiamente
      $log->update([
        'status' => VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
        'error_message' => null,
        'proceso_estado' => 0,
        'attempts' => 0,
      ]);

      $resetActions[] = "Log reseteado a pending: {$log->step}";
    }

    $document->update(['was_dyn_requested' => true]);

    SyncSalesDocumentJob::dispatch($document->id);

    return [
      'message' => "Job de sincronización despachado para el documento {$documentoId}",
      'resets' => $resetActions,
    ];
  }

  /**
   * Despacha jobs de migración para todos los documentos electrónicos no completados
   * y retorna un resumen con el motivo de cada despacho.
   */
  public function dispatchAll(): array
  {
    $dispatched = [];

    ElectronicDocument::whereNotIn('migration_status', [VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED])
      ->get()
      ->each(function (ElectronicDocument $doc) use (&$dispatched) {
        $logs = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $doc->id)->get();
        $reason = $this->buildDispatchAllReason($logs);

        $this->dispatchMigration($doc->id);

        $dispatched[] = [
          'id' => $doc->id,
          'number' => $doc->full_number,
          'migration_status' => $doc->migration_status,
          'reason' => $reason,
        ];
      });

    return [
      'total_dispatched' => count($dispatched),
      'dispatched' => $dispatched,
    ];
  }

  private function buildDispatchAllReason($logs): array
  {
    if ($logs->isEmpty()) {
      return [
        'type' => 'no_logs',
        'description' => 'Sin logs de migración — despacho inicial',
        'steps' => [],
      ];
    }

    $nonCompleted = $logs->filter(fn($log) => $log->status !== VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED);

    if ($nonCompleted->isEmpty()) {
      return [
        'type' => 'retry',
        'description' => 'Todos los pasos tienen logs — reintentando migración',
        'steps' => [],
      ];
    }

    $hasFailed = $nonCompleted->contains('status', VehiclePurchaseOrderMigrationLog::STATUS_FAILED);
    $hasPending = $nonCompleted->contains('status', VehiclePurchaseOrderMigrationLog::STATUS_PENDING);

    $steps = $nonCompleted->map(fn($log) => [
      'step' => $log->step,
      'status' => $log->status,
      'attempts' => $log->attempts,
      'error' => $log->error_message,
      'last_attempt_at' => $log->last_attempt_at?->format('Y-m-d H:i:s'),
    ])->values()->toArray();

    return [
      'type' => $hasFailed ? 'failed_steps' : ($hasPending ? 'pending_steps' : 'in_progress_steps'),
      'description' => $hasFailed
        ? 'Tiene pasos fallidos pendientes de reintento'
        : ($hasPending ? 'Tiene pasos pendientes de ejecutar' : 'Tiene pasos en progreso'),
      'steps' => $steps,
    ];
  }

  /**
   * Despacha un job de sincronización con deduplicación para evitar jobs duplicados
   * Usa cache de base de datos como lock para prevenir dispatch de jobs múltiples
   * para el mismo documento electrónico
   * @param int $electronicDocumentId
   * @return void
   */
  protected function dispatchJobWithDeduplication(int $electronicDocumentId): void
  {
    $cacheKey = "sync-doc-{$electronicDocumentId}";

    // Verificar si ya hay un job activo para este documento (lock existe)
    if (Cache::store('database')->has($cacheKey)) {
      // Ya hay un job activo, no despachar otro
      return;
    }

    // Marcar como activo (lock por 10 minutos = 600 segundos)
    // Este lock se limpiará automáticamente después de 10 minutos
    // Si el job termina antes, el lock persiste pero no afecta porque ya se procesó
    Cache::store('database')->put($cacheKey, true, 600);

    // Despachar job
    SyncSalesDocumentJob::dispatch($electronicDocumentId);
  }

  /**
   * List all electronic documents with filtering, sorting, and pagination
   */
  public function list(Request $request): JsonResponse
  {
    $user = $request->user();

    if ($user->role->id === Constants::TICS_ROL_ID) {
      $query = ElectronicDocument::class;
    } else {
      $sedes = $user->sedes()->pluck('config_sede.id')->toArray();
      $query = ElectronicDocument::whereHas('seriesModel', function ($q) use ($sedes) {
        $q->whereIn('sede_id', $sedes);
      });
    }

    return $this->getFilteredResults(
      $query,
      $request,
      ElectronicDocument::filters,
      ElectronicDocument::sorts,
      ElectronicDocumentResource::class,
      ['documentType', 'currency', 'identityDocumentType', 'items', 'creator']
    );
  }

  /**
   * Find a specific electronic document by ID
   * @throws Exception
   */
  public function find($id): ElectronicDocument
  {
    $document = ElectronicDocument::with([
      'documentType',
      'transactionType',
      'identityDocumentType',
      'currency',
      'items.igvType',
      'guides',
      'installments',
      'vehicleMovement',
      'creator',
      'updater'
    ])->withCount('referencingItems')->find($id);

    if (!$document) {
      throw new Exception('Documento electrónico no encontrado');
    }

    return $document;
  }

  public function show(int $id)
  {
    $electronicDocument = ElectronicDocument::find($id);
    return new ElectronicDocumentResource($electronicDocument);
  }

  /**
   * Get the next document number for a given type and series
   * @param string $documentType
   * @param string $series
   * @return int
   * @throws Exception
   */
  public function nextDocumentNumber(string $documentType, string $seriesCode): array
  {
    $assignSeries = AssignSalesSeries::where('series', $seriesCode)
      ->whereNull('deleted_at')
      ->first();

    // Sin whereNull('deleted_at'): los soft-deleted también cuentan
    // para evitar reusar números ya emitidos ante SUNAT
    $maxCorrelative = ElectronicDocument::where('sunat_concept_document_type_id', $documentType)
      ->where('serie', $seriesCode)
      ->max('numero');

    $correlativeNumber = $maxCorrelative !== null
      ? ((int)$maxCorrelative) + 1
      : $assignSeries->correlative_start + 1;

    $number = $this->completeNumber($correlativeNumber);
    return ["number" => $number];
  }

  /**
   * @param string $documentType
   * @param string $series
   * @return array[]|int[]
   */
  private function nextDocumentNumberCorrelative(string $documentType, string $series): array
  {
    $number = $this->nextDocumentNumber($documentType, $series);
    return ["number" => (int)$number["number"]];
  }

  /**
   * Create a new electronic document
   * @throws Exception
   * @throws Throwable
   */
  public function store(mixed $data): ElectronicDocumentResource
  {
    DB::beginTransaction();
    try {
      // ================================================================
      // 1. PREPARACIÓN INICIAL (común para todos)
      // ================================================================

      /**
       * Validar y calcular el siguiente número correlativo
       */
      $nextNumberData = $this->nextDocumentNumberCorrelative(
        $data['sunat_concept_document_type_id'],
        $data['serie']
      );
      $data['numero'] = $nextNumberData['number'];

      /**
       * Validar que la serie sea correcta
       */
      if (!ElectronicDocument::validateSerie($data['sunat_concept_document_type_id'], $data['serie'])) {
        throw new Exception('La serie no es válida para el tipo de documento seleccionado');
      }

      /**
       * Obtener la tasa de cambio y datos del cliente
       */
      $emissionDate = is_string($data['fecha_de_emision'])
        ? $data['fecha_de_emision']
        : Carbon::parse($data['fecha_de_emision'])->format('Y-m-d');

      $exchangeRate = (new ExchangeRateService())->getExchangeRate(TypeCurrency::USD_ID, $emissionDate);
      if (!$exchangeRate) {
        throw new Exception("No se ha registrado la tasa de cambio para la fecha de emisión ({$emissionDate}).");
      }

      $client = BusinessPartners::find($data['client_id']);
      $documentType = SunatConcepts::where('tribute_code', $client->document_type_id)
        ->where('type', SunatConcepts::TYPE_DOCUMENT)
        ->first();

      $data['sunat_concept_identity_document_type_id'] = $documentType->id;
      $data['cliente_numero_de_documento'] = $client->num_doc;
      $data['cliente_denominacion'] = $client->full_name . ($client->spouse_full_name ? ' - ' . $client->spouse_full_name : '');
      $data['cliente_direccion'] = $client->direction;
      $data['cliente_email'] = $client->email;
      $data['porcentaje_de_igv'] = $client->taxClassType->igv;
      $data['client_id'] = $client->id;
      $data['tipo_de_cambio'] = $exchangeRate->rate;

      // ================================================================
      // 2. VALIDACIONES POR TIPO DE ENTIDAD
      // ================================================================

      $this->validateAndEnrichForQuotation($data);
      $this->validateAndEnrichForWorkOrder($data);

      // ================================================================
      // 3. VALIDACIONES COMUNES
      // ================================================================

      /**
       * Validar que un anticipo no sea por 0 soles
       */
      if (isset($data['is_advance_payment']) && $data['is_advance_payment'] == 1) {
        $total = (float)($data['total'] ?? 0);
        if ($total <= 0) {
          throw new Exception('Un anticipo no puede ser por 0 soles. El total debe ser mayor a 0.');
        }
      }

      /**
       * Validar que para venta interna (is_advance_payment = 0) la suma de anticipos + monto factura = total entidad
       */
      $this->validateInternalSaleTotal($data);

      // ================================================================
      // 4. APLICAR LÓGICA DE DETRACCIONES
      // ================================================================
      $this->applyDetractionLogic($data);

      // ================================================================
      // 4.5. PROCESAR ARCHIVO DE ORDEN DE COMPRA SERVICIO (OPCIONAL)
      // ================================================================

      /**
       * Si el frontend envía un archivo para orden de compra de servicio,
       * subirlo a Digital Ocean y guardar la URL
       */
      if (isset($data['orden_compra_servicio_file']) && $data['orden_compra_servicio_file'] instanceof UploadedFile) {
        $file = $data['orden_compra_servicio_file'];
        $path = '/ap/facturacion/orden-compra-servicio/';
        $model = 'ap_billing_electronic_documents';

        // Subir archivo usando DigitalFileService
        $digitalFile = $this->digitalFileService->store($file, $path, 'public', $model);

        // Guardar la URL en el campo correspondiente
        $data['orden_compra_servicio_url'] = $digitalFile->url;

        // Remover el archivo del array para no guardarlo en la BD
        unset($data['orden_compra_servicio_file']);
      }

      // ================================================================
      // 5. CREACIÓN DEL DOCUMENTO Y RELACIONES
      // ================================================================

      /**
       * Crear el documento principal
       */

      $data = array_merge($data, [
        'exchange_rate_id' => $exchangeRate->id,
        'created_by' => auth()->id(),
        'status' => ElectronicDocument::STATUS_DRAFT,
      ]);

//      throw new Exception(json_encode($data));
      $document = ElectronicDocument::create($data);

      /**
       * Crear los items
       */
      if (isset($data['items']) && is_array($data['items'])) {
        // Enriquecer el campo `codigo` de cada item antes de crearlos
        if (!empty($data['order_quotation_id'])) {
          $this->enrichItemsCodigoFromQuotation($data['items'], (int)$data['order_quotation_id']);
        } elseif (!empty($data['work_order_id'])) {
          $this->enrichItemsCodigoFromWorkOrder($data['items'], (int)$data['work_order_id']);
        }

        $data['items'] = collect($data['items'])->sortBy('anticipo_regularizacion')->values()->all();
        foreach ($data['items'] as $index => $itemData) {
          $itemData['line_number'] = $index + 1;
          $document->items()->create($itemData);
        }
      }

      /**
       * Crear guías de remisión si existen
       */
      if (isset($data['guias']) && is_array($data['guias'])) {
        foreach ($data['guias'] as $guiaData) {
          $document->guides()->create($guiaData);
        }
      }

      /**
       * Crear cuotas si es venta al crédito
       */
      if (isset($data['venta_al_credito']) && is_array($data['venta_al_credito'])) {
        foreach ($data['venta_al_credito'] as $cuotaData) {
          $document->installments()->create($cuotaData);
        }
      }

      /**
       * Crear movimiento de vehículo si viene ap_vehicle_id
       */
      if (isset($data['ap_vehicle_id']) && $data['ap_vehicle_id']) {
        $vehicleMovement = $this->createVehicleMovement($data['ap_vehicle_id'], $document);

        // Actualizar el documento con el ID del movimiento
        $document->update([
          'ap_vehicle_movement_id' => $vehicleMovement->id
        ]);
      }

      // ================================================================
      // 6. POST-PROCESAMIENTO POR ENTIDAD
      // ================================================================

      $this->afterCreateForQuotation($document, $data);
      $this->afterCreateForWorkOrder($document, $data);

      DB::commit();
      return new ElectronicDocumentResource($document->load(['items', 'guides', 'installments', 'vehicleMovement']));
    } catch (Throwable $e) {
      DB::rollBack();
      throw new Exception('Error al crear el documento electrónico: ' . $e->getMessage());
    }
  }

  public function update(mixed $data): ElectronicDocumentResource
  {
    $id = $data['id'];
    DB::beginTransaction();
    try {
      $document = $this->find($id);

      // Solo se pueden actualizar documentos en estado draft
      if ($document->status !== ElectronicDocument::STATUS_DRAFT) {
        throw new Exception('Solo se pueden actualizar documentos en estado borrador');
      }

      // Prevenir cambio de número correlativo
      if (isset($data['numero']) && $data['numero'] !== $document->numero) {
        throw new Exception('No se puede cambiar el número correlativo del documento');
      }

      // Validar serie si está siendo actualizada
      if (isset($data['serie']) && isset($data['sunat_concept_document_type_id'])) {
        if (!ElectronicDocument::validateSerie($data['sunat_concept_document_type_id'], $data['serie'])) {
          throw new Exception('La serie no es válida para el tipo de documento seleccionado');
        }
      }

      // ============================================================================
      // VALIDACIONES PARA order_quotation_id
      // ============================================================================

      // Determinar el order_quotation_id efectivo (el del documento o el nuevo)
      $effectiveQuotationId = isset($data['order_quotation_id']) ? $data['order_quotation_id'] : $document->order_quotation_id;

      // Validar que no se pueda CAMBIAR la cotización si ya está asociada a una
      if (isset($data['order_quotation_id']) && $document->order_quotation_id && $data['order_quotation_id'] != $document->order_quotation_id) {
        throw new Exception('No se puede cambiar la cotización de un documento que ya está asociado a una cotización. Debe eliminar el documento y crear uno nuevo.');
      }

      // Validar que no se pueda QUITAR la cotización si ya está asociada
      if (isset($data['order_quotation_id']) && $data['order_quotation_id'] === null && $document->order_quotation_id) {
        throw new Exception('No se puede quitar la cotización de un documento que ya está asociado a una. Debe eliminar el documento y crear uno nuevo.');
      }

      // Validar la cotización si existe (ya sea del documento o nueva)
      if ($effectiveQuotationId) {
        $quotation = ApOrderQuotations::find($effectiveQuotationId);

        if (!$quotation) {
          throw new Exception('La cotización especificada no existe.');
        }

        // Validar que la cotización no esté descartada
        if ($quotation->status === ApOrderQuotations::STATUS_DESCARTADO) {
          throw new Exception('No se puede asociar un documento electrónico a una cotización descartada.');
        }

        // Determinar si es anticipo (considerar cambios en is_advance_payment)
        $isAdvancePayment = isset($data['is_advance_payment']) ? $data['is_advance_payment'] : $document->is_advance_payment;

        // Validar stock de productos si no es un anticipo
        $this->validateQuotationStock($quotation, $isAdvancePayment);

        // Validar anticipos si es anticipo o si está cambiando el monto
        if ($isAdvancePayment == 1) {
          $total = isset($data['total']) ? (float)$data['total'] : (float)$document->total;

          if ($total <= 0) {
            throw new Exception('Un anticipo no puede ser por 0 soles. El total debe ser mayor a 0.');
          }

          // Sumar todos los anticipos aceptados por SUNAT para esta cotización (excluyendo el actual)
          $totalAnticiposExistentes = ElectronicDocument::where('order_quotation_id', $effectiveQuotationId)
            ->where('id', '!=', $id)
            ->where('is_advance_payment', 1)
            ->where('aceptada_por_sunat', true)
            ->where('anulado', false)
            ->whereNull('deleted_at')
            ->sum('total');

          $totalAnticiposConNuevo = $totalAnticiposExistentes + $total;

          // Validar que no exceda el total de la cotización
          if ($totalAnticiposConNuevo > $quotation->total_amount) {
            throw new Exception(sprintf(
              'La suma de anticipos (%.2f) excede el monto total de la cotización (%.2f). Ya hay %.2f en anticipos existentes.',
              $totalAnticiposConNuevo,
              $quotation->total_amount,
              $totalAnticiposExistentes
            ));
          }
        }
      }

      // ============================================================================
      // VALIDACIONES PARA work_order_id
      // ============================================================================

      // Determinar el work_order_id efectivo (el del documento o el nuevo)
      $effectiveWorkOrderId = isset($data['work_order_id']) ? $data['work_order_id'] : $document->work_order_id;

      // Validar que no se pueda CAMBIAR la orden de trabajo si ya está asociada a una
      if (isset($data['work_order_id']) && $document->work_order_id && $data['work_order_id'] != $document->work_order_id) {
        throw new Exception('No se puede cambiar la orden de trabajo de un documento que ya está asociado a una orden. Debe eliminar el documento y crear uno nuevo.');
      }

      // Validar que no se pueda QUITAR la orden de trabajo si ya está asociada
      if (isset($data['work_order_id']) && $data['work_order_id'] === null && $document->work_order_id) {
        throw new Exception('No se puede quitar la orden de trabajo de un documento que ya está asociado a una. Debe eliminar el documento y crear uno nuevo.');
      }

      // Validar la orden de trabajo si existe (ya sea del documento o nueva)
      if ($effectiveWorkOrderId) {
        // Preparar datos para validación incluyendo tanto los datos del documento como los nuevos
        $validationData = array_merge($document->toArray(), $data);
        $validationData['work_order_id'] = $effectiveWorkOrderId;

        $this->validateWorkOrderInvoice($validationData);

        // Setear códigos de productos para items de work order si se están actualizando items
        if (isset($data['items']) && is_array($data['items'])) {
          $data['items'] = $this->setWorkOrderItemCodes($effectiveWorkOrderId, $data['items']);
        }
      }

      // Validar venta interna para order_quotation_id o work_order_id
      // Solo si es venta interna (is_advance_payment = 0) y tiene alguna de estas entidades
      if (($effectiveQuotationId || $effectiveWorkOrderId)) {
        // Determinar si es venta interna
        $isAdvancePayment = isset($data['is_advance_payment']) ? $data['is_advance_payment'] : $document->is_advance_payment;

        if ($isAdvancePayment == 0) {
          // Preparar datos para validación
          $validationData = array_merge($document->toArray(), $data);
          $this->validateInternalSaleTotal($validationData);
        }
      }

      // Manejar cambios en ap_vehicle_id
      if (isset($data['ap_vehicle_id']) && $data['ap_vehicle_id']) {
        // Si el documento ya tiene un movimiento de vehículo, no permitir cambiar el vehículo
        if ($document->ap_vehicle_movement_id) {
          // Obtener el movimiento existente
          $existingMovement = VehicleMovement::find($document->ap_vehicle_movement_id);

          // Si el vehículo es diferente al actual, no permitir el cambio
          if ($existingMovement && $existingMovement->ap_vehicle_id != $data['ap_vehicle_id']) {
            throw new Exception('No se puede cambiar el vehículo porque ya se creó un movimiento de vehículo. Debe eliminar el documento y crear uno nuevo.');
          }
        } else {
          // Si no tiene movimiento, crear uno nuevo
          $vehicleMovement = $this->createVehicleMovement($data['ap_vehicle_id'], $document);
          $data['ap_vehicle_movement_id'] = $vehicleMovement->id;
        }
      }

      // Actualizar datos del cliente si el client_id está cambiando
      if (isset($data['client_id']) && $data['client_id'] !== $document->client_id) {
        $client = BusinessPartners::find($data['client_id']);
        if (!$client) {
          throw new Exception('Cliente no encontrado');
        }

        $documentType = SunatConcepts::where('tribute_code', $client->document_type_id)
          ->where('type', SunatConcepts::TYPE_DOCUMENT)
          ->first();

        $data['sunat_concept_identity_document_type_id'] = $documentType->id;
        $data['cliente_numero_de_documento'] = $client->num_doc;
        $data['cliente_denominacion'] = $client->full_name . ($client->spouse_full_name ? ' - ' . $client->spouse_full_name : '');
        $data['cliente_direccion'] = $client->direction;
        $data['cliente_email'] = $client->email;
        $data['porcentaje_de_igv'] = $client->taxClassType->igv;
      }

      // Actualizar tipo de cambio si la moneda está cambiando
      if (isset($data['sunat_concept_currency_id']) && $data['sunat_concept_currency_id'] !== $document->sunat_concept_currency_id) {
        $exchangeRate = (new ExchangeRateService())->getCurrentUSDRate();
        $data['tipo_de_cambio'] = $exchangeRate->rate;
        $data['exchange_rate_id'] = $exchangeRate->id;
      }

      // Actualizar el documento
      $document->update(array_merge($data, [
        'updated_by' => auth()->id(),
      ]));

      // Actualizar items si se proporcionan
      if (isset($data['items']) && is_array($data['items'])) {
        // Eliminar items existentes
        $document->items()->delete();

        // Crear nuevos items
        $data['items'] = collect($data['items'])->sortBy('anticipo_regularizacion')->values()->all();
        foreach ($data['items'] as $index => $itemData) {
          $itemData['line_number'] = $index + 1;
          $document->items()->create($itemData);
        }
      }

      // Actualizar guías si se proporcionan
      if (isset($data['guias']) && is_array($data['guias'])) {
        $document->guides()->delete();
        foreach ($data['guias'] as $guiaData) {
          $document->guides()->create($guiaData);
        }
      }

      // Actualizar cuotas si se proporcionan
      if (isset($data['venta_al_credito']) && is_array($data['venta_al_credito'])) {
        $document->installments()->delete();
        foreach ($data['venta_al_credito'] as $cuotaData) {
          $document->installments()->create($cuotaData);
        }
      }

      // Actualizar estado de cotización si corresponde
      if (isset($data['order_quotation_id']) && $data['order_quotation_id']) {
        $this->updateQuotationInvoiceStatus($data['order_quotation_id']);
      }

      DB::commit();
      return new ElectronicDocumentResource($document->fresh(['items', 'guides', 'installments', 'vehicleMovement']));
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception('Error al actualizar el documento electrónico: ' . $e->getMessage());
    }
  }

  /**
   * Delete an electronic document
   * @throws Exception
   */
  public function destroy($id): JsonResponse
  {
    DB::beginTransaction();
    try {
      $document = $this->find($id);

      // Solo se pueden eliminar documentos en estado draft o que no hayan sido aceptados por SUNAT
      if ($document->status === ElectronicDocument::STATUS_ACCEPTED && $document->aceptada_por_sunat) {
        throw new Exception('No se puede eliminar un documento aceptado por SUNAT. Debe anularlo primero.');
      }

      $document->delete();

      DB::commit();
      return response()->json([
        'success' => true,
        'message' => 'Documento electrónico eliminado correctamente'
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception('Error al eliminar el documento electrónico: ' . $e->getMessage());
    }
  }

  public function sendToNubefact($id): JsonResponse
  {
    DB::beginTransaction();
    try {
      $document = $this->find($id);

      // Validar que el documento esté en estado correcto
      if ($document->status === ElectronicDocument::STATUS_ACCEPTED) {
        throw new Exception('El documento ya ha sido aceptado por SUNAT');
      }

      if ($document->anulado) {
        throw new Exception('No se puede enviar un documento anulado');
      }

      // Marcar como enviado
      $document->markAsSent();

      // Enviar a Nubefact
      $response = $this->nubefactService->generateDocument($document);

      // Validar estructura de respuesta
      if (!is_array($response)) {
        throw new Exception('Respuesta inválida de Nubefact: formato inesperado');
      }

      // El servicio devuelve ['success' => bool, 'data' => array] o ['success' => bool, 'error' => string, 'data' => array]
      if (!$response['success']) {
        $errorMessage = is_array($response['error']) ? implode(', ', $response['error']) : ($response['error'] ?? 'Error desconocido');

        throw new Exception('Error de Nubefact: ' . $errorMessage);
      }

      // Obtener los datos reales de Nubefact
      $nubefactData = $response['data'] ?? [];

      if (!isset($nubefactData['aceptada_por_sunat'])) {
        throw new Exception('Respuesta inválida de Nubefact: falta campo aceptada_por_sunat');
      }

      // Procesar respuesta
      if (isset($nubefactData['enlace_del_pdf']) && isset($nubefactData['enlace_del_xml']) && !isset($nubefactData['enlace_del_cdr'])) {
        $document->update([
          'enlace_del_pdf' => $nubefactData['enlace_del_pdf'],
          'enlace_del_xml' => $nubefactData['enlace_del_xml'],
        ]);
      }

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => 'Documento procesado correctamente',
        'data' => new ElectronicDocumentResource($document->fresh()),
        'sunat_response' => $nubefactData
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception('Error al enviar el documento a Nubefact: ' . $e->getMessage());
    }
  }

  public function queryFromNubefact($id): JsonResponse
  {
    try {
      $document = $this->find($id);

      $response = $this->nubefactService->queryDocument($document);

      // Extraer los datos de la respuesta
      $nubefactData = $response['data'] ?? $response;

      DB::beginTransaction();

      // Si el documento fue aceptado por SUNAT, actualizar usando el metodo del modelo
      if (isset($nubefactData['aceptada_por_sunat']) && $nubefactData['aceptada_por_sunat'] && !$document->aceptada_por_sunat) {
        $document->markAsAccepted($nubefactData);
        // Usa deduplicación para evitar jobs duplicados
        $this->dispatchJobWithDeduplication($id);
        $document->markAsInProgress();

        // Actualizar estado de cotización si el documento tiene order_quotation_id
        if ($document->order_quotation_id) {
          $this->updateQuotationInvoiceStatus($document->order_quotation_id);
        }

        // Actualizar estado de orden de trabajo si el documento tiene work_order_id
        if ($document->work_order_id) {
          $this->updateWorkOrderInvoiceStatus($document->work_order_id, $document->is_advance_payment);
        }

        // NOTA: La creación del movimiento de inventario se realiza en SyncAccountingStatusJob
        // después de confirmar que la factura fue contabilizada en Dynamics (is_accounted = true)
      }
      // Verificar si el documento fue anulado en Nubefact
      if (isset($nubefactData['anulado']) && $nubefactData['anulado'] === true) {
        // Si el documento no está marcado como cancelado en nuestra BD, actualizarlo
        if ($document->status !== ElectronicDocument::STATUS_CANCELLED || !$document->anulado) {
          $document->markAsCancelled();
        }
      }

      if ($nubefactData['aceptada_por_sunat'] !== $document->aceptada_por_sunat) {
        $document->aceptada_por_sunat = $nubefactData['aceptada_por_sunat'];
        $document->save();
      }

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => 'Estado consultado correctamente',
        'sunat_response' => $nubefactData
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception('Error al consultar el documento en Nubefact: ' . $e->getMessage());
    }
  }

  /**
   * Cancel document in Nubefact (Comunicación de baja)
   * @throws Exception
   */
  public function cancelInNubefact($id, string $reason): JsonResponse
  {
    DB::beginTransaction();
    try {
      $document = $this->find($id);

      // Validar que el documento esté aceptado
      if (!$document->aceptada_por_sunat) {
        throw new Exception('Solo se pueden anular documentos aceptados por SUNAT');
      }

      if ($document->anulado) {
        throw new Exception('El documento ya está anulado');
      }

      $electronicDocumentItem = ElectronicDocumentItem::where('anticipo_documento_serie', $document->serie)
        ->where('anticipo_documento_numero', $document->numero)
        ->whereNull('deleted_at');

      if ($electronicDocumentItem->count() > 0) {
        throw new Exception('El documento no se puede anular porque tiene anticipos asociados');
      }

      // Enviar anulación a Nubefact
      $response = $this->nubefactService->cancelDocument($document, $reason);

      // Marcar como cancelado
      $document->markAsLocalCancelled($reason);

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => 'Documento anulado correctamente en SUNAT',
        'data' => new ElectronicDocumentResource($document->fresh()),
        'sunat_response' => $response
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception('Error al anular el documento: ' . $e->getMessage());
    }
  }

  /**
   * Pre-cancel document in Nubefact (check status in Dynamics)
   * @param $id
   * @return array
   * @throws Exception
   */
  public function preCancelInNubefact($id): array
  {
    $document = $this->find($id);

    $documentDynamics30200 = DB::connection('dbtest')
      ->table('SOP30200')
      ->where('SOPNUMBE', 'like', '%' . $document->full_number . '%')
      ->first();

    if (!$documentDynamics30200) {
      $documentDynamics10100 = DB::connection('dbtest')
        ->table('SOP10100')
        ->where('SOPNUMBE', 'like', '%' . $document->full_number . '%')
        ->first();

      if (!$documentDynamics10100) {
        throw new Exception('No se encontró el documento en Dynamics para pre-anulación');
      } else {
        throw new Exception('El documento está en trabajo pendiente en Dynamics y no puede ser anulado');
      }
    }

    $isAnnulled = $documentDynamics30200->VOIDSTTS == "1";

    // Segunda opinión: si SOP30200 no lo marca como anulado, consultamos RM20101
    if (!$isAnnulled) {
      $rmRecord = DB::connection('dbtest')
        ->table('RM20101')
        ->where('DOCNUMBR', 'like', '%' . $document->full_number . '%')
        ->whereNot('RMDTYPAL', '9') // Excluir documentos de tipo 9 (cobros)
        ->first();

      if ($rmRecord) {
        $isAnnulled = $rmRecord->VOIDSTTS == "1";
      }
    }

    return [
      'annulled' => $isAnnulled,
    ];
  }

  /**
   * Calculate totals from items array
   * @param array $items
   * @return array
   */
  private function calculateTotalsFromItemsNotes(array $items): array
  {
    $totals = [
      'total_gravada' => 0,
      'total_inafecta' => 0,
      'total_exonerada' => 0,
      'total_igv' => 0,
      'total_gratuita' => 0,
      'total_descuento' => 0,
      'total_anticipo' => 0,
      'total_otros_cargos' => 0,
      'total_isc' => 0,
      'total' => 0,
    ];

    foreach ($items as $item) {
      // Verificar si es un item de anticipo regularización
      $isAnticipo = isset($item['anticipo_regularizacion']) && $item['anticipo_regularizacion'] === true;

      // Determinar el multiplicador (1 para items normales, -1 para anticipos)
      $multiplier = $isAnticipo ? -1 : 1;

      // Acumular IGV (restar si es anticipo)
      $totals['total_igv'] += $multiplier * (float)($item['igv'] ?? 0);

      // Acumular descuentos
      $totals['total_descuento'] += (float)($item['descuento'] ?? 0);

      // Acumular total anticipo (siempre positivo)
      if ($isAnticipo) {
        $totals['total_anticipo'] += (float)($item['total'] ?? 0);
      }

      // Acumular total (restar si es anticipo)
      $totals['total'] += $multiplier * (float)($item['total'] ?? 0);

      // Determinar el tipo de IGV y acumular en el total correspondiente
      $igvTypeId = $item['sunat_concept_igv_type_id'] ?? null;
      $subtotal = (float)($item['subtotal'] ?? 0);

      // Buscar el código del tipo de IGV
      $igvType = SunatConcepts::find($igvTypeId);
      $igvCode = $igvType->code_nubefact ?? null;

      if ($igvCode) {
        if ($igvCode == '1') {
          // Gravado - Operación Onerosa (restar si es anticipo)
          $totals['total_gravada'] += $multiplier * $subtotal;
        } elseif ($igvCode == '20') {
          // Exonerado - Operación Onerosa (restar si es anticipo)
          $totals['total_exonerada'] += $multiplier * $subtotal;
        } elseif ($igvCode == '30') {
          // Inafecto - Operación Onerosa (restar si es anticipo)
          $totals['total_inafecta'] += $multiplier * $subtotal;
        } elseif (in_array($igvCode, ['11', '12', '13', '14', '15', '16', '17', '21', '31', '32', '33', '34', '35', '36', '37'])) {
          // Operaciones gratuitas (restar si es anticipo)
          $totals['total_gratuita'] += $multiplier * $subtotal;
        }
      }
    }

    // Redondear a 2 decimales
    foreach ($totals as $key => $value) {
      $totals[$key] = round($value, 2);
    }

    return $totals;
  }

  /**
   * Build the items array for a credit note based on its type.
   * @throws Exception
   */
  private function resolveItemsForCreditNote(ElectronicDocument $originalDocument, array $data): array
  {
    $concept = SunatConcepts::find($data['sunat_concept_credit_note_type_id']);
    $typeCode = $concept?->code_nubefact;

    switch ($typeCode) {
      case SunatConcepts::CODE_CREDIT_NOTE_ANULACION:       // '01'
      case SunatConcepts::CODE_CREDIT_NOTE_DEVOLUCION_TOTAL: // '06'
        return $this->buildItemsFromAllOriginalItems($originalDocument);

      case SunatConcepts::CODE_CREDIT_NOTE_DEVOLUCION_ITEM: // '07'
        return $this->buildItemsFromSelectedIds($originalDocument, $data['detail_ids']);

      case SunatConcepts::CODE_CREDIT_NOTE_DESCUENTO_GLOBAL: // '04'
        return $this->buildDiscountGlobalItem($originalDocument, $data);

      default:
        throw new Exception("Tipo de nota de crédito no reconocido: {$typeCode}");
    }
  }

  /**
   * Copy all items from the original document (for Anulación / Devolución total).
   */
  private function buildItemsFromAllOriginalItems(ElectronicDocument $document): array
  {
    if ($document->items->isEmpty()) {
      throw new Exception('El documento original no tiene ítems');
    }

    return $document->items->map(function (ElectronicDocumentItem $item) {
      return [
        'account_plan_id' => $item->account_plan_id,
        'unidad_de_medida' => $item->unidad_de_medida,
        'codigo' => $item->codigo,
        'codigo_producto_sunat' => $item->codigo_producto_sunat,
        'descripcion' => $item->descripcion,
        'cantidad' => $item->cantidad,
        'valor_unitario' => $item->valor_unitario,
        'precio_unitario' => $item->precio_unitario,
        'descuento' => $item->descuento,
        'subtotal' => $item->subtotal,
        'sunat_concept_igv_type_id' => $item->sunat_concept_igv_type_id,
        'igv' => $item->igv,
        'total' => $item->total,
      ];
    })->values()->toArray();
  }

  /**
   * Copy specific items from the original document by their IDs (for Devolución por ítem).
   * @throws Exception
   */
  private function buildItemsFromSelectedIds(ElectronicDocument $document, array $detailIds): array
  {
    $selectedItems = $document->items->whereIn('id', $detailIds);

    if ($selectedItems->isEmpty()) {
      throw new Exception('Ninguno de los ítems especificados pertenece al documento original');
    }

    return $selectedItems->map(function (ElectronicDocumentItem $item) {
      return [
        'account_plan_id' => $item->account_plan_id,
        'unidad_de_medida' => $item->unidad_de_medida,
        'codigo' => $item->codigo,
        'codigo_producto_sunat' => $item->codigo_producto_sunat,
        'descripcion' => $item->descripcion,
        'cantidad' => $item->cantidad,
        'valor_unitario' => $item->valor_unitario,
        'precio_unitario' => $item->precio_unitario,
        'descuento' => $item->descuento,
        'subtotal' => $item->subtotal,
        'sunat_concept_igv_type_id' => $item->sunat_concept_igv_type_id,
        'igv' => $item->igv,
        'total' => $item->total,
      ];
    })->values()->toArray();
  }

  /**
   * Build a single discount line item for a global discount credit note (Descuento global).
   * The user provides discount_amount as the total amount (including IGV when applicable).
   */
  private function buildDiscountGlobalItem(ElectronicDocument $document, array $data): array
  {
    $discountAmount = (float)$data['discount_amount'];

    // Determine IGV handling based on original document
    $isGravado = (float)$document->total_gravada > 0;

    if ($isGravado) {
      $igvTypeId = SunatConcepts::ID_IGV_GRAVADO_ONEROSA;
      $subtotal = round($discountAmount / 1.18, 2);
      $igv = round($discountAmount - $subtotal, 2);
    } else {
      // Use IGV type from first item of original document (inafecta/exonerada)
      $firstItem = $document->items->first();
      $igvTypeId = $firstItem?->sunat_concept_igv_type_id ?? SunatConcepts::ID_IGV_GRAVADO_ONEROSA;
      $subtotal = $discountAmount;
      $igv = 0.00;
    }

    return [[
      'account_plan_id' => $data['account_plan_id'],
      'unidad_de_medida' => 'NIU',
      'codigo' => null,
      'codigo_producto_sunat' => null,
      'descripcion' => 'Descuento global',
      'cantidad' => 1,
      'valor_unitario' => $subtotal,
      'precio_unitario' => $discountAmount,
      'descuento' => null,
      'subtotal' => $subtotal,
      'sunat_concept_igv_type_id' => $igvTypeId,
      'igv' => $igv,
      'total' => $discountAmount,
    ]];
  }

  /**
   * Create a credit note from an existing document
   * @throws Exception
   */
  public function createCreditNote($originalDocumentId, array $data): ElectronicDocumentResource
  {
    DB::beginTransaction();
    try {
      $originalDocument = $this->find($originalDocumentId);

      // Validar que el documento original esté aceptado
      if (!$originalDocument->aceptada_por_sunat) {
        throw new Exception('Solo se pueden crear notas de crédito para documentos aceptados por SUNAT');
      }

      // Resolver los items según el tipo de nota de crédito
      $originalDocument->load('items');
      $data['items'] = $this->resolveItemsForCreditNote($originalDocument, $data);

      // Calcular totales desde items
      if (isset($data['items']) && is_array($data['items'])) {
        $calculatedTotals = $this->calculateTotalsFromItemsNotes($data['items']);

        // Merge calculated totals only if not provided by user
        foreach ($calculatedTotals as $key => $value) {
          if (!isset($data[$key])) {
            $data[$key] = $value;
          }
        }
      }

      // Copiar moneda y tipo de cambio del documento original
      if (!isset($data['sunat_concept_currency_id'])) {
        $data['sunat_concept_currency_id'] = $originalDocument->sunat_concept_currency_id;
      }
      if (!isset($data['tipo_de_cambio'])) {
        $data['tipo_de_cambio'] = $originalDocument->tipo_de_cambio;
      }

      // Copiar cliente del documento original
      if (!isset($data['client_id'])) {
        $data['client_id'] = $originalDocument->client_id;
      }

      // Copiar tipo de transacción del documento original
      if (!isset($data['sunat_concept_transaction_type_id'])) {
        $data['sunat_concept_transaction_type_id'] = $originalDocument->sunat_concept_transaction_type_id;
      }

      // Preparar datos de la nota de crédito
      $creditNoteData = array_merge($data, [
        'sunat_concept_document_type_id' => ElectronicDocument::TYPE_NOTA_CREDITO,
        'documento_que_se_modifica_tipo' => $originalDocument->documentType->code_nubefact,
        'documento_que_se_modifica_serie' => $originalDocument->serie,
        'documento_que_se_modifica_numero' => $originalDocument->numero,
        'original_document_id' => $originalDocumentId,
        'area_id' => $originalDocument->area_id,
        'origin_entity_type' => $originalDocument->origin_entity_type,
        'origin_entity_id' => $originalDocument->origin_entity_id,
        'purchase_request_quote_id' => $originalDocument->purchase_request_quote_id ?? null,
      ]);

      // Crear la nota de crédito
      $creditNote = $this->store($creditNoteData);

      $originalDocument->update([
        'credit_note_id' => $creditNote->id,
      ]);

      DB::commit();
      return $creditNote;
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception('Error al crear la nota de crédito: ' . $e->getMessage());
    }
  }

  /**
   * Create a debit note from an existing document
   * @throws Exception
   */
  public function createDebitNote($originalDocumentId, array $data): ElectronicDocumentResource
  {
    DB::beginTransaction();
    try {
      $originalDocument = $this->find($originalDocumentId);

      // Validar que el documento original esté aceptado
      if (!$originalDocument->aceptada_por_sunat) {
        throw new Exception('Solo se pueden crear notas de débito para documentos aceptados por SUNAT');
      }

      // Calcular totales desde items si no se proporcionan
      if (isset($data['items']) && is_array($data['items'])) {
        $calculatedTotals = $this->calculateTotalsFromItemsNotes($data['items']);

        // Merge calculated totals only if not provided by user
        foreach ($calculatedTotals as $key => $value) {
          if (!isset($data[$key])) {
            $data[$key] = $value;
          }
        }
      }

      // Copiar moneda y tipo de cambio del documento original
      if (!isset($data['sunat_concept_currency_id'])) {
        $data['sunat_concept_currency_id'] = $originalDocument->sunat_concept_currency_id;
      }
      if (!isset($data['tipo_de_cambio'])) {
        $data['tipo_de_cambio'] = $originalDocument->tipo_de_cambio;
      }

      // Copiar cliente del documento original
      if (!isset($data['client_id'])) {
        $data['client_id'] = $originalDocument->client_id;
      }

      // Copiar tipo de transacción del documento original
      if (!isset($data['sunat_concept_transaction_type_id'])) {
        $data['sunat_concept_transaction_type_id'] = $originalDocument->sunat_concept_transaction_type_id;
      }

      // Preparar datos de la nota de débito
      $debitNoteData = array_merge($data, [
        'sunat_concept_document_type_id' => ElectronicDocument::TYPE_NOTA_DEBITO,
        'enviar_automaticamente_a_la_sunat' => false,
        'enviar_automaticamente_al_cliente' => false,
        'documento_que_se_modifica_tipo' => $originalDocument->documentType->code_nubefact,
        'documento_que_se_modifica_serie' => $originalDocument->serie,
        'documento_que_se_modifica_numero' => $originalDocument->numero,
        'original_document_id' => $originalDocumentId,
        'area_id' => $originalDocument->area_id,
        'origin_entity_type' => $originalDocument->origin_entity_type,
        'origin_entity_id' => $originalDocument->origin_entity_id,
        'purchase_request_quote_id' => $originalDocument->purchase_request_quote_id ?? null,
      ]);

      // Validar límite razonable para notas de débito (200% del original)
      $originalTotal = (float)$originalDocument->total;
      $debitNoteTotal = (float)$debitNoteData['total'];
      $maxAllowedTotal = $originalTotal * 2;

      if ($debitNoteTotal > $maxAllowedTotal) {
        throw new Exception(sprintf(
          'El total de la nota de débito (%.2f) excede el límite permitido (%.2f). El total no puede ser mayor al 200%% del documento original (%.2f)',
          $debitNoteTotal,
          $maxAllowedTotal,
          $originalTotal
        ));
      }

      // Crear la nota de débito
      $debitNote = $this->store($debitNoteData);

      $originalDocument->update([
        'debit_note_id' => $debitNote->id,
      ]);

      DB::commit();
      return $debitNote;
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception('Error al crear la nota de débito: ' . $e->getMessage());
    }
  }

  /**
   * Update an existing credit note
   * @throws Exception
   */
  public function updateCreditNote($creditNoteId, array $data): ElectronicDocumentResource
  {
    DB::beginTransaction();
    try {
      // Buscar la nota de crédito existente
      $creditNote = ElectronicDocument::with(['items'])->find($creditNoteId);

      if (!$creditNote) {
        throw new Exception('Nota de crédito no encontrada');
      }

      // Validar que sea una nota de crédito
      if ($creditNote->sunat_concept_document_type_id !== ElectronicDocument::TYPE_NOTA_CREDITO) {
        throw new Exception('El documento especificado no es una nota de crédito');
      }

      // Validar que no haya sido enviada a SUNAT
      if ($creditNote->aceptada_por_sunat) {
        throw new Exception('No se puede actualizar una nota de crédito que ya ha sido aceptada por SUNAT');
      }

      // Validar que no esté anulada
      if ($creditNote->anulado) {
        throw new Exception('No se puede actualizar una nota de crédito anulada');
      }

      // Obtener el documento original desde la nota de crédito
      $originalDocument = $this->find($creditNote->original_document_id);

      // Resolver los items según el tipo de nota de crédito
      $originalDocument->load('items');
      $data['items'] = $this->resolveItemsForCreditNote($originalDocument, $data);

      // Calcular totales desde items
      if (isset($data['items']) && is_array($data['items'])) {
        $calculatedTotals = $this->calculateTotalsFromItemsNotes($data['items']);

        foreach ($calculatedTotals as $key => $value) {
          if (!isset($data[$key])) {
            $data[$key] = $value;
          }
        }
      }

      // Copiar moneda y tipo de cambio del documento original si no se proporcionan
      if (!isset($data['sunat_concept_currency_id'])) {
        $data['sunat_concept_currency_id'] = $originalDocument->sunat_concept_currency_id;
      }
      if (!isset($data['tipo_de_cambio'])) {
        $data['tipo_de_cambio'] = $originalDocument->tipo_de_cambio;
      }

      // Copiar cliente del documento original si no se proporciona
      if (!isset($data['client_id'])) {
        $data['client_id'] = $originalDocument->client_id;
      }

      // Copiar tipo de transacción del documento original si no se proporciona
      if (!isset($data['sunat_concept_transaction_type_id'])) {
        $data['sunat_concept_transaction_type_id'] = $originalDocument->sunat_concept_transaction_type_id;
      }

      // Preparar datos para actualización
      $updateData = array_merge($data, [
        'documento_que_se_modifica_tipo' => $originalDocument->documentType->code_nubefact,
        'documento_que_se_modifica_serie' => $originalDocument->serie,
        'documento_que_se_modifica_numero' => $originalDocument->numero,
        'original_document_id' => $creditNote->original_document_id,
      ]);

      // No permitir cambiar estos campos
      unset($updateData['sunat_concept_document_type_id']);
      unset($updateData['area_id']);
      unset($updateData['origin_entity_type']);
      unset($updateData['origin_entity_id']);

      // Actualizar la nota de crédito
      $updateData['id'] = $creditNoteId;
      $updatedCreditNote = $this->update($updateData);

      DB::commit();
      return $updatedCreditNote;
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception('Error al actualizar la nota de crédito: ' . $e->getMessage());
    }
  }

  /**
   * Update an existing debit note
   * @throws Exception
   */
  public function updateDebitNote($debitNoteId, array $data): ElectronicDocumentResource
  {
    DB::beginTransaction();
    try {
      // Buscar la nota de débito existente
      $debitNote = ElectronicDocument::with(['items'])->find($debitNoteId);

      if (!$debitNote) {
        throw new Exception('Nota de débito no encontrada');
      }

      // Validar que sea una nota de débito
      if ($debitNote->sunat_concept_document_type_id !== ElectronicDocument::TYPE_NOTA_DEBITO) {
        throw new Exception('El documento especificado no es una nota de débito');
      }

      // Validar que no haya sido enviada a SUNAT
      if ($debitNote->aceptada_por_sunat) {
        throw new Exception('No se puede actualizar una nota de débito que ya ha sido aceptada por SUNAT');
      }

      // Validar que no esté anulada
      if ($debitNote->anulado) {
        throw new Exception('No se puede actualizar una nota de débito anulada');
      }

      // Obtener el documento original
      $originalDocument = $this->find($data['original_document_id']);

      // Calcular totales desde items si no se proporcionan
      if (isset($data['items']) && is_array($data['items'])) {
        $calculatedTotals = $this->calculateTotalsFromItemsNotes($data['items']);

        // Merge calculated totals only if not provided by user
        foreach ($calculatedTotals as $key => $value) {
          if (!isset($data[$key])) {
            $data[$key] = $value;
          }
        }
      }

      // Copiar moneda y tipo de cambio del documento original si no se proporcionan
      if (!isset($data['sunat_concept_currency_id'])) {
        $data['sunat_concept_currency_id'] = $originalDocument->sunat_concept_currency_id;
      }
      if (!isset($data['tipo_de_cambio'])) {
        $data['tipo_de_cambio'] = $originalDocument->tipo_de_cambio;
      }

      // Copiar cliente del documento original si no se proporciona
      if (!isset($data['client_id'])) {
        $data['client_id'] = $originalDocument->client_id;
      }

      // Copiar tipo de transacción del documento original si no se proporciona
      if (!isset($data['sunat_concept_transaction_type_id'])) {
        $data['sunat_concept_transaction_type_id'] = $originalDocument->sunat_concept_transaction_type_id;
      }

      // Preparar datos para actualización
      $updateData = array_merge($data, [
        'enviar_automaticamente_a_la_sunat' => false,
        'enviar_automaticamente_al_cliente' => false,
        'documento_que_se_modifica_tipo' => $originalDocument->documentType->code_nubefact,
        'documento_que_se_modifica_serie' => $originalDocument->serie,
        'documento_que_se_modifica_numero' => $originalDocument->numero,
        'original_document_id' => $data['original_document_id'],
      ]);

      // No permitir cambiar estos campos
      unset($updateData['sunat_concept_document_type_id']);
      unset($updateData['area_id']);
      unset($updateData['origin_entity_type']);
      unset($updateData['origin_entity_id']);

      // Validar límite razonable para notas de débito (200% del original)
      $originalTotal = (float)$originalDocument->total;
      $debitNoteTotal = (float)$updateData['total'];
      $maxAllowedTotal = $originalTotal * 2;

      if ($debitNoteTotal > $maxAllowedTotal) {
        throw new Exception(sprintf(
          'El total de la nota de débito (%.2f) excede el límite permitido (%.2f). El total no puede ser mayor al 200%% del documento original (%.2f)',
          $debitNoteTotal,
          $maxAllowedTotal,
          $originalTotal
        ));
      }

      // Actualizar la nota de débito
      $updateData['id'] = $debitNoteId;
      $updatedDebitNote = $this->update($updateData);

      DB::commit();
      return $updatedDebitNote;
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception('Error al actualizar la nota de débito: ' . $e->getMessage());
    }
  }

  /**
   * Get documents by module and entity
   */
  public function getByOriginEntity(int $areaId, string $entityType, int $entityId): JsonResponse
  {
    try {
      $documents = ElectronicDocument::with(['documentType', 'currency', 'items'])
        ->where('area_id', $areaId)
        ->where('origin_entity_type', $entityType)
        ->where('origin_entity_id', $entityId)
        ->orderBy('fecha_de_emision', 'desc')
        ->get();

      return response()->json([
        'success' => true,
        'data' => ElectronicDocumentResource::collection($documents)
      ]);
    } catch (Exception $e) {
      throw new Exception('Error al obtener documentos: ' . $e->getMessage());
    }
  }

  /**
   * Calculate totals from items
   */
  public function calculateTotalsFromItems(array $items): array
  {
    $totals = [
      'total_gravada' => 0,
      'total_exonerada' => 0,
      'total_inafecta' => 0,
      'total_gratuita' => 0,
      'total_igv' => 0,
      'total' => 0,
    ];

    foreach ($items as $item) {
      $igvTypeCode = $item['tipo_de_igv'] ?? 1;

      // Determinar el tipo de operación según el código IGV
      if ($igvTypeCode == 1) { // Gravado
        $totals['total_gravada'] += $item['subtotal'];
        $totals['total_igv'] += $item['igv'];
      } elseif ($igvTypeCode == 8) { // Exonerado
        $totals['total_exonerada'] += $item['subtotal'];
      } elseif ($igvTypeCode == 9) { // Inafecto
        $totals['total_inafecta'] += $item['subtotal'];
      } elseif ($igvTypeCode == 17 || $igvTypeCode == 20) { // Gratuito
        $totals['total_gratuita'] += $item['subtotal'];
      }
    }

    // Calcular total (las operaciones gratuitas no suman)
    $totals['total'] = $totals['total_gravada'] + $totals['total_exonerada'] + $totals['total_inafecta'] + $totals['total_igv'];

    return $totals;
  }

  /**
   * Obtiene los anticipos pendientes de regularización para una entidad origen
   */
  public function getPendingAnticipos(string $module, string $entityType, int $entityId)
  {
    return ElectronicDocument::byOriginEntity($module, $entityType, $entityId)
      ->anticipos()
      ->acceptedBySunat()
      ->notCancelled()
      ->get()
      ->filter(function ($anticipo) {
        return !$anticipo->isRegularized();
      })
      ->values();
  }

  /**
   * Calcula los totales para una factura de regularización
   */
  public function calculateRegularizationTotals(float $vehiclePrice, $anticipos): array
  {
    $totalAnticipos = $anticipos->sum('total');
    $totalGravada = ($vehiclePrice / 1.18) - ($totalAnticipos / 1.18);
    $totalIgv = $vehiclePrice - ($vehiclePrice / 1.18) - ($totalAnticipos - ($totalAnticipos / 1.18));
    $totalFinal = $vehiclePrice - $totalAnticipos;

    return [
      'total_anticipo' => round($totalAnticipos, 2),
      'total_gravada' => round($totalGravada, 2),
      'total_igv' => round($totalIgv, 2),
      'total' => round($totalFinal, 2),
    ];
  }

  /**
   * Construye los items para una factura de regularización
   */
  public function buildRegularizationItems($vehicle, $anticipos, array $additionalData = []): array
  {
    $items = [];
    $vehiclePrice = (float)$vehicle->model->sale_price;
    $porcentajeIgv = 18;

    // Item 1: Producto principal (vehículo completo) - POSITIVO
    $valorUnitario = round($vehiclePrice / (1 + ($porcentajeIgv / 100)), 2);
    $igv = round($vehiclePrice - $valorUnitario, 2);

    $items[] = [
      'unidad_de_medida' => 'NIU',
      'codigo' => $additionalData['codigo'] ?? 'VEH-001',
      'descripcion' => $additionalData['descripcion'] ?? "Vehículo {$vehicle->model->commercial_brand->name} {$vehicle->model->model} {$vehicle->model->year} - VIN: {$vehicle->vin}",
      'cantidad' => 1,
      'valor_unitario' => $valorUnitario,
      'precio_unitario' => $vehiclePrice,
      'descuento' => 0,
      'subtotal' => $valorUnitario,
      'sunat_concept_igv_type_id' => SunatConcepts::ID_IGV_ANTICIPO_GRAVADO, // Tipo 1
      'igv' => $igv,
      'total' => $vehiclePrice,
      'anticipo_regularizacion' => false,
    ];

    // Items 2-N: Anticipos (negativos)
    foreach ($anticipos as $anticipo) {
      $anticipoTotal = (float)$anticipo->total;
      $anticipoValorUnitario = round(-($anticipoTotal / (1 + ($porcentajeIgv / 100))), 2);
      $anticipoIgv = round(-($anticipoTotal - abs($anticipoValorUnitario)), 2);

      $items[] = [
        'unidad_de_medida' => 'ZZ',
        'codigo' => "ANT-{$anticipo->serie}-{$anticipo->numero}",
        'descripcion' => "Anticipo {$anticipo->serie}-{$anticipo->numero}",
        'cantidad' => 1,
        'valor_unitario' => $anticipoValorUnitario,
        'precio_unitario' => -$anticipoTotal,
        'descuento' => 0,
        'subtotal' => $anticipoValorUnitario,
        'sunat_concept_igv_type_id' => SunatConcepts::ID_IGV_ANTICIPO_GRAVADO, // Tipo 1
        'igv' => $anticipoIgv,
        'total' => -$anticipoTotal,
        'anticipo_regularizacion' => true,
        'anticipo_documento_serie' => $anticipo->serie,
        'anticipo_documento_numero' => $anticipo->numero,
      ];
    }

    return $items;
  }

  /**
   * Generate PDF for electronic document
   * @throws Exception
   */
  public function generatePDF($id)
  {
    try {
      $document = $this->find($id);
      $resource = new ElectronicDocumentResource($document);
      $dataArray = $resource->resolve();

      // Agregar datos adicionales para el PDF
      $dataArray['currency_symbol'] = $document->currency->symbol ?? 'S/';
      $dataArray['document_type_name'] = $document->documentType->description ?? '';
      $dataArray['identity_document_type_name'] = $document->identityDocumentType->description ?? '';
      $dataArray['transaction_type_name'] = $document->transactionType->description ?? '';

      // Cargar items con sus relaciones
      $dataArray['items_collection'] = $document->items->map(function ($item) {
        return [
          'codigo' => $item->codigo,
          'descripcion' => $item->descripcion,
          'unidad_de_medida' => $item->unidad_de_medida,
          'cantidad' => $item->cantidad,
          'valor_unitario' => $item->valor_unitario,
          'precio_unitario' => $item->precio_unitario,
          'descuento' => $item->descuento,
          'subtotal' => $item->subtotal,
          'igv' => $item->igv,
          'total' => $item->total,
          'igv_type_description' => $item->igvType->description ?? '',
        ];
      })->toArray();

      // Convertir totales en letras
      $dataArray['total_en_letras'] = $this->convertNumberToWords($document->total);

      $pdf = PDF::loadView('reports.ap.facturacion.electronic-document', ['document' => $dataArray]);

      // Configurar PDF
      $pdf->setOptions([
        'defaultFont' => 'Arial',
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => false,
        'dpi' => 96,
      ]);

      // Tamaño A4
      $pdf->setPaper('A4', 'portrait');

      return $pdf;
    } catch (Exception $e) {
      throw new Exception('Error al generar el PDF: ' . $e->getMessage());
    }
  }

  /**
   * Create vehicle movement when electronic document is created
   * @param int $vehicleId
   * @param ElectronicDocument $document
   * @return VehicleMovement
   * @throws Exception
   */
  private function createVehicleMovement(int $vehicleId, ElectronicDocument $document)
  {
    try {
      $vehicle = Vehicles::find($vehicleId);
      $isFinal = !$document->is_advance_payment;

      if (!$vehicle) {
        throw new Exception("Vehículo con ID {$vehicleId} no encontrado");
      }

      // Obtener el estado anterior del vehículo
      $previousStatusId = $vehicle->ap_vehicle_status_id;

      // Crear el movimiento de vehículo
      $vehicleMovement = VehicleMovement::create([
        'movement_type' => 'VENTA',
        'ap_vehicle_id' => $vehicleId,
        'ap_vehicle_status_id' => $isFinal ? ApVehicleStatus::FACTURADO_FINAL : ApVehicleStatus::FACTURADO,
        'movement_date' => now(),
        'observation' => "Venta de vehículo - Documento: {$document->serie}-{$document->numero}",
        'previous_status_id' => $previousStatusId,
        'new_status_id' => $isFinal ? ApVehicleStatus::FACTURADO_FINAL : ApVehicleStatus::FACTURADO,
        'created_by' => auth()->id(),
      ]);

      // Actualizar el estado del vehículo
      $vehicle->update([
        'ap_vehicle_status_id' => $isFinal ? ApVehicleStatus::FACTURADO_FINAL : ApVehicleStatus::FACTURADO,
      ]);

      return $vehicleMovement;
    } catch (Exception $e) {
      throw new Exception('Error al crear el movimiento de vehículo: ' . $e->getMessage());
    }
  }

  /**
   * Convert number to words (Spanish)
   */
  private function convertNumberToWords($number): string
  {
    $formatter = new NumberFormatter('es', NumberFormatter::SPELLOUT);
    $integerPart = floor($number);
    $decimalPart = round(($number - $integerPart) * 100);

    $words = strtoupper($formatter->format($integerPart));

    return "{$words} CON {$decimalPart}/100";
  }

  /**
   * @throws Exception
   */
  public function nextCreditNoteNumber(array $data, $id): array
  {
    /**
     * TODO: Change series to series_id in the future
     */
    $series = AssignSalesSeries::find($data['series']);
    $electronicDocument = $this->find($id);
    $electronicDocumentItems = ElectronicDocumentItem::where('anticipo_documento_serie', $electronicDocument->serie)
      ->where('anticipo_documento_numero', $electronicDocument->numero)
      ->whereNull('deleted_at')->get();

    $isRegularized = false;

    foreach ($electronicDocumentItems as $electronicDocumentItem) {
      $electronicDocumentParent = ElectronicDocument::where('id', $electronicDocumentItem->reference_document_id)
        ->where('anulado', false)
        ->where('aceptada_por_sunat', true)
        ->whereNull('deleted_at')
        ->first();

      if ($electronicDocumentParent) {
        $isRegularized = true;
        break;
      }
//    throw new Exception($electronicDocumentFirstItem);
    }

//    if ($isRegularized) {
//      throw new Exception('El anticipo ya ha sido regularizado, no se puede crear una nota de crédito. En su lugar cree una nota de crédito para el documento de regularización.');
//    }

    return [
      'series' => $series->series,
      'number' => $this->nextDocumentNumber(
        ElectronicDocument::TYPE_NOTA_CREDITO,
        $series->series
      )['number']
    ];

  }

  /**
   * @throws Exception
   */
  public function nextDebitNoteNumber(array $data, $id): array
  {
    /**
     * TODO: Change series to series_id in the future
     */
    $series = AssignSalesSeries::find($data['series']);
    $electronicDocument = $this->find($id);
    $electronicDocumentItem = ElectronicDocumentItem::where('anticipo_documento_serie', $electronicDocument->serie)
      ->where('anticipo_documento_numero', $electronicDocument->numero)
      ->whereNull('deleted_at');

    if ($electronicDocumentItem->count() > 0) {
      throw new Exception('El anticipo ya ha sido regularizado, no se puede crear una nota de débito. En su lugar cree una nota de débito para el documento de regularización.');
    }

    return [
      'series' => $series->series,
      'number' => $this->nextDocumentNumber(
        ElectronicDocument::TYPE_NOTA_DEBITO,
        $series->series
      )['number']
    ];

  }

  /**
   * Sync electronic document to Dynamics 365
   *
   * @param int $id
   * @return array
   * @throws Exception
   */
  public function syncToDynamics(int $id): array
  {
    $document = $this->find($id);

    if (!$document) {
      throw new Exception('Documento electrónico no encontrado');
    }

    if ($document->anulado) {
      throw new Exception('No se puede sincronizar un documento anulado');
    }

    $document->update(['was_dyn_requested' => true]);

    // Dispatch the sync job con deduplicación
    $this->dispatchJobWithDeduplication($id);

    return [
      'success' => true,
      'message' => 'Sincronización con Dynamics iniciada correctamente',
      'document_id' => $id,
      'document_number' => $document->document_number,
    ];
  }

  /**
   * Get sync status for electronic document
   *
   * @param int $id
   * @return array
   * @throws Exception
   */
  public function getSyncStatus(int $id): array
  {
    $document = $this->find($id);

    if (!$document) {
      throw new Exception('Documento electrónico no encontrado');
    }

    // Get all migration logs for this document
    $logs = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $id)
      ->orderBy('step')
      ->get()
      ->map(function ($log) {
        return [
          'step' => $log->step,
          'table_name' => $log->table_name,
          'status' => $log->status,
          'proceso_estado' => $log->proceso_estado,
          'error_message' => $log->error_message,
          'attempts' => $log->attempts,
          'last_attempt_at' => $log->last_attempt_at,
          'completed_at' => $log->completed_at,
        ];
      });

    // Determine overall sync status
    $allCompleted = $logs->every(fn($log) => $log['status'] === 'completed');
    $anyFailed = $logs->contains(fn($log) => $log['status'] === 'failed');
    $anyInProgress = $logs->contains(fn($log) => $log['status'] === 'in_progress');

    $overallStatus = 'not_started';
    if ($logs->isNotEmpty()) {
      if ($allCompleted) {
        $overallStatus = 'completed';
      } elseif ($anyFailed) {
        $overallStatus = 'failed';
      } elseif ($anyInProgress) {
        $overallStatus = 'in_progress';
      } else {
        $overallStatus = 'pending';
      }
    }

    // Check intermediate database status if completed
    $dynamicsStatus = null;
    if ($allCompleted) {
      $tipoId = match ($document->sunat_concept_document_type_id) {
        ElectronicDocument::TYPE_FACTURA => '01',
        ElectronicDocument::TYPE_BOLETA => '03',
        ElectronicDocument::TYPE_NOTA_CREDITO => '07',
        ElectronicDocument::TYPE_NOTA_DEBITO => '08',
        default => '01',
      };

      $documentoId = "{$tipoId}-{$document->serie}-{$document->numero}";

      try {
        $dynamicsRecord = DB::connection('dbtp')
          ->table('neInTbVenta')
          ->where('EmpresaId', Company::AP_DYNAMICS)
          ->where('DocumentoId', $documentoId)
          ->first();

        if ($dynamicsRecord) {
          $dynamicsStatus = [
            'found' => true,
            'proceso_estado' => $dynamicsRecord->ProcesoEstado,
            'proceso_error' => $dynamicsRecord->ProcesoError,
            'fecha_proceso' => $dynamicsRecord->FechaProceso,
            'processed_by_dynamics' => $dynamicsRecord->ProcesoEstado === 1,
          ];
        }
      } catch (\Exception $e) {
        $dynamicsStatus = [
          'found' => false,
          'error' => $e->getMessage(),
        ];
      }
    }

    return [
      'document_id' => $id,
      'document_number' => $document->document_number,
      'overall_status' => $overallStatus,
      'sync_steps' => $logs,
      'dynamics_status' => $dynamicsStatus,
    ];
  }

  public function checkResources($id)
  {
    $document = $this->find($id);
    $document->load('purchaseRequestQuote.accessories.approvedAccessory');

    return (new \App\Services\Dynamics\SalesDynamicsBuilder())->buildAll($document);
  }

  /**
   * Update quotation invoice status
   * Marks has_invoice_generated = true when a document is created
   * Marks is_fully_paid = true when total paid (accepted docs, non-advance) >= quotation total
   *
   * @param int $quotationId
   * @return void
   * @throws Exception
   */
  private function updateQuotationInvoiceStatus(int $quotationId): void
  {
    try {
      $quotation = ApOrderQuotations::find($quotationId);

      if (!$quotation) {
        return;
      }

      // Marcar que se generó factura
      $quotation->update(['has_invoice_generated' => true]);

      // Calcular total pagado (suma de todos los documentos aceptados por SUNAT)
      // Incluye anticipos + facturas de regularización/venta directa
      $totalPaid = ElectronicDocument::where('order_quotation_id', $quotationId)
        ->where('aceptada_por_sunat', true)
        ->where('anulado', false)
        ->whereNull('deleted_at')
        ->sum('total');

      // Verificar si existe al menos una venta interna (is_advance_payment = 0) aceptada por SUNAT
      // La venta interna es el documento que cierra la operación (puede ser por 0 o mayor)
      $hasInternalSale = ElectronicDocument::where('order_quotation_id', $quotationId)
        ->where('is_advance_payment', 0)
        ->where('aceptada_por_sunat', true)
        ->where('anulado', false)
        ->whereNull('deleted_at')
        ->exists();

      // Marcar como totalmente pagado solo si:
      // 1. El total pagado >= total de la cotización
      // 2. Existe al menos una venta interna (is_advance_payment = 0) aceptada por SUNAT
      if ($totalPaid >= $quotation->total_amount && $hasInternalSale) {
        $quotation->update([
          'is_fully_paid' => true,
          'status' => ApOrderQuotations::STATUS_FACTURADO
        ]);
      }
    } catch (Exception $e) {
      Log::error('Error updating quotation invoice status', [
        'quotation_id' => $quotationId,
        'error' => $e->getMessage(),
      ]);
      // No lanzar excepción para evitar que falle la creación del documento
    }
  }

  private function validateQuotationStock(ApOrderQuotations $quotation, int $is_advance_payment = 0): void
  {
    // Si es un anticipo, no validamos stock
    if ($is_advance_payment == 1) {
      return;
    }

    // Get warehouse from sede
    $warehouse = Warehouse::where('sede_id', $quotation->sede_id)
      ->where('is_physical_warehouse', 1)
      ->where('status', 1)
      ->first();

    if (!$warehouse) {
      throw new Exception('No se encontró un almacén físico activo para la sede seleccionada.');
    }

    // Get all product details from quotation
    $productDetails = $quotation->details->where('item_type', 'PRODUCT');

    if ($productDetails->isEmpty()) {
      throw new Exception('La cotización no tiene productos para validar stock.');
    }

    // Instanciar InventoryMovementService para validaciones en sistema externo
    $inventoryMovementService = app(InventoryMovementService::class);

    // Check stock for each product
    foreach ($productDetails as $detail) {
      // Skip if no product_id
      if (!$detail->product_id) {
        continue;
      }

      // Get stock for this product in this warehouse
      $stock = ProductWarehouseStock::where('warehouse_id', $warehouse->id)
        ->where('product_id', $detail->product_id)
        ->first();

      // If no stock record found or insufficient available quantity, throw exception
      if (!$stock || $stock->available_quantity < $detail->quantity) {
        throw new Exception('No hay stock suficiente para el producto: ' . $detail->product->description);
      }

      // Validar stock en sistema externo (Dynamics) si el producto y almacén tienen dyn_code
      if ($detail->product && $detail->product->dyn_code && $warehouse->dyn_code) {
        try {
          $externalStock = $inventoryMovementService->validateStockInExternalSystem(
            $detail->product->dyn_code,
            $warehouse->dyn_code
          );

          // El SP retorna ArticuloStock como string, convertir a float para comparar
          $availableQuantityExternal = isset($externalStock['ArticuloStock'])
            ? (float)trim($externalStock['ArticuloStock'])
            : 0;

          if ($availableQuantityExternal < $detail->quantity) {
            throw new Exception(
              "Stock insuficiente en sistema externo para el producto: {$detail->product->description}. " .
              "Stock disponible en Dynamics: {$availableQuantityExternal}, Cantidad requerida: {$detail->quantity}"
            );
          }
        } catch (Exception $e) {
          // Si falla la validación en sistema externo, propagar la excepción
          throw new Exception(
            "Error al validar stock externo para el producto '{$detail->product->description}': " . $e->getMessage()
          );
        }
      }
    }
  }

  /**
   * Validar stock reservado para Orden de Trabajo
   * Valida que exista suficiente reserved_quantity para los repuestos de la OT
   *
   * @param ApWorkOrder $workOrder
   * @param int $is_advance_payment
   * @return void
   * @throws Exception
   */
  private function validateWorkOrderStock(ApWorkOrder $workOrder, int $is_advance_payment = 0): void
  {
    // Si es un anticipo, no validamos stock
    if ($is_advance_payment == 1) {
      return;
    }

    // Cargar repuestos de la orden de trabajo
    $workOrder->load(['parts.product', 'sede']);

    // Si no hay repuestos, no hay nada que validar
    if ($workOrder->parts->isEmpty()) {
      return;
    }

    // Obtener almacén físico de la sede
    $warehouse = Warehouse::where('sede_id', $workOrder->sede_id)
      ->where('is_physical_warehouse', 1)
      ->where('status', 1)
      ->first();

    if (!$warehouse) {
      throw new Exception('No se encontró un almacén físico activo para la sede de la orden de trabajo.');
    }

    // Instanciar InventoryMovementService para validaciones en sistema externo
    $inventoryMovementService = app(InventoryMovementService::class);

    // Validar stock reservado para cada repuesto
    foreach ($workOrder->parts as $part) {
      // Omitir si no tiene product_id
      if (!$part->product_id) {
        continue;
      }

      // Obtener registro de stock para este producto en el almacén
      $stock = ProductWarehouseStock::where('warehouse_id', $part->warehouse_id)
        ->where('product_id', $part->product_id)
        ->first();

      // Validar que exista stock y que haya suficiente cantidad reservada
      if (!$stock) {
        throw new Exception(
          "No se encontró registro de stock para el repuesto: {$part->product->description}"
        );
      }

      // Validar que el stock reservado sea suficiente
      if ($stock->reserved_quantity < $part->quantity_used) {
        throw new Exception(
          "Stock reservado insuficiente para el repuesto: {$part->product->description}. " .
          "Stock reservado: {$stock->reserved_quantity}, Cantidad requerida: {$part->quantity_used}"
        );
      }

      // Validar stock en sistema externo (Dynamics) si el producto y almacén tienen dyn_code
      if ($part->product && $part->product->dyn_code && $warehouse->dyn_code) {
        try {
          $externalStock = $inventoryMovementService->validateStockInExternalSystem(
            $part->product->dyn_code,
            $warehouse->dyn_code
          );

          // El SP retorna ArticuloStock como string, convertir a float para comparar
          $availableQuantityExternal = isset($externalStock['ArticuloStock'])
            ? (float)trim($externalStock['ArticuloStock'])
            : 0;

          if ($availableQuantityExternal < $part->quantity_used) {
            throw new Exception(
              "Stock insuficiente en sistema externo para el repuesto: {$part->product->description}. " .
              "Stock disponible en Dynamics: {$availableQuantityExternal}, Cantidad requerida: {$part->quantity_used}"
            );
          }
        } catch (Exception $e) {
          // Si falla la validación en sistema externo, propagar la excepción
          throw new Exception(
            "Error al validar stock externo para el repuesto '{$part->product->description}': " . $e->getMessage()
          );
        }
      }
    }
  }

  /**
   * Validate work order invoice constraints
   * - Work order must have at least labours or parts to invoice
   * - Invoice amount cannot exceed work order total
   * - Currency must be consistent with previous invoices
   * - Advance payment cannot be 0
   *
   * @param array $data
   * @return void
   * @throws Exception
   */
  private function validateWorkOrderInvoice(array $data): void
  {
    $workOrderId = $data['work_order_id'];
    $isAdvancePayment = isset($data['is_advance_payment']) && $data['is_advance_payment'] == 1;
    $newTotal = (float)($data['total'] ?? 0);
    $currencyId = $data['sunat_concept_currency_id'] ?? null;

    $workOrder = ApWorkOrder::with(['labours', 'parts.deliveries', 'advancesWorkOrder', 'items.typePlanning'])->find($workOrderId);

    if (!$workOrder) {
      throw new Exception('Orden de trabajo no encontrada.');
    }

    if ($workOrder->status_id == ApMasters::CANCELED_WORK_ORDER_ID) {
      throw new Exception('No se puede facturar una orden de trabajo cancelada.');
    }

    $validateLabor = $workOrder->items->first()?->typePlanning->validate_labor;

    if ($workOrder->status_id == ApMasters::AT_WORK_WORK_ORDER_ID && !$isAdvancePayment && $validateLabor) {
      throw new Exception('No se puede facturar una OT que aún no ha sido finalizado su trabajo.');
    }

    if (!$isAdvancePayment && $validateLabor) {
      $laboursWithWorker = $workOrder->plannings->filter(function ($labour) {
        return $labour->worker_id !== null && $labour->deleted_at === null;
      });

      if ($laboursWithWorker->count() === 0) {
        throw new Exception('La orden de trabajo debe tener al menos una mano de obra con trabajador asignado.');
      }
    }

    // Validate that all parts are fully delivered if work order has parts
    if (!$isAdvancePayment && $workOrder->parts->count() > 0) {
      $partsNotFullyDelivered = [];

      foreach ($workOrder->parts as $part) {
        // Calculate total delivered quantity for this part (excluding soft deleted deliveries)
        $totalDelivered = $part->deliveries
          ->whereNull('deleted_at')
          ->sum('delivered_quantity');

        // Compare with quantity_used
        $quantityUsed = (float)$part->quantity_used;
        $totalDelivered = (float)$totalDelivered;

        // If not fully delivered, add to list
        if ($totalDelivered < $quantityUsed) {
          $partsNotFullyDelivered[] = sprintf(
            '%s (Usado: %.2f, Entregado: %.2f, Pendiente: %.2f)',
            $part->product->name ?? "Producto ID: {$part->product_id}",
            $quantityUsed,
            $totalDelivered,
            $quantityUsed - $totalDelivered
          );
        }
      }

      if (count($partsNotFullyDelivered) > 0) {
        throw new Exception(
          'No se puede facturar la orden de trabajo. Los siguientes repuestos no han sido entregados en su totalidad: ' .
          implode('; ', $partsNotFullyDelivered)
        );
      }
    }

    // Calculate work order total using centralized method (includes labour, parts, discount, and tax)
    $workOrderTotal = (float)$workOrder->final_amount;

    // Validate that work order has at least labours or parts to invoice
    if ($workOrderTotal <= 0 && !$isAdvancePayment) {
      throw new Exception('La orden de trabajo no tiene mano de obra ni repuestos para facturar.');
    }

    // Calculate total already invoiced (accepted documents, not cancelled)
    $totalInvoiced = ElectronicDocument::where('work_order_id', $workOrderId)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', false)
      ->whereNull('deleted_at')
      ->sum('total');

    // Calculate total with new invoice and round to 2 decimals to avoid floating point precision issues
    $totalWithNewInvoice = round($totalInvoiced + $newTotal, 2);
    $workOrderTotal = round($workOrderTotal, 2);

    // Validate that invoice does not exceed work order total
    if ($totalWithNewInvoice > $workOrderTotal) {
      throw new Exception(sprintf(
        'El monto total a facturar (%.2f) excede el monto de la orden de trabajo (%.2f) que incluye IGV. Ya hay %.2f facturado.',
        $totalWithNewInvoice,
        $workOrderTotal,
        $totalInvoiced
      ));
    }

    // Validate currency consistency with previous invoices
    $firstInvoice = ElectronicDocument::where('work_order_id', $workOrderId)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', false)
      ->whereNull('deleted_at')
      ->orderBy('created_at', 'asc')
      ->first();

    if ($firstInvoice && $currencyId && $firstInvoice->sunat_concept_currency_id != $currencyId) {
      throw new Exception('El tipo de moneda debe ser el mismo que la primera factura emitida para esta orden de trabajo.');
    }

    // Validate advance payment is not 0
    if ($isAdvancePayment && $newTotal <= 0) {
      throw new Exception('Un anticipo no puede ser por 0. El total debe ser mayor a 0.');
    }

    // Validate advance payments don't exceed work order total
    if ($isAdvancePayment) {
      $totalAdvances = ElectronicDocument::where('work_order_id', $workOrderId)
        ->where('is_advance_payment', 1)
        ->where('aceptada_por_sunat', true)
        ->where('anulado', false)
        ->whereNull('deleted_at')
        ->sum('total');

      // Round to 2 decimals to avoid floating point precision issues
      $totalAdvancesWithNew = round($totalAdvances + $newTotal, 2);

      if ($totalAdvancesWithNew > $workOrderTotal) {
        throw new Exception(sprintf(
          'La suma de anticipos (%.2f) excede el monto total de la orden de trabajo (%.2f) que incluye IGV. Ya hay %.2f en anticipos existentes.',
          $totalAdvancesWithNew,
          $workOrderTotal,
          $totalAdvances
        ));
      }
    }
  }

  /**
   * Validate internal sale total
   * When is_advance_payment = 0, validates that sum of advances + current invoice equals the total amount
   *
   * @param array $data
   * @return void
   * @throws Exception
   */
  private function validateInternalSaleTotal(array $data): void
  {
    $isAdvancePayment = isset($data['is_advance_payment']) && $data['is_advance_payment'] == 1;

    // Only validate for internal sales (is_advance_payment = 0)
    if ($isAdvancePayment) {
      return;
    }

    $newTotal = (float)($data['total'] ?? 0);
    $entityTotal = 0;
    $entityName = '';
    $entityId = null;
    $entityField = null;

    // Determine which entity we're working with
    if (isset($data['order_quotation_id']) && $data['order_quotation_id']) {
      $entityField = 'order_quotation_id';
      $entityId = $data['order_quotation_id'];
      $quotation = ApOrderQuotations::find($entityId);
      if ($quotation) {
        $entityTotal = (float)$quotation->total_amount;
        $entityName = 'cotización';
      }
    } elseif (isset($data['work_order_id']) && $data['work_order_id']) {
      $entityField = 'work_order_id';
      $entityId = $data['work_order_id'];
      $workOrder = ApWorkOrder::with(['labours', 'parts'])->find($entityId);
      if ($workOrder) {
        // Calculate total using centralized method (includes labour, parts, discount, and tax)
        $entityTotal = (float)$workOrder->final_amount;
        $entityName = 'orden de trabajo';
      }
    } elseif (isset($data['purchase_request_quote_id']) && $data['purchase_request_quote_id']) {
      $entityField = 'purchase_request_quote_id';
      $entityId = $data['purchase_request_quote_id'];
      $purchaseRequestQuote = PurchaseRequestQuote::find($entityId);
      if ($purchaseRequestQuote) {
        $entityTotal = (float)$purchaseRequestQuote->doc_sale_price;
        $entityName = 'solicitud de cotización';
      }
    }

    // If no entity found, skip validation
    if (!$entityId || !$entityField || $entityTotal <= 0) {
      return;
    }

    // Calculate total advances already accepted by SUNAT
    $totalAdvances = ElectronicDocument::where($entityField, $entityId)
      ->where('is_advance_payment', 1)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', false)
      ->whereNull('deleted_at')
      ->sum('total');

    // Calculate expected total (advances + current invoice)
    // Round to 2 decimals to ensure exact comparison
    $totalWithNewInvoice = round($totalAdvances + $newTotal, 2);
    $entityTotal = round($entityTotal, 2);

    // Validate that sum equals entity total (exact match required)
    if ($totalWithNewInvoice != $entityTotal) {
      throw new Exception(sprintf(
        'La suma de anticipos (%.2f) más el monto de esta factura (%.2f) debe ser igual al monto total de la %s (%.2f). Total actual: %.2f',
        $totalAdvances,
        $newTotal,
        $entityName,
        $entityTotal,
        $totalWithNewInvoice
      ));
    }
  }

  private function updateWorkOrderInvoiceStatus(int $workOrderId, bool $isAdvancePayment = true): void
  {
    try {
      $workOrder = ApWorkOrder::find($workOrderId);

      if (!$workOrder) {
        return;
      }

      // Siempre marcar que se generó factura
      $workOrder->update(['has_invoice_generated' => true]);

      // Calcular total pagado (suma de todos los documentos aceptados por SUNAT)
      $totalPaid = ElectronicDocument::where('work_order_id', $workOrderId)
        ->where('aceptada_por_sunat', true)
        ->where('anulado', false)
        ->whereNull('deleted_at')
        ->sum('total');

      // Verificar si la suma de anticipos coincide con el monto total de la OT
      // Marcar como totalmente facturado si el total pagado >= monto final de la OT y no sea un anticipo (la venta interna es la que cierra la operación)
      if ((float)$totalPaid >= (float)$workOrder->final_amount && !$isAdvancePayment) {
        $workOrder->update(['is_invoiced' => true]);
      }

      // Si no es anticipo (es venta interna), cerrar la orden de trabajo
      if (!$isAdvancePayment) {
        $workOrder->update(['status_id' => ApMasters::CLOSED_WORK_ORDER_ID]);
      }
    } catch (Exception $e) {
      Log::error('Error updating work order invoice status', [
        'work_order_id' => $workOrderId,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Enriquece los campos `codigo` y `dyn_code` de cada item desde el producto.
   * El frontend envía `product_id` (opcional) en cada item.
   * No aplica a items de anticipo (anticipo_regularizacion = true).
   */
  private function enrichItemsCodigoFromQuotation(array &$items, int $quotationId): void
  {
    // Recopilar todos los product_ids de items que no son anticipos
    $productIds = [];
    foreach ($items as $item) {
      // Saltar items de anticipo
      if (!empty($item['anticipo_regularizacion'])) {
        continue;
      }

      // Recopilar product_id si existe
      if (!empty($item['product_id'])) {
        $productIds[] = $item['product_id'];
      }
    }

    // Si no hay product_ids, no hay nada que mapear
    if (empty($productIds)) {
      return;
    }

    // Cargar todos los productos de una vez
    $products = Products::whereIn('id', array_unique($productIds))
      ->get()
      ->keyBy('id');

    // Mapear codigo y dyn_code para cada item
    foreach ($items as &$item) {
      // Saltar items de anticipo
      if (!empty($item['anticipo_regularizacion'])) {
        continue;
      }

      // Si tiene product_id, mapear desde el producto
      if (!empty($item['product_id'])) {
        $product = $products->get($item['product_id']);

        if ($product) {
          if ($product->code) {
            $item['codigo'] = $product->code;
          }
          if ($product->dyn_code) {
            $item['dyn_code'] = $product->dyn_code;
          }
        }
      }
    }
  }

  /**
   * Enriquece los campos `codigo` y `dyn_code` de cada item desde una orden de trabajo.
   * Usa el campo `codigo` del item (enviado por el frontend) como ID de part o labour.
   * - Repuestos (parts con product_id): mapea codigo y dyn_code del producto.
   * - Mano de obra (labours sin product_id): valor fijo 'V0000011' o 'V0000012'.
   */
  private function enrichItemsCodigoFromWorkOrder(array &$items, int $workOrderId): void
  {
    $workOrder = ApWorkOrder::with(['parts.product', 'labours'])->find($workOrderId);

    if (!$workOrder) {
      return;
    }

    $labourCode = ApAccountingAccountPlan::find(ApAccountingAccountPlan::LABOUR_ACCOUNT_ID)?->code ?? 'V0000011';

    // Indexar parts y labours por ID para búsqueda rápida
    $parts = $workOrder->parts->keyBy('id');
    $labours = $workOrder->labours->keyBy('id');

    foreach ($items as $index => &$item) {
      // Saltar items de anticipo
      if (!empty($item['anticipo_regularizacion'])) {
        continue;
      }

      // Si el item tiene product_id, buscar el producto directamente para mapear código y dyn_code
      if (!empty($item['product_id'])) {
        $product = Products::find($item['product_id']);
        if ($product) {
          if ($product->code) {
            $item['codigo'] = $product->code;
          }
          if ($product->dyn_code) {
            $item['dyn_code'] = $product->dyn_code;
          }
        }
        continue;
      }

      $itemId = $item['codigo'] ?? null;

      if (!$itemId) {
        continue;
      }

      // Intentar buscar como part
      $part = $parts->get($itemId);
      if ($part && $part->product) {
        if ($part->product->code) {
          $item['codigo'] = $part->product->code;
        }
        if ($part->product->dyn_code) {
          $item['dyn_code'] = $part->product->dyn_code;
        }
        continue;
      }

      // Intentar buscar como labour
      $labour = $labours->get($itemId);
      if ($labour) {
        $descripcionNormalizada = trim(strtolower($labour->description ?? ''));

        if ($descripcionNormalizada === 'materiales') {
          $materialsCode = ApAccountingAccountPlan::find(ApAccountingAccountPlan::LABOUR_ACCOUNT_MATERIAL_ID)?->code ?? 'V0000012';
          $item['codigo'] = $materialsCode;
        } else {
          $item['codigo'] = $labourCode;
        }
      }
    }
  }

  // ========================================================================
  // MÉTODOS DE VALIDACIÓN Y ENRIQUECIMIENTO POR TIPO DE ENTIDAD
  // ========================================================================

  /**
   * Validar y enriquecer datos para Order Quotation
   *
   * @param array $data
   * @return void
   * @throws Exception
   */
  private function validateAndEnrichForQuotation(array &$data): void
  {
    if (!isset($data['order_quotation_id']) || !$data['order_quotation_id']) {
      return;
    }

    $quotation = ApOrderQuotations::find($data['order_quotation_id']);

    // Validar que cotización no esté descartada
    if ($quotation->status === ApOrderQuotations::STATUS_DESCARTADO) {
      throw new Exception('No se puede generar un documento electrónico para una cotización descartada.');
    }

    // Validar stock de productos si no es un anticipo
    $this->validateQuotationStock($quotation, $data['is_advance_payment']);

    // Validar suma de anticipos si es anticipo
    if (isset($data['is_advance_payment']) && $data['is_advance_payment'] == 1) {
      $total = (float)($data['total'] ?? 0);

      // Sumar todos los anticipos aceptados por SUNAT para esta cotización
      $totalAnticiposExistentes = ElectronicDocument::where('order_quotation_id', $data['order_quotation_id'])
        ->where('is_advance_payment', 1)
        ->where('aceptada_por_sunat', true)
        ->where('anulado', false)
        ->whereNull('deleted_at')
        ->sum('total');

      // Sumar el nuevo anticipo
      $totalAnticiposConNuevo = $totalAnticiposExistentes + $total;

      // Validar que no exceda el total de la cotización
      if ($totalAnticiposConNuevo > $quotation->total_amount) {
        throw new Exception(sprintf(
          'La suma de anticipos (%.2f) excede el monto total de la cotización (%.2f). Ya hay %.2f en anticipos existentes.',
          $totalAnticiposConNuevo,
          $quotation->total_amount,
          $totalAnticiposExistentes
        ));
      }
    }
  }

  /**
   * Validar y enriquecer datos para Work Order
   *
   * @param array $data
   * @return void
   * @throws Exception
   */
  private function validateAndEnrichForWorkOrder(array &$data): void
  {
    if (!isset($data['work_order_id']) || !$data['work_order_id']) {
      return;
    }

    // Validar reglas de negocio de la orden de trabajo
    $this->validateWorkOrderInvoice($data);

    // Validar stock reservado para repuestos de la OT
    $workOrder = ApWorkOrder::find($data['work_order_id']);
    if ($workOrder) {
      $this->validateWorkOrderStock($workOrder, $data['is_advance_payment'] ?? 0);
    }
  }

  // ========================================================================
  // MÉTODOS DE LÓGICA DE DETRACCIONES POR TIPO DE ENTIDAD
  // ========================================================================

  /**
   * Aplicar lógica de detracción para Order Quotation
   *
   * @param array $data
   * @param Company $company
   * @return float Entity total
   */
  private function applyDetractionForQuotation(array &$data, Company $company): float
  {
    // Para quotation, no hay una entidad total específica en este contexto
    // Solo retornar 0, la detracción se calculará sobre el monto del documento
    return 0;
  }

  /**
   * Aplicar lógica de detracción para Work Order
   *
   * @param array $data
   * @param Company $company
   * @return float Entity total
   */
  private function applyDetractionForWorkOrder(array &$data, Company $company): float
  {
    $workOrder = ApWorkOrder::with(['labours', 'parts'])->find($data['work_order_id']);

    if (!$workOrder) {
      return 0;
    }

    $entityTotal = (float)$workOrder->final_amount;
    $detractionAmount = (float)($company->detraction_amount ?? 0);

    /**
     * Calcular sunat_concept_transaction_type_id para work_order
     * Ignorar el valor enviado por el frontend y calcularlo según reglas de negocio
     */
    $isAdvancePayment = isset($data['is_advance_payment']) && $data['is_advance_payment'];

    // Determinar el concepto base según el tipo de documento
    if ($isAdvancePayment) {
      // Es un anticipo
      $transactionConceptId = SunatConcepts::ID_VENTA_INTERNA_ANTICIPOS;
    } else {
      // Es venta interna final
      $transactionConceptId = SunatConcepts::ID_VENTA_INTERNA;
    }

    // Si el monto total de la work_order supera o iguala el monto de detracción,
    // aplicar ID_SUJETA_DETRACCION a todos los documentos de esta orden
    if ($entityTotal >= $detractionAmount && $detractionAmount > 0) {
      $transactionConceptId = SunatConcepts::ID_SUJETA_DETRACCION;
    }

    // Sobrescribir el valor enviado por el frontend
    $data['sunat_concept_transaction_type_id'] = $transactionConceptId;

    return $entityTotal;
  }

  /**
   * Aplicar lógica de detracciones (orquestador principal)
   *
   * @param array $data
   * @return void
   */
  private function applyDetractionLogic(array &$data): void
  {
    if (!in_array($data['area_id'], [ApMasters::AREA_COMERCIAL, ApMasters::AREA_TALLER])) {
      return;
    }

    $company = Company::find(Company::COMPANY_AP_ID);
    if (!$company || !isset($data['total'])) {
      return;
    }

    $detractionAmount = (float)($company->detraction_amount ?? 0);
    $entityTotal = 0;

    // Determinar el tipo de entidad y aplicar su lógica específica
    if (isset($data['work_order_id']) && $data['work_order_id'] && (int)$data['area_id'] === ApMasters::AREA_TALLER) {
      $entityTotal = $this->applyDetractionForWorkOrder($data, $company);
      $amountToCheck = $entityTotal > 0 ? $entityTotal : (float)$data['total'];

      if ($amountToCheck >= $detractionAmount && $detractionAmount > 0) {
        $data['detraccion'] = true;
        $data['sunat_concept_detraction_type_id'] = match ((int)$data['area_id']) {
          ApMasters::AREA_TALLER => SunatConcepts::ID_DETRACTION_MANTENIMIENTO_REPACION,
          default => SunatConcepts::ID_DETRACTION_SERVICIOS
        };

        // Obtener el porcentaje de detracción desde GeneralMaster
        $detractionPercentage = GeneralMaster::find(GeneralMaster::SUNAT_DETRACTION_PERCENTAGE_ID);
        if ($detractionPercentage) {
          $porcentaje = (float)$detractionPercentage->value;
          $data['detraccion_porcentaje'] = $porcentaje;
          // La detracción se calcula sobre el total del documento actual
          $data['detraccion_total'] = (float)$data['total'] * ($porcentaje / 100);
        }
      }
    } else if (isset($data['detraccion']) && $data['detraccion']) {
      $data['sunat_concept_transaction_type_id'] = SunatConcepts::ID_SUJETA_DETRACCION;
    }

    if (isset($data['detraccion'])) {
      // Para el área comercial, solo marcar como sujeta a detracción sin importar el monto
      $data['detraccion'] = true;
      $data['sunat_concept_detraction_type_id'] = SunatConcepts::ID_DETRACTION_SERVICIOS;

      // Obtener el porcentaje de detracción desde GeneralMaster
      $detractionPercentage = GeneralMaster::find(GeneralMaster::SUNAT_DETRACTION_PERCENTAGE_ID);
      if ($detractionPercentage) {
        $porcentaje = (float)$detractionPercentage->value;
        $data['detraccion_porcentaje'] = $porcentaje;
        // La detracción se calcula sobre el total del documento actual
        $data['detraccion_total'] = (float)$data['total'] * ($porcentaje / 100);
      }
    }
  }

  // ========================================================================
  // MÉTODOS DE POST-PROCESAMIENTO POR TIPO DE ENTIDAD
  // ========================================================================

  /**
   * Post-procesamiento para Order Quotation
   *
   * @param ElectronicDocument $document
   * @param array $data
   * @return void
   */
  private function afterCreateForQuotation(ElectronicDocument $document, array &$data): void
  {
    if (!isset($data['order_quotation_id']) || !$data['order_quotation_id']) {
      return;
    }

    // Marcar quotation con has_invoice_generated
    $this->updateQuotationInvoiceStatus($data['order_quotation_id']);
  }

  /**
   * Post-procesamiento para Work Order
   *
   * @param ElectronicDocument $document
   * @param array $data
   * @return void
   */
  private function afterCreateForWorkOrder(ElectronicDocument $document, array &$data): void
  {
    if (!isset($data['work_order_id']) || !$data['work_order_id']) {
      return;
    }

    // Marcar work_order con has_invoice_generated
    $workOrder = ApWorkOrder::find($data['work_order_id']);

    if (!$workOrder) {
      return;
    }

    $workOrder->update(['has_invoice_generated' => true]);
  }

  /*
   * Consulta Dynamics y actualiza is_accounted e is_annulled para todos los
   * documentos que han sido solicitados a Dynamics y cuya migración está completada.
   *
   * @return array
   */
  public function syncAccountingStatusFromDynamics(): array
  {
    $documents = ElectronicDocument::where('migration_status', VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED)
      ->get();

    $results = [];
    $errors = [];

    foreach ($documents as $document) {
      try {
        $sopRecord = DB::connection('dbtest')
          ->table('SOP30200')
          ->where('SOPNUMBE', 'like', '%' . $document->full_number . '%')
          ->first();

        $source = null;

        if ($sopRecord) {
          $isAnnulled = $sopRecord->VOIDSTTS == "1";
          $source = 'SOP30200';

          // Segunda opinión: si SOP30200 no lo marca como anulado, consultamos RM20101
          if (!$isAnnulled) {
            $rmRecord = DB::connection('dbtest')
              ->table('RM20101')
              ->where('DOCNUMBR', 'like', '%' . $document->full_number . '%')
              ->whereNot('RMDTYPAL', '9') // Excluir documentos de tipo 9 (cobros)
              ->first();

            if ($rmRecord) {
              $isAnnulled = $rmRecord->VOIDSTTS == "1";
              $source = 'RM20101';
            }
          }

          $document->update([
            'is_accounted' => true,
            'is_annulled' => $isAnnulled,
          ]);
        } else {
          $isAnnulled = false;
          $document->update([
            'is_accounted' => false,
            'is_annulled' => false,
          ]);
        }

        $results[] = [
          'document_id' => $document->id,
          'full_number' => $document->full_number,
          'is_accounted' => $document->is_accounted,
          'is_annulled' => $isAnnulled,
          'source' => $source,
        ];
      } catch (Throwable $e) {
        $errors[] = [
          'document_id' => $document->id,
          'full_number' => $document->full_number,
          'error' => $e->getMessage(),
        ];
      }
    }

    return [
      'total' => $documents->count(),
      'updated' => count($results),
      'results' => $results,
      'errors' => $errors,
    ];
  }

  public function createConsolidatedInvoice(array $data): array
  {
    DB::beginTransaction();
    try {
      // 1. Get and validate internal notes
      $internalNotes = ApInternalNote::whereIn('id', $data['internal_note_ids'])
        ->pending()
        ->with(['workOrder.invoiceTo', 'workOrder.typeCurrency'])
        ->get();

      if ($internalNotes->isEmpty()) {
        throw new Exception('No se encontraron notas internas pendientes con los IDs proporcionados');
      }

      if ($internalNotes->count() !== count($data['internal_note_ids'])) {
        throw new Exception('Una o más notas internas no están disponibles para facturación (pueden estar ya facturadas o no existir)');
      }

      // 2. Validate all work orders belong to the same client
      $clientIds = $internalNotes->pluck('workOrder.invoice_to')->unique();
      if ($clientIds->count() > 1) {
        throw new Exception('Todas las órdenes de trabajo deben pertenecer al mismo cliente para consolidar');
      }

      $clientId = $clientIds->first();
      if (!$clientId) {
        throw new Exception('Las órdenes de trabajo no tienen un cliente asignado');
      }

      // 3. Validate all work orders have the same currency
      $currencyIds = $internalNotes->pluck('workOrder.currency_id')->unique();
      if ($currencyIds->count() > 1) {
        throw new Exception('Todas las órdenes de trabajo deben tener la misma moneda para consolidar');
      }

      $data['series_id'] = $data['serie'];
      $data['serie'] = AssignSalesSeries::find($data['serie'])->series;

      // 4. Calculate totals from work orders
      $subtotal = 0;
      $taxAmount = 0;
      $total = 0;
      $workOrdersData = [];

      foreach ($internalNotes as $note) {
        $workOrder = $note->workOrder;
        $subtotal += (float)$workOrder->subtotal;
        $taxAmount += (float)$workOrder->tax_amount;
        $total += (float)$workOrder->final_amount;

        $workOrdersData[] = [
          'work_order_id' => $workOrder->id,
          'correlative' => $workOrder->correlative,
          'internal_note_number' => $note->number,
          'subtotal' => $workOrder->subtotal,
          'tax_amount' => $workOrder->tax_amount,
          'final_amount' => $workOrder->final_amount,
        ];

        // actualizamos el estado de has_invoice_generated del ApWorkOrder
        $workOrder->update(['has_invoice_generated' => true]);
      }

      // 5. Get client data
      $client = BusinessPartners::find($clientId);
      if (!$client) {
        throw new Exception('Cliente no encontrado');
      }

      // 6. Get exchange rate
      $emissionDate = is_string($data['fecha_de_emision'])
        ? $data['fecha_de_emision']
        : Carbon::parse($data['fecha_de_emision'])->format('Y-m-d');

      $exchangeRate = (new ExchangeRateService())->getExchangeRate(TypeCurrency::USD_ID, $emissionDate);
      if (!$exchangeRate) {
        throw new Exception("No se ha registrado la tasa de cambio para la fecha de emisión ({$emissionDate}).");
      }

      // 7. Get document type
      $documentType = SunatConcepts::where('tribute_code', $client->document_type_id)
        ->where('type', SunatConcepts::TYPE_DOCUMENT)
        ->first();

      // 8. Get next correlative number
      $nextNumberData = $this->nextDocumentNumberCorrelative(
        $data['sunat_concept_document_type_id'],
        $data['serie']
      );

      // 9. Validate serie
      if (!ElectronicDocument::validateSerie($data['sunat_concept_document_type_id'], $data['serie'])) {
        throw new Exception('La serie no es válida para el tipo de documento seleccionado');
      }

      // 10. Prepare invoice data
      $invoiceData = [
        'sunat_concept_document_type_id' => $data['sunat_concept_document_type_id'],
        'serie' => $data['serie'],
        'series_id' => $data['series_id'],
        'numero' => $nextNumberData['number'],
        'full_number' => "{$data['serie']}-{$nextNumberData['number']}",
        'consolidation_type' => ElectronicDocument::CONSOLIDATION_WORK_ORDERS,
        'client_id' => $clientId,
        'sunat_concept_identity_document_type_id' => $documentType->id,
        'cliente_numero_de_documento' => $client->num_doc,
        'cliente_denominacion' => $client->full_name . ($client->spouse_full_name ? ' - ' . $client->spouse_full_name : ''),
        'cliente_direccion' => $client->direction,
        'cliente_email' => $client->email,
        'fecha_de_emision' => $emissionDate,
        'fecha_de_vencimiento' => $data['fecha_de_vencimiento'] ?? null,
        'sunat_concept_currency_id' => $data['sunat_concept_currency_id'],
        'tipo_de_cambio' => $exchangeRate->rate,
        'exchange_rate_id' => $exchangeRate->id,
        'porcentaje_de_igv' => $client->taxClassType->igv,
        'total_gravada' => round($subtotal, 2),
        'total_igv' => round($taxAmount, 2),
        'total' => round($total, 2),
        'observaciones' => $data['observaciones'] ?? "Factura consolidada de " . $internalNotes->count() . " órdenes de trabajo",
        'enviar_automaticamente_a_la_sunat' => false,
        'enviar_automaticamente_al_cliente' => false,
        'created_by' => auth()->id(),
        'sunat_concept_transaction_type_id' => $data['sunat_concept_transaction_type_id'] ?? null,
        'area_id' => ApMasters::AREA_POSVENTA,
      ];

      // 11. Apply detraction logic for consolidated work orders
      $company = Company::find(Company::COMPANY_AP_ID);
      if ($company && isset($invoiceData['total'])) {
        $detractionAmount = (float)($company->detraction_amount ?? 0);

        // Verificar si el total de la factura consolidada supera o iguala el monto de detracción
        if ($total >= $detractionAmount && $detractionAmount > 0) {
          $invoiceData['detraccion'] = true;
          $invoiceData['sunat_concept_detraction_type_id'] = SunatConcepts::ID_DETRACTION_MANTENIMIENTO_REPACION;
          $invoiceData['sunat_concept_transaction_type_id'] = SunatConcepts::ID_SUJETA_DETRACCION;

          // Obtener el porcentaje de detracción desde GeneralMaster
          $detractionPercentage = GeneralMaster::find(GeneralMaster::SUNAT_DETRACTION_PERCENTAGE_ID);
          if ($detractionPercentage) {
            $porcentaje = (float)$detractionPercentage->value;
            $invoiceData['detraccion_porcentaje'] = $porcentaje;
            // La detracción se calcula sobre el total del documento
            $invoiceData['detraccion_total'] = (float)$invoiceData['total'] * ($porcentaje / 100);
          }
        }
      }

      // 12. Create invoice
      $invoice = ElectronicDocument::create($invoiceData);

      // 13. Create invoice items from frontend data
      $lineNumber = 1;
      foreach ($data['items'] as $item) {
        $invoice->items()->create([
          'line_number' => $lineNumber++,
          'unidad_de_medida' => $item['unidad_de_medida'],
          'codigo' => $item['codigo'],
          'descripcion' => $item['descripcion'],
          'cantidad' => $item['cantidad'],
          'valor_unitario' => round((float)$item['valor_unitario'], 2),
          'precio_unitario' => round((float)$item['precio_unitario'], 2),
          'subtotal' => round((float)$item['subtotal'], 2),
          'sunat_concept_igv_type_id' => $item['sunat_concept_igv_type_id'],
          'igv' => round((float)$item['igv'], 2),
          'total' => round((float)$item['total'], 2),
          'account_plan_id' => $item['account_plan_id'] ?? null,
        ]);
      }

      // 14. Attach internal notes to invoice
      $invoice->internalNotes()->attach($data['internal_note_ids']);

      // 15. Mark internal notes as invoiced
      foreach ($internalNotes as $note) {
        $note->markAsInvoiced($emissionDate);
      }

      DB::commit();

      return [
        'invoice' => new ElectronicDocumentResource($invoice->load('internalNotes.workOrder', 'items')),
        'work_orders' => $workOrdersData,
        'message' => 'Factura consolidada creada exitosamente',
        'totals' => [
          'subtotal' => round($subtotal, 2),
          'tax_amount' => round($taxAmount, 2),
          'total' => round($total, 2),
          'work_orders_count' => $internalNotes->count(),
        ],
      ];

    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Get invoice with internal notes and their work orders
   *
   * @param int $invoiceId
   * @return array
   * @throws Exception
   */
  public function getInvoiceWithWorkOrders(int $invoiceId): array
  {
    $invoice = ElectronicDocument::with([
      'internalNotes.workOrder.invoiceTo',
      'internalNotes.workOrder.typeCurrency',
      'internalNotes.workOrder.items',
      'documentType',
      'client',
      'currency'
    ])->find($invoiceId);

    if (!$invoice) {
      throw new Exception('Factura no encontrada');
    }

    // Prepare internal notes with work orders data
    $internalNotesData = $invoice->internalNotes->map(function ($note) {
      return [
        'id' => $note->id,
        'number' => $note->number,
        'status' => $note->status,
        'created_date' => $note->created_date?->format('Y-m-d'),
        'closed_date' => $note->closed_date?->format('Y-m-d'),
        'work_order' => $note->workOrder ? [
          'id' => $note->workOrder->id,
          'correlative' => $note->workOrder->correlative,
          'invoice_to' => $note->workOrder->invoice_to,
          'client_name' => $note->workOrder->invoiceTo?->razon_social,
          'currency_id' => $note->workOrder->currency_id,
          'currency' => $note->workOrder->typeCurrency?->name,
          'subtotal' => $note->workOrder->subtotal,
          'tax_amount' => $note->workOrder->tax_amount,
          'final_amount' => $note->workOrder->final_amount,
          'status' => $note->workOrder->status,
        ] : null,
      ];
    });

    return [
      'invoice' => [
        'id' => $invoice->id,
        'full_number' => $invoice->full_number,
        'serie' => $invoice->serie,
        'numero' => $invoice->numero,
        'document_type' => $invoice->documentType?->description,
        'client_name' => $invoice->cliente_denominacion,
        'client_document' => $invoice->cliente_numero_de_documento,
        'emission_date' => $invoice->fecha_de_emision?->format('Y-m-d'),
        'due_date' => $invoice->fecha_de_vencimiento?->format('Y-m-d'),
        'currency' => $invoice->currency?->description,
        'total' => $invoice->total,
        'status' => $invoice->status,
        'consolidation_type' => $invoice->consolidation_type,
      ],
      'internal_notes' => $internalNotesData,
      'summary' => [
        'total_internal_notes' => $internalNotesData->count(),
        'total_work_orders' => $internalNotesData->count(),
        'total_amount' => $invoice->total,
      ],
    ];
  }
}
