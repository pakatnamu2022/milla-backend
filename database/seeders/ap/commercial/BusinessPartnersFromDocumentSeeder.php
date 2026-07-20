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
      "dni"   => "07087005",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "47950345",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "03678910",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "74037777",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "74047976",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "44929921",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "80539915",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "46049408",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "17632017",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "77027417",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "44214322",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "45214796",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "74374644",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "72483421",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "70046881",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "09944265",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "76449504",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "33592480",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "44239534",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "16415453",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "43481724",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "16691878",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "70450250",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "16640286",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "45625638",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "72090006",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "76127448",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"    => "26241969",
      "largo"  => "8",
      "tipo"   => "DNI ",
      "nombre" => "VILLEGAS ORTIZ TATIANA PATRICIA",
    ],
    [
      "dni"   => "26731155",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "72146905",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "26688241",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "71227050",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "43686476",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "10613317",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "74925058",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "42345373",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "27434160",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "70039774",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "40981529",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "43130520",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "77101519",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "44306184",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "41835190",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "71266878",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "41442136",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "47221113",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "75685321",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "43157603",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "43154949",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "42801453",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "40769323",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "46867879",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "02665397",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "03637139",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "42380922",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "46979537",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "43001912",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "45555555",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "42256324",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "45457528",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "72700680",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "03377751",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "02853657",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "46677685",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "03325660",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "70678110",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "44770275",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "42387876",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "76455480",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "71232224",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "29594778",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "45393418",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "16753004",
      "largo" => "8",
      "tipo"  => "DNI "
    ],
    [
      "dni"   => "002540986",
      "largo" => "9",
      "tipo"  => "CEX "
    ],
    [
      "dni"    => "0915451470",
      "largo"  => "10",
      "tipo"   => "PAS ",
      "nombre" => "CRUZ AQUINO FERNANDO",
    ],
    [
      "dni"   => "20607565938",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20549379622",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20604041164",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20610852743",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20609762331",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20606978945",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20100210909",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20563405563",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20100041953",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20418896915",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20553157014",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20509959766",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20603952899",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20608882970",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20504292968",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20546218504",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20100115663",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20495811736",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20495625606",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20600718674",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20537284723",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20604718041",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20482084444",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "10418849865",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20529823321",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20530062563",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20343877294",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20610719865",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20601001994",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20607544663",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20611409665",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20525998577",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20610677003",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20601647649",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20492783651",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20523395701",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20603393083",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20602209475",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "10427037032",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20480270160",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20450158586",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "10752180038",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20101036813",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20570875796",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20570772636",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20491734745",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20100278708",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "15410911776",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20100202124",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20538642445",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20602994946",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20513943998",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "10409114437",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "10733772838",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "10405086854",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20613547437",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20614418827",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "10746130916",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20529965380",
      "largo" => "11",
      "tipo"  => "RUC "
    ],
    [
      "dni"   => "20609971798",
      "largo" => "11",
      "tipo"  => "RUC "
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
  ];

  // Tipo de consulta al servicio de validación (debe coincidir con Factiliza)
  protected array $serviceTypeMap = [
    'DNI' => 'dni',
    'RUC' => 'ruc',
    'CEX' => 'ce',
    'PAS' => null,  // Factiliza no tiene endpoint para pasaportes; se usa nombre manual
  ];

  public function run(): void
  {
    // ── Datos base requeridos ────────────────────────────────────────────
    $company = Company::first();
    $district = District::first();
    $taxClass = TaxClassTypes::where('id', 4)->first() ?? TaxClassTypes::first();
    $origin = ApMasters::where('type', 'ORIGEN_CLIENTE')->first();
    $typePerson = ApMasters::where('type', 'TIPO_PERSONA')->first();
    $personSegment = ApMasters::where('type', 'SEGMENTO_PERSONA')->first();
    $activityEconomic = ApMasters::where('type', 'ACTIVIDAD_ECONOMICA')->first();

    if (!$company || !$district || !$taxClass) {
      $this->command->error('Faltan datos base (Company, District, TaxClassTypes). Abortando.');
      return;
    }

    if (!$origin || !$typePerson || !$personSegment || !$activityEconomic) {
      $this->command->error('Faltan registros en ap_masters (ORIGIN, TYPE_PERSON, PERSON_SEGMENT, ACTIVITY_ECONOMIC). Abortando.');
      return;
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

        if ($this->syncToDynamics) {
          try {
            $existsInDynamics = DB::connection('dbtp')
              ->table('neInTbCliente')
              ->where('EmpresaId', 'GPAUP')
              ->where('NumeroDocumento', $numDoc)
              ->exists();

            if ($existsInDynamics) {
              $this->command->warn("   [SYNC OMITIDO] Ya existe en neInTbCliente (GPAUP).");
              $report['sync_skipped'][] = "{$docCode} {$numDoc} → ya existe en neInTbCliente (GPAUP)";
            } else {
              $syncService->sync('business_partners', $partner->toArray(), 'create');
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
        $origin->id,
        $typePerson->id,
        $personSegment->id,
        $activityEconomic->id,
        $nombreFijo,
      );

      // ── Determinar type_person_id según tipo de documento ────────────
      if ($docCode === 'RUC') {
        $juridica = ApMasters::find(ApMasters::TYPE_PERSON_JURIDICA_ID)
          ?? ApMasters::where('type', 'TYPE_PERSON')->where('code', 'LIKE', '%JUR%')->first()
          ?? $typePerson;
        $payload['type_person_id'] = $juridica->id;
      }

      // ── Validar campos requeridos para Dynamics ──────────────────────
      $dynamicsErrors = $this->validateForDynamics($payload);
      if (!empty($dynamicsErrors)) {
        $errList = implode('; ', $dynamicsErrors);
        $this->command->error("   [DYNAMICS INVÁLIDO] No se creará: {$errList}");
        $report['failed'][] = "{$docCode} {$numDoc} → Dynamics: {$errList}";
        continue;
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
            ->table('neInTbCliente')
            ->where('EmpresaId', 'GPAUP')
            ->where('NumeroDocumento', $numDoc)
            ->exists();

          if ($existsInDynamics) {
            $this->command->warn("   [SYNC OMITIDO] Ya existe en neInTbCliente (GPAUP).");
            $report['sync_skipped'][] = "{$docCode} {$numDoc} → ya existe en neInTbCliente (GPAUP)";
          } else {
            $syncService->sync('business_partners', $partner->toArray(), 'create');
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
        $this->command->warn('  OMITIDOS EN DYNAMICS — ya existen en neInTbCliente (' . count($report['sync_skipped']) . '):');
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
    int     $originId,
    int     $typePersonId,
    int     $personSegmentId,
    int     $activityEconomicId,
    ?string $nombreFijo = null,
  ): array
  {
    $base = [
      'num_doc'              => $numDoc,
      'document_type_id'     => $documentTypeId,
      'type'                 => BusinessPartners::CLIENT,
      'tax_class_type_id'    => $taxClassTypeId,
      'company_id'           => $companyId,
      'district_id'          => $districtId,
      'origin_id'            => $originId,
      'type_person_id'       => $typePersonId,
      'person_segment_id'    => $personSegmentId,
      'activity_economic_id' => $activityEconomicId,
      'nationality'          => 'NACIONAL',
      'status_ap'            => 1,
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
