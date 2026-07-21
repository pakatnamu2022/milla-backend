<?php

namespace Database\Seeders\ap\commercial;

use App\Http\Services\DatabaseSyncService;
use App\Http\Services\DocumentValidation\DocumentValidationService;
use App\Http\Utils\Constants;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\maestroGeneral\TaxClassTypes;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\gestionsistema\District;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessPartnersFromDocumentSeeder extends Seeder
{
  /**
   * Documentos a procesar.
   * dni  = número de documento
   * largo = dígitos esperados (referencial)
   * tipo  = DNI | CEX | PAS | RUC
   */
  protected array $documents = [
    [
      "dni"   => "75090837",
      "largo" => "8",
      "tipo"  => "DNI"
    ],
    [
      "dni"   => "42772411",
      "largo" => "8",
      "tipo"  => "DNI"
    ],
    [
      "dni"   => "42082941",
      "largo" => "8",
      "tipo"  => "DNI"
    ],
    [
      "dni"    => "IE9692928F",
      "largo"  => "10",
      "tipo"   => "OTR",
      "nombre" => "META PLATFORMS IRELAND LIMITE"
    ],
    [
      "dni"   => "20607826308",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20101973922",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20467534026",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20603386541",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20100017491",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "10414091038",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20479461156",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "10276763984",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20604155879",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20495905552",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20454073143",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20602819834",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20606438975",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20601066018",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20348687191",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "10467115273",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "10277163778",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20613790692",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20603381697",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20606432446",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20144961146",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20505205791",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20610736778",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20519151279",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20164032613",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20379430377",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20610098259",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20545996252",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20543725821",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20565643496",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20557296973",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20607490229",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "10167793431",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20561127311",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20479819051",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20492353214",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "10415914321",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20511004251",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20480738676",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20615421791",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "10192497936",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20491647770",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20557644416",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20609090791",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20613058878",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20522547957",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "10459074525",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20526233329",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "10772384161",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20105354330",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20548704261",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20608652630",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20127765279",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20600509935",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20607596108",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20103117560",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20344877158",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20545621879",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20124148970",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20341841357",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20504292968",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20601415586",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20604093091",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20600057058",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20517342891",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20342868844",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20515339508",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20516711559",
      "largo" => "11",
      "tipo"  => "RUC"
    ],
    [
      "dni"   => "20604457948",
      "largo" => "11",
      "tipo"  => "RUC"
    ]
  ];

  /**
   * Enviar a Dynamics después de insertar.
   * true  → llama al DatabaseSyncService para la entidad 'business_partners'
   * false → solo inserta en base de datos local
   */
  protected bool $syncToDynamics = true;

  /**
   * Solo sincronizar registros ya existentes en business_partners (sin crear nuevos).
   * true  → omite la creación, busca los registros por num_doc y los envía a Dynamics
   * false → flujo normal (crear + sincronizar)
   */
  protected bool $syncOnly = true;

  // -------------------------------------------------------------------------
  // Mapa de tipo de documento (val2) → ap_masters.id
  // Ajusta los IDs si difieren en tu entorno.
  // -------------------------------------------------------------------------
  protected array $documentTypeMap = [
    'DNI' => Constants::TYPE_DOCUMENT_DNI_ID,  // 809
    'RUC' => Constants::TYPE_DOCUMENT_RUC_ID,  // 810
    'CEX' => 811,  // Carnet de Extranjería
    'PAS' => 973,  // Pasaporte
    'OTR' => 974,  // Otros tipos o sin RUC
  ];

  // Tipo de consulta al servicio de validación (debe coincidir con Factiliza)
  protected array $serviceTypeMap = [
    'DNI' => 'dni',
    'RUC' => 'ruc',
    'CEX' => 'ce',
    'PAS' => null,  // Factiliza no tiene endpoint para pasaportes; se usa nombre manual
    'OTR' => null,  // Sin endpoint de validación
  ];

  public function run(): void
  {
    // ── Datos base requeridos ────────────────────────────────────────────
    $company = Company::first();
    $district = District::first();
    $taxClass = TaxClassTypes::where('id', 4)->first() ?? TaxClassTypes::first();
    $supplierTaxClass = TaxClassTypes::where('dyn_code', 'N.T.SR.IGV')->where('type', 'PROVEEDOR')->first()
      ?? TaxClassTypes::find(12);
    $origin = ApMasters::where('type', 'ORIGEN_PROVEEDOR')->first();
    $typePerson = ApMasters::where('type', 'TIPO_PERSONA')->first();
    $personSegment = ApMasters::where('type', 'SEGMENTO_PERSONA')->first();
    $activityEconomic = ApMasters::where('type', 'ACTIVIDAD_ECONOMICA')->first();

    if (!$company || !$district || !$taxClass) {
      $this->command->error('Faltan datos base (Company, District, TaxClassTypes). Abortando.');
      return;
    }

    if (!$supplierTaxClass) {
      $this->command->error('No se encontró TaxClassTypes N.T.SR.IGV (tipo PROVEEDOR). Abortando.');
      return;
    }

    if (!$this->syncOnly && (!$typePerson || !$personSegment || !$activityEconomic)) {
      $this->command->error('Faltan registros en ap_masters (TYPE_PERSON, PERSON_SEGMENT, ACTIVITY_ECONOMIC). Abortando.');
      return;
    }

    if (!$origin) {
      $this->command->warn('ORIGEN_PROVEEDOR no encontrado en ap_masters, se usará null.');
    }

    $validationService = new DocumentValidationService();
    $syncService = new DatabaseSyncService();

    // ── Contadores para el reporte ───────────────────────────────────────
    $report = [
      'created'      => [],
      'skipped'      => [],
      'failed'       => [],
      'no_api'       => [],
      'synced'       => [],
      'sync_skipped' => [],
      'sync_err'     => [],
    ];

    foreach ($this->documents as $doc) {
      $numDoc = trim($doc['dni']);
      $docCode = strtoupper(trim($doc['tipo']));
      $nombreFijo = isset($doc['nombre']) ? strtoupper(trim($doc['nombre'])) : null;

      $this->command->line("── Procesando {$docCode}: {$numDoc}");

      // ── Modo syncOnly: solo sincronizar registros ya existentes ──────
      if ($this->syncOnly) {
        $partner = BusinessPartners::where('num_doc', $numDoc)->whereNull('deleted_at')->first();
        if (!$partner) {
          $this->command->warn("   [SYNC-ONLY] No encontrado en business_partners. Saltando.");
          $report['skipped'][] = "{$docCode} {$numDoc} → no existe localmente";
          continue;
        }
        $this->command->info("   [SYNC-ONLY] Encontrado id={$partner->id} → {$partner->full_name}");

        // Asegurar supplier_tax_class_id en el registro local
        if (!$partner->supplier_tax_class_id) {
          $partner->update(['supplier_tax_class_id' => $supplierTaxClass->id]);
          $this->command->line("   [ACTUALIZADO] supplier_tax_class_id={$supplierTaxClass->id} ({$supplierTaxClass->dyn_code})");
        }

        if ($this->syncToDynamics) {
          try {
            $dynRecord = DB::connection('dbtp')
              ->table('neInTbProveedor')
              ->where('EmpresaId', 'GPAUP')
              ->where('NumeroDocumento', $numDoc)
              ->first();

            if ($dynRecord) {
              $tieneError = !empty($dynRecord->ProcesoError);
              if ($tieneError && ($dynRecord->ClaseId ?? '') !== $supplierTaxClass->dyn_code) {
                DB::connection('dbtp')
                  ->table('neInTbProveedor')
                  ->where('EmpresaId', 'GPAUP')
                  ->where('NumeroDocumento', $numDoc)
                  ->update(['ClaseId' => $supplierTaxClass->dyn_code]);
                $this->command->line("   [DYN ACTUALIZADO] ClaseId={$supplierTaxClass->dyn_code} (ProcesoError={$dynRecord->ProcesoError})");
                $report['synced'][] = "{$docCode} {$numDoc} → ClaseId actualizado en neInTbProveedor";
              } else {
                $motivo = !$tieneError ? 'sin ProcesoError' : 'ClaseId ya correcto';
                $this->command->warn("   [SYNC OMITIDO] Ya existe en neInTbProveedor ({$motivo}).");
                $report['sync_skipped'][] = "{$docCode} {$numDoc} → {$motivo}";
              }
            } else {
              $syncService->sync('business_partners_ap_supplier', $partner->fresh()->toArray(), 'create');
              $this->command->line("   [SYNC]   Enviado a Dynamics.");
              $report['synced'][] = "{$docCode} {$numDoc} → id={$partner->id}";
            }
          } catch (\Throwable $e) {
            $this->command->warn("   [SYNC ERROR] " . $e->getMessage());
            $report['sync_err'][] = "{$docCode} {$numDoc} → " . $e->getMessage();
          }
        }
        continue;
      }

      // ── Verificar si ya existe ───────────────────────────────────────
      if (BusinessPartners::where('num_doc', $numDoc)->whereNull('deleted_at')->exists()) {
        $this->command->warn("   [OMITIDO] Ya existe en business_partners.");
        $report['skipped'][] = "{$docCode} {$numDoc} → ya existe";
        continue;
      }

      // ── Resolver document_type_id ────────────────────────────────────
      $documentTypeId = $this->documentTypeMap[$docCode] ?? null;
      if (!$documentTypeId) {
        $this->command->error("   [ERROR] Tipo de documento desconocido: {$docCode}");
        $report['failed'][] = "{$docCode} {$numDoc} → tipo desconocido";
        continue;
      }

      // Buscar el ApMasters real por id (puede existir o no con ese id exacto)
      $documentTypeMaster = ApMasters::find($documentTypeId);
      if (!$documentTypeMaster) {
        // Fallback: buscar por code
        $documentTypeMaster = ApMasters::where('type', 'TYPE_DOCUMENT')
          ->where('code', $docCode)
          ->first();
      }

      if (!$documentTypeMaster) {
        $this->command->error("   [ERROR] No se encontró ApMasters para tipo {$docCode} (id={$documentTypeId}).");
        $report['failed'][] = "{$docCode} {$numDoc} → sin ap_masters";
        continue;
      }

      // ── Consultar API de validación ──────────────────────────────────
      $serviceType = $this->serviceTypeMap[$docCode] ?? null;
      $apiOk = false;
      $apiData = [];

      if ($serviceType !== null) {
        $apiResponse = $validationService->validateDocument($serviceType, $numDoc, [], false);
        $apiData = $apiResponse['data'] ?? [];
        $apiOk = ($apiResponse['success'] ?? false) && !empty($apiData);

        if (!$apiOk) {
          $apiError = $apiResponse['error'] ?? 'sin respuesta';
          if (!$nombreFijo) {
            $this->command->warn("   [API SIN DATOS] {$apiError}. Revisa manualmente y agrega 'nombre' al array si es necesario.");
            $report['no_api'][] = "{$docCode} {$numDoc} → {$apiError}";
          } else {
            $this->command->line("   [API SIN DATOS] {$apiError}. Se usará nombre fijo: {$nombreFijo}.");
          }
        }
      } else {
        $this->command->line("   [API OMITIDA] No hay endpoint para {$docCode}; se usará nombre manual.");
      }

      // ── Construir payload ────────────────────────────────────────────
      $payload = $this->buildPayload(
        $docCode,
        $numDoc,
        $documentTypeMaster->id,
        $apiOk ? $apiData : [],
        $company->id,
        $district->id,
        $taxClass->id,
        $origin?->id,
        $typePerson->id,
        $personSegment->id,
        $activityEconomic->id,
        $supplierTaxClass->id,
        $nombreFijo,
      );

      // ── Determinar type_person_id según tipo de documento ────────────
      if ($docCode === 'RUC') {
        $juridica = ApMasters::find(ApMasters::TYPE_PERSON_JURIDICA_ID)
          ?? ApMasters::where('type', 'TYPE_PERSON')->where('code', 'LIKE', '%JUR%')->first()
          ?? $typePerson;
        $payload['type_person_id'] = $juridica->id;
      }

      // ── Validar campos requeridos para Dynamics (solo si se va a sincronizar) ──
      if ($this->syncToDynamics) {
        $dynamicsErrors = $this->validateForDynamics($payload);
        if (!empty($dynamicsErrors)) {
          $errList = implode('; ', $dynamicsErrors);
          $this->command->error("   [DYNAMICS INVÁLIDO] No se creará: {$errList}");
          $report['failed'][] = "{$docCode} {$numDoc} → Dynamics: {$errList}";
          continue;
        }
      }

      // ── Insertar ─────────────────────────────────────────────────────
      try {
        $partner = BusinessPartners::create($payload);
        $this->command->info("   [CREADO] id={$partner->id} → {$partner->full_name}");
        $apiNote = $apiOk ? 'API OK' : 'sin datos API (se usaron defaults)';
        $report['created'][] = "{$docCode} {$numDoc} → id={$partner->id} | {$partner->full_name} | {$apiNote}";
      } catch (\Throwable $e) {
        $this->command->error("   [ERROR] Al crear: " . $e->getMessage());
        $report['failed'][] = "{$docCode} {$numDoc} → " . $e->getMessage();
        continue;
      }

      // ── Sincronizar a Dynamics ────────────────────────────────────────
      if ($this->syncToDynamics) {
        try {
          $existsInDynamics = DB::connection('dbtp')
            ->table('neInTbProveedor')
            ->where('EmpresaId', 'GPAUP')
            ->where('NumeroDocumento', $numDoc)
            ->exists();

          if ($existsInDynamics) {
            $this->command->warn("   [SYNC OMITIDO] Ya existe en neInTbProveedor (GPAUP).");
            $report['sync_skipped'][] = "{$docCode} {$numDoc} → ya existe en neInTbProveedor (GPAUP)";
          } else {
            $syncService->sync('business_partners_ap_supplier', $partner->toArray(), 'create');
            $this->command->line("   [SYNC]   Enviado a Dynamics.");
            $report['synced'][] = "{$docCode} {$numDoc} → id={$partner->id}";
          }
        } catch (\Throwable $e) {
          $this->command->warn("   [SYNC ERROR] " . $e->getMessage());
          $report['sync_err'][] = "{$docCode} {$numDoc} → " . $e->getMessage();
        }
      }
    }

    // ── Reporte final ────────────────────────────────────────────────────
    $this->command->newLine();
    $this->command->line('═══════════════════════════════════════════════════════');
    $this->command->info('  REPORTE DE PROCESAMIENTO');
    $this->command->line('═══════════════════════════════════════════════════════');

    $this->command->info('  CREADOS (' . count($report['created']) . '):');
    foreach ($report['created'] as $line) {
      $this->command->line("    ✓ {$line}");
    }

    $this->command->warn('  OMITIDOS (' . count($report['skipped']) . '):');
    foreach ($report['skipped'] as $line) {
      $this->command->line("    ~ {$line}");
    }

    $this->command->error('  FALLIDOS (' . count($report['failed']) . '):');
    foreach ($report['failed'] as $line) {
      $this->command->line("    ✗ {$line}");
    }

    if (!empty($report['no_api'])) {
      $this->command->warn('  SIN DATOS DE API — REVISAR MANUALMENTE (' . count($report['no_api']) . '):');
      foreach ($report['no_api'] as $line) {
        $this->command->line("    ? {$line}");
      }
      $this->command->warn("  → Agrega 'nombre' al array del documento correspondiente y vuelve a ejecutar.");
    }

    if ($this->syncToDynamics) {
      $this->command->info('  SINCRONIZADOS A DYNAMICS (' . count($report['synced']) . '):');
      foreach ($report['synced'] as $line) {
        $this->command->line("    ↑ {$line}");
      }
      if (!empty($report['sync_skipped'])) {
        $this->command->warn('  OMITIDOS EN DYNAMICS — ya existen en neInTbProveedor (' . count($report['sync_skipped']) . '):');
        foreach ($report['sync_skipped'] as $line) {
          $this->command->line("    ~ {$line}");
        }
      }
      if (!empty($report['sync_err'])) {
        $this->command->warn('  ERRORES DE SYNC (' . count($report['sync_err']) . '):');
        foreach ($report['sync_err'] as $line) {
          $this->command->line("    ! {$line}");
        }
      }
    }

    $this->command->line('═══════════════════════════════════════════════════════');
    $total = count($this->documents);
    $this->command->info("  Total procesados: {$total} | Creados: " . count($report['created']) . " | Omitidos: " . count($report['skipped']) . " | Fallidos: " . count($report['failed']));
    $this->command->line('═══════════════════════════════════════════════════════');
  }

  // -------------------------------------------------------------------------

  protected function buildPayload(
    string  $docCode,
    string  $numDoc,
    int     $documentTypeId,
    array   $apiData,
    int     $companyId,
    int     $districtId,
    int     $taxClassTypeId,
    ?int    $originId,
    int     $typePersonId,
    int     $personSegmentId,
    int     $activityEconomicId,
    int     $supplierTaxClassId,
    ?string $nombreFijo = null,
  ): array
  {
    $base = [
      'num_doc'                => $numDoc,
      'document_type_id'       => $documentTypeId,
      'type'                   => BusinessPartners::SUPPLIER,
      'tax_class_type_id'      => $taxClassTypeId,
      'supplier_tax_class_id'  => $supplierTaxClassId,
      'company_id'             => $companyId,
      'district_id'            => $districtId,
      'origin_id'              => $originId,
      'type_person_id'         => $typePersonId,
      'person_segment_id'      => $personSegmentId,
      'activity_economic_id'   => $activityEconomicId,
      'nationality'            => 'NACIONAL',
      'status_ap'              => 1,
    ];

    $payload = match ($docCode) {
      'DNI' => array_merge($base, $this->mapDniData($apiData)),
      'RUC' => array_merge($base, $this->mapRucData($apiData, $numDoc)),
      'CEX', 'PAS' => array_merge($base, $this->mapCeData($apiData, $numDoc)),
      default => array_merge($base, ['full_name' => $numDoc]),
    };

    // Si se proporcionó un nombre fijo (override manual), sobreescribir full_name
    if ($nombreFijo !== null) {
      $payload['full_name'] = $nombreFijo;
    }

    return $payload;
  }

  protected function mapDniData(array $d): array
  {
    $firstName = $d['first_name'] ?? '';
    $paternalSurname = $d['paternal_surname'] ?? '';
    $maternalSurname = $d['maternal_surname'] ?? '';
    $fullName = $d['names'] ?? trim("{$firstName} {$paternalSurname} {$maternalSurname}");
    $birthDate = isset($d['birth_date']) && $d['birth_date'] !== '' ? $d['birth_date'] : null;

    return [
      'first_name'       => $firstName ?: null,
      'paternal_surname' => $paternalSurname ?: null,
      'maternal_surname' => $maternalSurname ?: null,
      'full_name'        => $fullName ?: 'SIN NOMBRE',
      'birth_date'       => $birthDate,
      'direction'        => ($d['address'] ?? '') ?: null,
    ];
  }

  protected function mapRucData(array $d, string $numDoc): array
  {
    $fullName = ($d['business_name'] ?? '') ?: null;
    $status = ($d['status'] ?? '') ?: null;
    $condition = ($d['condition'] ?? '') ?: null;
    $direction = ($d['full_address'] ?? $d['address'] ?? '') ?: null;

    return [
      'full_name'         => $fullName ?: "EMPRESA {$numDoc}",
      'company_status'    => $status,
      'company_condition' => $condition,
      'direction'         => $direction,
    ];
  }

  protected function mapCeData(array $d, string $numDoc): array
  {
    $firstName = ($d['first_name'] ?? '') ?: null;
    $paternalSurname = ($d['paternal_surname'] ?? '') ?: null;
    $maternalSurname = ($d['maternal_surname'] ?? '') ?: null;
    $fullName = ($d['names'] ?? '') ?: trim("{$paternalSurname} {$maternalSurname} {$firstName}");

    return [
      'first_name'       => $firstName,
      'paternal_surname' => $paternalSurname,
      'maternal_surname' => $maternalSurname,
      'full_name'        => $fullName ?: "EXTRANJERO {$numDoc}",
      'nationality'      => 'EXTRANJERO',
      'direction'        => null,
    ];
  }

  // ── Validación para Dynamics ─────────────────────────────────────────────
  protected function validateForDynamics(array $payload): array
  {
    $errors = [];

    // num_doc → Cliente / NumeroDocumento
    if (empty($payload['num_doc'])) {
      $errors[] = 'num_doc vacío';
    }

    // full_name → Nombre / NombreCorto / RazonSocial
    $fullName = $payload['full_name'] ?? '';
    if (empty($fullName) || $fullName === 'SIN NOMBRE') {
      $errors[] = 'full_name vacío o "SIN NOMBRE"';
    }
    $numDoc = $payload['num_doc'] ?? '';
    if ($fullName === "EXTRANJERO {$numDoc}" || $fullName === "EMPRESA {$numDoc}") {
      $errors[] = "full_name es placeholder ({$fullName}); agrega 'nombre' al array del documento";
    }

    // document_type_id → TipoDocumento (necesita ApMasters.code no vacío)
    if (empty($payload['document_type_id'])) {
      $errors[] = 'document_type_id vacío';
    } else {
      $docTypeMaster = ApMasters::find($payload['document_type_id']);
      if (!$docTypeMaster || empty($docTypeMaster->code)) {
        $errors[] = "document_type_id={$payload['document_type_id']} sin code en ap_masters";
      }
    }

    // tax_class_type_id → ClaseCliente (necesita TaxClassTypes.dyn_code no vacío)
    if (empty($payload['tax_class_type_id'])) {
      $errors[] = 'tax_class_type_id vacío';
    } else {
      $taxClass = TaxClassTypes::find($payload['tax_class_type_id']);
      if (!$taxClass || empty($taxClass->dyn_code)) {
        $errors[] = "tax_class_type_id={$payload['tax_class_type_id']} sin dyn_code en tax_class_types";
      }
    }

    // type_person_id → Contribuyente (necesita ApMasters.code no vacío)
    if (empty($payload['type_person_id'])) {
      $errors[] = 'type_person_id vacío';
    } else {
      $typePerson = ApMasters::find($payload['type_person_id']);
      if (!$typePerson || empty($typePerson->code)) {
        $errors[] = "type_person_id={$payload['type_person_id']} sin code en ap_masters";
      }
    }

    return $errors;
  }
}
