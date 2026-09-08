<?php

namespace App\Imports\ap\facturacion;

use App\Http\Services\ap\facturacion\ElectronicDocumentService;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\PurchaseRequestQuote;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Importa la plantilla de ventas internas del stock inicial (vehículos ya vendidos y
 * entregados pero cargados sin factura). Columnas esperadas:
 *   vin, asesor, cliente_dni, serie, numero, fecha_factura,
 *   total_factura, margen_monto, margen_pct, sede, descripcion
 *
 * Toda la operación es en USD.
 *  - total_factura: monto real de la ÚLTIMA factura (puede ser parcial si hubo anticipos).
 *    Se registra tal cual en el ElectronicDocument.
 *  - margen_monto / margen_pct: margen de la cotización (mismos números que muestra el
 *    sistema). Se guardan en la solicitud y se usan para reconstruir el precio de venta:
 *      precio_venta_neto = margen_monto / (margen_pct / 100)
 *      base_selling_price = precio_venta_neto * IGV_FACTOR
 *    (exacto, porque el % de margen se define como monto / precio_venta_neto).
 *
 * Modo analizar ($dryRun = true): no persiste nada, devuelve el detalle fila por fila.
 * Los VIN que ya tienen la venta interna / factura / movimiento FACTURADO FINAL se
 * marcan como OMITIDOS (idempotente).
 */
class VehicleHistoricalFinalSaleBulkImport implements ToCollection, WithHeadingRow
{
  /** Mismo factor que PurchaseRequestQuoteService::calculateMargin (netSalePrice * 1.18). */
  private const IGV_FACTOR = 1.18;

  /**
   * Alias de sede -> abreviatura real en Automotores. La operación comercial usa
   * "CHICLAYO" coloquialmente para la sede LEGUIA (AP_LEGUIA).
   */
  private const SEDE_ALIASES = [
    'CHICLAYO' => 'LEGUIA',
  ];

  /** Distrito por defecto (CHICLAYO) para clientes creados sin ubigeo. */
  private const DEFAULT_DISTRICT_ID = 1227;

  /** tax_class_type_id por defecto para clientes creados: NAC. TERCERO CON IGV (18%). */
  private const DEFAULT_TAX_CLASS_TYPE_ID = 4;

  private bool $dryRun;

  private array $results = [
    'created'        => 0,
    'skipped'        => 0,
    'errors'         => [],
    'rows_processed' => 0,
    'dry_run'        => true,
    'rows'           => [],
  ];

  public function __construct(bool $dryRun = true)
  {
    $this->dryRun = $dryRun;
    $this->results['dry_run'] = $dryRun;
  }

  public function collection(Collection $rows): void
  {
    foreach ($rows as $index => $row) {
      $rowNumber = $index + 2; // fila 1 = encabezado
      $vin = strtoupper(trim((string) ($row['vin'] ?? '')));

      if ($vin === '') {
        continue; // fila vacía
      }

      try {
        $this->processRow($row->toArray(), $rowNumber, $vin);
        $this->results['rows_processed']++;
      } catch (Exception $e) {
        $this->results['errors'][] = "Fila {$rowNumber} (VIN: {$vin}): " . $e->getMessage();
        $this->results['rows'][] = $this->rowDetail($rowNumber, $vin, 'error', $e->getMessage());
      }
    }
  }

  private function processRow(array $row, int $rowNumber, string $vin): void
  {
    $vehicle = Vehicles::with('model', 'warehouse')
      ->where('vin', $vin)->whereNull('deleted_at')->first();
    if (!$vehicle) {
      throw new Exception('No se encontró un vehículo con ese VIN');
    }

    $serie = strtoupper(trim((string) ($row['serie'] ?? '')));
    $numero = (int) preg_replace('/\D/', '', (string) ($row['numero'] ?? ''));
    $comprobante = $serie !== '' && $numero > 0 ? "{$serie}-{$numero}" : null;

    // ---- Idempotencia: ¿ya está registrado? ----
    $skipReason = $this->alreadyRegisteredReason($vehicle, $serie, $numero);
    if ($skipReason !== null) {
      $this->results['skipped']++;
      $detail = $this->rowDetail($rowNumber, $vin, 'skipped', $skipReason);
      $detail['comprobante'] = $comprobante;
      $this->results['rows'][] = $detail;
      return;
    }

    // ---- Validación / resolución de la fila ----
    if ($serie === '' || $numero <= 0) {
      throw new Exception('Serie y número son obligatorios');
    }

    $prefix = substr($serie, 0, 1);
    $docTypeId = match ($prefix) {
      'F'     => ElectronicDocument::TYPE_FACTURA,
      'B'     => ElectronicDocument::TYPE_BOLETA,
      default => throw new Exception("La serie {$serie} debe empezar con F (factura) o B (boleta)"),
    };

    // Todo es en USD.
    $moneda = 'USD';
    $currency = ['sunat' => SunatConcepts::CURRENCY_USD, 'type' => TypeCurrency::USD_ID];

    // total_factura: monto real de la última factura (se registra tal cual).
    // Puede ser 0 (la última factura tras anticipos puede quedar en 0), pero no negativo.
    $total = $this->parseAmount($row['total_factura'] ?? null);
    if ($total === null) {
      throw new Exception('El total_factura (monto de la última factura, USD) es obligatorio (usa 0 si la última factura fue por saldo cero)');
    }
    if ($total < 0) {
      throw new Exception('El total_factura no puede ser negativo');
    }

    // Margen de la cotización: monto y porcentaje. Se guardan y se usan para
    // reconstruir el precio de venta.
    $marginAmount = $this->parseAmount($row['margen_monto'] ?? null);
    if ($marginAmount === null) {
      throw new Exception('El margen_monto (USD) es obligatorio');
    }
    $marginPct = $this->parseAmount($row['margen_pct'] ?? null);
    if ($marginPct === null || $marginPct == 0.0) {
      throw new Exception('El margen_pct (porcentaje, ej. 12.5) es obligatorio y distinto de 0');
    }

    // % de margen = margen_monto / precio_venta_neto  =>  precio_venta_neto = margen_monto / (pct/100)
    $netSalePrice = $marginAmount / ($marginPct / 100);
    $salePrice = round($netSalePrice * self::IGV_FACTOR, 2);
    if ($salePrice <= 0) {
      throw new Exception('El precio de venta reconstruido debe ser mayor a 0 (revisa margen_monto y margen_pct)');
    }

    $emissionDate = $this->parseDate($row['fecha_factura'] ?? null);
    if ($emissionDate === null) {
      throw new Exception('La fecha de factura es obligatoria y debe ser una fecha válida (dd/mm/aaaa)');
    }

    $worker = $this->resolveWorker((string) ($row['asesor'] ?? ''));
    $client = $this->resolveClient((string) ($row['cliente_dni'] ?? ''), $row);
    $sedeId = $this->resolveSedeId((string) ($row['sede'] ?? ''), $vehicle);

    $descripcion = trim((string) ($row['descripcion'] ?? ''));
    if ($descripcion === '') {
      $marca = $vehicle->model?->family?->brand?->name ?? '';
      $modelo = $vehicle->model?->version ?? '';
      $descripcion = trim("VENTA VEHICULO {$marca} {$modelo}") ?: 'VENTA VEHICULO';
    }

    $detail = $this->rowDetail($rowNumber, $vin, 'ok', null);
    $detail['asesor'] = $worker->nombre_completo;
    $detail['cliente'] = "{$client->full_name} ({$client->num_doc})"
      . ($client->exists ? '' : ' · cliente nuevo');
    $detail['comprobante'] = $comprobante;
    $detail['fecha'] = $emissionDate;
    $detail['beneficio'] = number_format($marginAmount, 2) . " {$moneda} / "
      . rtrim(rtrim(number_format($marginPct, 2), '0'), '.') . '%';
    $detail['total'] = number_format($total, 2) . " {$moneda} (factura) · venta "
      . number_format($salePrice, 2);
    $detail['quote_action'] = PurchaseRequestQuote::where('ap_vehicle_id', $vehicle->id)->whereNull('deleted_at')->exists()
      ? 'reutilizar solicitud'
      : 'crear solicitud';

    if (!$this->dryRun) {
      DB::transaction(function () use ($vin, $client, $worker, $docTypeId, $serie, $numero, $sedeId, $currency, $total, $salePrice, $marginAmount, $marginPct, $descripcion, $emissionDate) {
        app(ElectronicDocumentService::class)->createHistoricalFinalSaleFromBulkRow([
          'vin'                            => $vin,
          'client_id'                      => $client->id,
          'worker_id'                      => $worker->id,
          'sunat_concept_document_type_id' => $docTypeId,
          'serie'                          => $serie,
          'numero'                         => $numero,
          'area_id'                        => ApMasters::AREA_COMERCIAL,
          'sede_id'                        => $sedeId,
          'sunat_concept_currency_id'      => $currency['sunat'],
          'doc_type_currency_id'           => $currency['type'],
          'type_currency_id'               => $currency['type'],
          'total'                          => $total,
          'sale_price'                     => $salePrice,
          'margin_amount'                  => $marginAmount,
          'margin_pct'                     => $marginPct,
          'descripcion'                    => $descripcion,
          'emission_date'                  => $emissionDate,
        ]);
      });
    }

    $this->results['created']++;
    $this->results['rows'][] = $detail;
  }

  /**
   * Devuelve el motivo por el que la fila debe omitirse, o null si hay que procesarla.
   */
  private function alreadyRegisteredReason(Vehicles $vehicle, string $serie, string $numero): ?string
  {
    if ($serie !== '' && (int) $numero > 0) {
      $exists = ElectronicDocument::whereNull('deleted_at')
        ->where('serie', $serie)->where('numero', (int) $numero)->exists();
      if ($exists) {
        return "Ya existe un comprobante {$serie}-{$numero} en el sistema";
      }
    }

    $quoteIds = PurchaseRequestQuote::where('ap_vehicle_id', $vehicle->id)
      ->whereNull('deleted_at')->pluck('id');

    if ($quoteIds->isNotEmpty()) {
      $hasExternal = ElectronicDocument::whereNull('deleted_at')
        ->whereIn('purchase_request_quote_id', $quoteIds)
        ->where('internal_note', 'REGISTRO_EXTERNO')
        ->where('is_advance_payment', 0)
        ->exists();
      if ($hasExternal) {
        return 'El vehículo ya tiene una venta histórica (REGISTRO EXTERNO) registrada';
      }

      $hasRealInvoice = ElectronicDocument::whereNull('deleted_at')
        ->whereIn('purchase_request_quote_id', $quoteIds)
        ->whereIn('sunat_concept_document_type_id', [ElectronicDocument::TYPE_FACTURA, ElectronicDocument::TYPE_BOLETA])
        ->where('is_advance_payment', 0)
        ->where('aceptada_por_sunat', true)
        ->where('anulado', 0)
        ->exists();
      if ($hasRealInvoice) {
        return 'El vehículo ya tiene una factura/boleta de venta aceptada';
      }
    }

    $hasMovement = VehicleMovement::where('ap_vehicle_id', $vehicle->id)
      ->where('ap_vehicle_status_id', ApVehicleStatus::FACTURADO_FINAL)
      ->exists();
    if ($hasMovement) {
      return 'El vehículo ya tiene un movimiento FACTURADO FINAL';
    }

    return null;
  }

  private function resolveWorker(string $raw): Worker
  {
    $value = trim($raw);
    if ($value === '') {
      throw new Exception('El asesor es obligatorio');
    }

    $dni = preg_replace('/\D/', '', $value);
    if ($dni !== '' && strlen($dni) >= 8) {
      $worker = Worker::withoutGlobalScopes()->where('vat', $dni)->first();
      if ($worker) {
        return $worker;
      }
    }

    $normalized = mb_strtoupper($value);
    $matches = Worker::withoutGlobalScopes()
      ->whereRaw('UPPER(TRIM(nombre_completo)) = ?', [$normalized])
      ->get();

    if ($matches->count() === 1) {
      return $matches->first();
    }
    if ($matches->count() > 1) {
      // Nombres homónimos: prioriza el asesor activo (status_id = 22). Los que ya
      // no están vigentes (se fueron) igual pueden registrarse como histórico si
      // son el único match, pero ante duplicados nos quedamos con el vigente.
      $pool = $matches->where('status_id', 22);
      if ($pool->isEmpty()) {
        $pool = $matches; // ninguno vigente: se registra igual como histórico
      }
      return $pool->sortByDesc('id')->first();
    }

    throw new Exception("No se encontró un asesor con DNI o nombre '{$value}'");
  }

  /**
   * Busca el cliente por documento y, si no existe, lo crea. El tipo de documento se
   * deduce de la longitud / prefijo del número:
   *   - 8 dígitos            -> DNI (persona natural, nacional)
   *   - 9 caracteres         -> Carné de extranjería (persona natural, extranjero)
   *   - 11 dígitos, empieza 10 -> RUC persona natural
   *   - 11 dígitos, empieza 20 -> RUC persona jurídica
   * El nombre/razón social se obtiene de RENIEC/SUNAT (Factiliza). Si la consulta no
   * devuelve datos se usa la columna opcional `cliente_nombre` del Excel.
   */
  private function resolveClient(string $raw, array $row = []): BusinessPartners
  {
    $doc = strtoupper(preg_replace('/\s+/', '', $raw));
    if ($doc === '') {
      throw new Exception('El documento del cliente (cliente_dni) es obligatorio');
    }

    $client = BusinessPartners::where('num_doc', $doc)->first();
    if ($client) {
      return $client;
    }

    $meta = $this->classifyDocument($doc);
    $lookup = $this->lookupPartnerData($doc, $meta['factiliza_type']);

    $fullName = $lookup['full_name'] ?? trim((string) ($row['cliente_nombre'] ?? ''));
    if ($fullName === '') {
      throw new Exception(
        "No se encontró el cliente {$doc} y no se pudo obtener su nombre de RENIEC/SUNAT; "
        . 'agrega la columna cliente_nombre en el Excel o créalo manualmente'
      );
    }

    $attributes = array_filter([
      'first_name'       => $lookup['first_name'] ?? null,
      'paternal_surname' => $lookup['paternal_surname'] ?? null,
      'maternal_surname' => $lookup['maternal_surname'] ?? null,
    ], fn ($v) => $v !== null && $v !== '') + [
      'num_doc'          => $doc,
      'full_name'        => $fullName,
      'nationality'      => $meta['nationality'],
      'direction'        => $lookup['direction'] ?? '-',
      'document_type_id' => $meta['document_type_id'],
      'type_person_id'   => $meta['type_person_id'],
      'district_id'      => $lookup['district_id'] ?? self::DEFAULT_DISTRICT_ID,
      'tax_class_type_id' => self::DEFAULT_TAX_CLASS_TYPE_ID,
      'company_id'       => \App\Http\Utils\Constants::COMPANY_AP,
      'type'             => BusinessPartners::CLIENT,
      'status_ap'        => 1,
    ];

    if ($this->dryRun) {
      return new BusinessPartners($attributes); // no se persiste en modo analizar
    }

    return BusinessPartners::create($attributes);
  }

  /**
   * Deduce tipo de documento y de persona a partir del número.
   *
   * @return array{document_type_id:int,type_person_id:int,nationality:string,factiliza_type:string}
   */
  private function classifyDocument(string $doc): array
  {
    $len = strlen($doc);

    if ($len === 8 && ctype_digit($doc)) {
      return [
        'document_type_id' => \App\Http\Utils\Constants::TYPE_DOCUMENT_DNI_ID,
        'type_person_id'   => \App\Http\Utils\Constants::TYPE_NATURAL_PERSON_ID,
        'nationality'      => 'NACIONAL',
        'factiliza_type'   => 'dni',
      ];
    }

    if ($len === 9) {
      return [
        'document_type_id' => 811, // CARNET DE EXTRANJERÍA (ap_masters)
        'type_person_id'   => \App\Http\Utils\Constants::TYPE_NATURAL_PERSON_ID,
        'nationality'      => 'EXTRANJERO',
        'factiliza_type'   => 'ce',
      ];
    }

    if ($len === 11 && ctype_digit($doc)) {
      $prefix = substr($doc, 0, 2);
      if ($prefix === '10') {
        return [
          'document_type_id' => \App\Http\Utils\Constants::TYPE_DOCUMENT_RUC_ID,
          'type_person_id'   => \App\Http\Utils\Constants::TYPE_NATURAL_PERSON_ID,
          'nationality'      => 'NACIONAL',
          'factiliza_type'   => 'ruc',
        ];
      }
      if ($prefix === '20') {
        return [
          'document_type_id' => \App\Http\Utils\Constants::TYPE_DOCUMENT_RUC_ID,
          'type_person_id'   => \App\Http\Utils\Constants::TYPE_LEGAL_PERSON_ID,
          'nationality'      => 'NACIONAL',
          'factiliza_type'   => 'ruc',
        ];
      }
      throw new Exception("El RUC {$doc} debe empezar con 10 (persona natural) o 20 (persona jurídica)");
    }

    throw new Exception(
      "El documento '{$doc}' no es válido: se espera 8 dígitos (DNI), 9 caracteres (carné de extranjería) o 11 dígitos (RUC)"
    );
  }

  /**
   * Consulta RENIEC/SUNAT vía DocumentValidationService. Devuelve [] si no hay datos.
   *
   * @return array{full_name?:string,first_name?:string,paternal_surname?:string,maternal_surname?:string,direction?:string,district_id?:int}
   */
  private function lookupPartnerData(string $doc, string $type): array
  {
    try {
      $res = app(\App\Http\Services\DocumentValidation\DocumentValidationService::class)
        ->validateDocument($type, $doc);
    } catch (\Throwable $e) {
      return [];
    }

    if (empty($res['success']) || empty($res['data']['valid'])) {
      return [];
    }
    $data = $res['data'];

    if ($type === 'ruc') {
      $name = trim((string) ($data['business_name'] ?? ''));
      if ($name === '') {
        return [];
      }
      $out = ['full_name' => $name];
      $dir = trim((string) ($data['full_address'] ?? $data['address'] ?? ''));
      if ($dir !== '') {
        $out['direction'] = $dir;
      }
      $districtId = $this->districtIdFromUbigeo($data['ubigeo_sunat'] ?? $data['ubigeo'] ?? null);
      if ($districtId) {
        $out['district_id'] = $districtId;
      }
      return $out;
    }

    // dni / ce
    $name = trim((string) ($data['names'] ?? ''));
    if ($name === '') {
      $name = trim(
        ($data['paternal_surname'] ?? '') . ' '
        . ($data['maternal_surname'] ?? '') . ' '
        . ($data['first_name'] ?? '')
      );
    }
    if ($name === '') {
      return [];
    }

    $out = [
      'full_name'        => $name,
      'first_name'       => trim((string) ($data['first_name'] ?? '')) ?: null,
      'paternal_surname' => trim((string) ($data['paternal_surname'] ?? '')) ?: null,
      'maternal_surname' => trim((string) ($data['maternal_surname'] ?? '')) ?: null,
    ];
    $dir = trim((string) ($data['address'] ?? ''));
    if ($dir !== '') {
      $out['direction'] = $dir;
    }
    $districtId = $this->districtIdFromUbigeo($data['ubigeo_sunat'] ?? $data['ubigeo_reniec'] ?? null);
    if ($districtId) {
      $out['district_id'] = $districtId;
    }
    return $out;
  }

  private function districtIdFromUbigeo($ubigeo): ?int
  {
    if (is_array($ubigeo)) {
      $ubigeo = end($ubigeo);
    }
    $ubigeo = preg_replace('/\D/', '', (string) $ubigeo);
    if (strlen($ubigeo) !== 6) {
      return null;
    }
    return \App\Models\gp\gestionsistema\District::where('ubigeo', $ubigeo)->value('id');
  }

  /**
   * Resuelve la sede SIEMPRE dentro de Automotores Pakatnamú (empresa_id = 3) y
   * activa (status_deleted = 1). Nunca cae en sedes de otras empresas del grupo
   * (TP / DP / GP), que comparten abreviaturas como "CHICLAYO".
   */
  private function resolveSedeId(string $raw, Vehicles $vehicle): int
  {
    $value = trim($raw);

    $base = fn () => \App\Models\gp\maestroGeneral\Sede::query()
      ->where('empresa_id', \App\Http\Utils\Constants::COMPANY_AP)
      ->where('status_deleted', 1);

    if ($value === '') {
      $sedeId = $vehicle->warehouse?->sede_id;
      if (!$sedeId) {
        throw new Exception('El vehículo no tiene sede en su almacén; especifica la sede (de Automotores) en el Excel');
      }
      if (!(clone $base())->where('id', (int) $sedeId)->exists()) {
        throw new Exception("La sede {$sedeId} del almacén del vehículo no es una sede activa de Automotores; especifica la sede en el Excel");
      }
      return (int) $sedeId;
    }

    $normalized = mb_strtoupper($value);
    $normalized = self::SEDE_ALIASES[$normalized] ?? $normalized;
    $sede = $base()
      ->where(function ($q) use ($normalized, $value) {
        $q->whereRaw('UPPER(suc_abrev) = ?', [$normalized])
          ->orWhereRaw('UPPER(abreviatura) = ?', [$normalized])
          ->orWhere('id', is_numeric($value) ? (int) $value : 0);
      })
      ->get();

    if ($sede->isEmpty()) {
      throw new Exception("No se encontró una sede activa de Automotores con abreviatura o id '{$value}'");
    }
    if ($sede->count() > 1) {
      $ids = $sede->pluck('abreviatura')->implode(', ');
      throw new Exception("La sede '{$value}' es ambigua en Automotores ({$ids}); usa la abreviatura exacta o el id");
    }

    return (int) $sede->first()->id;
  }

  /**
   * Convierte un valor de celda a número. Acepta "1,234.56", "1234,56", "$ 1.234,56",
   * "12.5%". Devuelve null si la celda está vacía o no es numérica.
   */
  private function parseAmount($value): ?float
  {
    if ($value === null) {
      return null;
    }
    $str = trim((string) $value);
    if ($str === '') {
      return null;
    }
    $str = str_replace(['$', '%', ' ', "\u{00A0}"], '', $str);

    // Si tiene coma y punto, el último separador es el decimal.
    if (str_contains($str, ',') && str_contains($str, '.')) {
      $str = strrpos($str, ',') > strrpos($str, '.')
        ? str_replace('.', '', str_replace(',', '.', $str))
        : str_replace(',', '', $str);
    } elseif (str_contains($str, ',')) {
      // Solo coma: decimal si hay 1-2 dígitos después, si no es separador de miles.
      $str = preg_match('/,\d{1,2}$/', $str)
        ? str_replace(',', '.', $str)
        : str_replace(',', '', $str);
    }

    return is_numeric($str) ? (float) $str : null;
  }

  private function parseDate($value): ?string
  {
    if ($value === null || $value === '') {
      return null;
    }

    try {
      if (is_numeric($value)) {
        return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
      }
      $str = trim((string) $value);
      // dd/mm/aaaa o dd-mm-aaaa
      if (preg_match('#^(\d{1,2})[/\-](\d{1,2})[/\-](\d{2,4})$#', $str, $m)) {
        $year = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
        return Carbon::createFromDate((int) $year, (int) $m[2], (int) $m[1])->format('Y-m-d');
      }
      return Carbon::parse($str)->format('Y-m-d');
    } catch (Exception $e) {
      return null;
    }
  }

  private function rowDetail(int $row, string $vin, string $status, ?string $message): array
  {
    return [
      'row'          => $row,
      'vin'          => $vin,
      'status'       => $status,
      'message'      => $message,
      'asesor'       => null,
      'cliente'      => null,
      'comprobante'  => null,
      'fecha'        => null,
      'beneficio'    => null,
      'total'        => null,
      'quote_action' => null,
    ];
  }

  public function getResults(): array
  {
    return $this->results;
  }
}
