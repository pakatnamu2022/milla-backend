<?php

use App\Http\Utils\AccessoryCodeGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Carga la lista oficial de accesorios homologados de POST-VENTA (en soles) con
 * precios por "grupo de carrocería":
 *
 *   AUTOS   -> SEDAN, HATCHBACK, SUV
 *   PICKUP  -> PICK UP, CAMIONETA, BARANDA, CHASIS, CHASIS CABINADO, TOLVA, VOLQUETE, UTILITARIO
 *   C3F     -> MULTIPROPOSITO (camionetas de 3 filas)
 *   VAN     -> VAN, MINIBUS, MICROBUS, PANEL
 *
 * Reglas:
 *  - Accesorios de la lista que ya existen  -> se reutiliza el registro (match
 *    por descripción / alias, case & accent insensitive) y se reescriben sus
 *    precios por grupo.
 *  - Accesorios de la lista que no existen  -> se crean (post-venta, soles).
 *  - Post-venta que NO está en la lista     -> status = 0 (queda registrado
 *    para no romper cotizaciones viejas, pero deja de ofrecerse).
 *  - Comercial                              -> se habilita para las carrocerías
 *    que hoy usan los modelos (sin SIN CARROCERIA), al precio que ya tuvieran.
 *
 * NOTA: no se usan IDs hardcodeados; los IDs difieren entre entornos.
 * Irreversible: down() no restaura datos.
 */
return new class extends Migration
{
  private const OPERATION_POSTVENTA = 804;
  private const OPERATION_COMERCIAL = 794;
  private const CURRENCY_PEN = 3;
  private const SIN_CARROCERIA = 559;

  /** Grupo de la lista -> carrocerías reales (ap_masters TIPO_CARROCERIA). */
  private array $groups = [
    'AUTOS'  => [558, 542, 557],
    'PICKUP' => [553, 528, 524, 532, 533, 561, 568, 565],
    'C3F'    => [550],
    'VAN'    => [566, 547, 546, 552],
  ];

  /**
   * Lista oficial.
   *  - 'description' = nombre canónico (se guarda / actualiza así).
   *  - 'aliases'     = otras descripciones con las que pudo haberse registrado
   *                    antes (para reutilizar el mismo registro en vez de duplicar).
   *  - 'prices'      = precio por grupo; grupo ausente = no aplica.
   */
  private array $catalog = [
    ['description' => 'LAMINAS DE SEGURIDAD (MIYASATO)',                          'aliases' => ['LAMINAS DE SEGURIDAD MIYASATO', 'LAMINA DE SEGURIDAD (MIYASATO)', 'LAMINAS DE SEGURIDAD'], 'prices' => ['AUTOS' => 390, 'PICKUP' => 390, 'C3F' => 390, 'VAN' => 650]],
    ['description' => 'ALARMA BÁSICA AMERICANA',                                   'aliases' => ['ALARMA BASICA AMERICANA', 'ALARMA BASICA'],                                                   'prices' => ['AUTOS' => 300, 'PICKUP' => 300, 'C3F' => 300, 'VAN' => 300]],
    ['description' => 'ALARMA PRESTIGE APS25',                                     'aliases' => ['ALARMA APS25', 'ALARMA PRESTIGE APS 25', 'ALARMA APS 25'],                                    'prices' => ['AUTOS' => 400, 'PICKUP' => 400, 'C3F' => 400, 'VAN' => 400]],
    ['description' => 'SENSORES DE RETROCESO',                                     'aliases' => ['SENSOR DE RETROCESO', 'SENSORES DE RETROCESO (4 PUNTOS)'],                                     'prices' => ['AUTOS' => 250, 'PICKUP' => 250, 'C3F' => 250, 'VAN' => 250]],
    ['description' => 'CÁMARA DE RETROCESO',                                       'aliases' => ['CAMARA DE RETROCESO'],                                                                        'prices' => ['AUTOS' => 300, 'PICKUP' => 300, 'C3F' => 300, 'VAN' => 300]],
    ['description' => 'KIT DE SEGUROS (INCLUYE REMACHES)',                         'aliases' => ['KIT DE SEGUROS INCLUYE REMACHES', 'KIT DE SEGUROS CON REMACHES', 'KIT DE SEGUROS + REMACHES'],  'prices' => ['AUTOS' => 150, 'PICKUP' => 150, 'C3F' => 150, 'VAN' => 150]],
    ['description' => 'KIT DE SEGUROS RENAULT (INCLUYE REMACHES)',                 'aliases' => ['KIT DE SEGUROS RENAULT'],                                                                     'prices' => ['AUTOS' => 150, 'PICKUP' => 150, 'C3F' => 150, 'VAN' => 150]],
    ['description' => 'CABLE ACERADO',                                            'aliases' => [],                                                                                            'prices' => ['AUTOS' => 100, 'PICKUP' => 100, 'C3F' => 100, 'VAN' => 100]],
    ['description' => 'UNDERCOATING (4 FRASCOS)',                                  'aliases' => [],                                                                                            'prices' => ['AUTOS' => 360]],
    ['description' => 'UNDERCOATING (9 FRASCOS)',                                  'aliases' => [],                                                                                            'prices' => ['PICKUP' => 810, 'C3F' => 810]],
    ['description' => 'UNDERCOATING (12 FRASCOS)',                                 'aliases' => [],                                                                                            'prices' => ['VAN' => 1080]],
    ['description' => 'TAPIZADO PRANA (INCLUYE ALFOMBRA)',                         'aliases' => ['TAPIZADO PRANA', 'TAPIZ PRANA'],                                                              'prices' => ['AUTOS' => 1170, 'PICKUP' => 1300, 'C3F' => 1560, 'VAN' => 2080]],
    ['description' => 'INSTALACIÓN DE CIERRE CENTRALIZADO (PESTILLOS ELECTRICOS)', 'aliases' => ['CIERRE CENTRALIZADO (PESTILLOS ELECTRICOS)'],                                                  'prices' => ['AUTOS' => 500]],
    ['description' => 'INSTALACIÓN DE CIERRE CENTRALIZADO (CON SWITCH)',           'aliases' => ['CIERRE CENTRALIZADO (CON SWITCH)'],                                                            'prices' => ['VAN' => 600]],
    ['description' => 'BARRAS LATERALES ALUMINIO',                                 'aliases' => ['BARRAS LATERALES DE ALUMINIO'],                                                               'prices' => ['AUTOS' => 650, 'PICKUP' => 650, 'C3F' => 650, 'VAN' => 650]],
    ['description' => 'BARRAS TRANSVERSALES ALUMINIO',                             'aliases' => ['BARRAS TRANSVERSALES DE ALUMINIO'],                                                           'prices' => ['AUTOS' => 560, 'PICKUP' => 560, 'C3F' => 560, 'VAN' => 560]],
    ['description' => 'ESTRIBOS PLANCHA DE ALUMINIO',                              'aliases' => ['ESTRIBOS DE PLANCHA DE ALUMINIO'],                                                            'prices' => ['PICKUP' => 1430]],
    ['description' => 'ANTIVUELCO ACERADO',                                        'aliases' => [],                                                                                            'prices' => ['PICKUP' => 1430]],
    ['description' => 'PROTECTOR DE TOLVA',                                        'aliases' => [],                                                                                            'prices' => ['PICKUP' => 1600]],
    ['description' => 'CARPA',                                                     'aliases' => [],                                                                                            'prices' => ['AUTOS' => 234, 'PICKUP' => 260, 'C3F' => 325]],
    ['description' => 'ESPEJO RETROVISOR CON CÁMARA',                              'aliases' => ['ESPEJO RETROVISOR CON CAMARA'],                                                               'prices' => ['AUTOS' => 580, 'PICKUP' => 580, 'C3F' => 580]],
    ['description' => 'TIRO REMOLQUE',                                             'aliases' => ['TIRO DE REMOLQUE'],                                                                           'prices' => ['PICKUP' => 1040]],
    ['description' => 'LONA MARITIMA',                                             'aliases' => ['LONA MARÍTIMA'],                                                                              'prices' => ['PICKUP' => 1600]],
    ['description' => 'PARILLA PEQUEÑA UNIVERSAL (125 CM X 95 CM)',                'aliases' => [],                                                                                            'prices' => ['PICKUP' => 715]],
    ['description' => 'PARILLA MEDIANA UNIVERSAL (140 CM X 100 CM)',               'aliases' => [],                                                                                            'prices' => ['PICKUP' => 754]],
    ['description' => 'PARILLA GRANDE UNIVERSAL (160 CM X 120 CM)',                'aliases' => [],                                                                                            'prices' => ['PICKUP' => 845]],
    ['description' => 'PARILLA PEQUEÑA AERODINAMICA (125 CM X 95 CM)',             'aliases' => [],                                                                                            'prices' => ['PICKUP' => 910]],
    ['description' => 'PARILLA MEDIANA AERODINAMICA (140 CM X 100 CM)',            'aliases' => [],                                                                                            'prices' => ['PICKUP' => 936]],
    ['description' => 'PARILLA GRANDE AERODINAMICA (160 CM X 120 CM)',             'aliases' => [],                                                                                            'prices' => ['PICKUP' => 975]],
    ['description' => 'FORRO DE TIMÓN',                                            'aliases' => ['FORRO DE TIMON', 'FORRO DE TIMON 60', 'FORRO DE TIMÓN 60', 'FORRO DE TIMON 120', 'FORRO DE TIMÓN 120', 'FORRO DE TIMON UNIVERSAL'], 'prices' => ['AUTOS' => 60, 'PICKUP' => 60, 'C3F' => 60, 'VAN' => 60]],
    ['description' => 'JGO DE NEBLINEROS FORCE LED',                               'aliases' => ['JUEGO DE NEBLINEROS FORCE LED', 'JGO NEBLINEROS FORCE LED'],                                   'prices' => ['PICKUP' => 1170]],
    ['description' => 'PORTANEBLINERO GALVANIZADO NEGRO',                          'aliases' => ['PORTA NEBLINERO GALVANIZADO NEGRO'],                                                          'prices' => ['PICKUP' => 650]],
  ];

  public function up(): void
  {
    $touchedIds = [];

    foreach ($this->catalog as $entry) {
      $bodyTypeIds = [];
      foreach (array_keys($entry['prices']) as $group) {
        $bodyTypeIds = array_merge($bodyTypeIds, $this->groups[$group]);
      }

      // Buscar el registro existente por descripción canónica o alias.
      // La colación de MySQL (utf8mb4_*_ci) ya ignora mayúsculas y tildes.
      $candidates = array_merge([$entry['description']], $entry['aliases'] ?? []);
      $id = DB::table('approved_accessories')
        ->where('type_operation_id', self::OPERATION_POSTVENTA)
        ->whereNull('deleted_at')
        ->whereIn('description', $candidates)
        ->orderBy('id')
        ->value('id');

      if ($id === null) {
        $codeSource = trim(preg_replace('/\(.*?\)/', '', $entry['description'])) ?: $entry['description'];
        $id = DB::table('approved_accessories')->insertGetId([
          'code'              => Str::upper(AccessoryCodeGenerator::generate($codeSource, $bodyTypeIds)),
          'description'       => Str::upper($entry['description']),
          'type_operation_id' => self::OPERATION_POSTVENTA,
          'type_currency_id'  => self::CURRENCY_PEN,
          'status'            => 1,
          'created_at'        => now(),
          'updated_at'        => now(),
        ]);
      } else {
        DB::table('approved_accessories')->where('id', $id)->update([
          'description'      => Str::upper($entry['description']),
          'type_currency_id' => self::CURRENCY_PEN,
          'status'           => 1,
          'updated_at'       => now(),
        ]);
      }

      // Reescribir precios por grupo.
      DB::table('approved_accessory_prices')->where('approved_accessory_id', $id)->delete();
      foreach ($entry['prices'] as $group => $price) {
        foreach ($this->groups[$group] as $bodyTypeId) {
          DB::table('approved_accessory_prices')->insert([
            'approved_accessory_id' => $id,
            'body_type_id'          => $bodyTypeId,
            'price'                 => $price,
            'created_at'            => now(),
            'updated_at'            => now(),
          ]);
        }
      }

      $touchedIds[] = $id;
    }

    // Post-venta fuera de la lista oficial -> se oculta (no se borra).
    DB::table('approved_accessories')
      ->where('type_operation_id', self::OPERATION_POSTVENTA)
      ->whereNotIn('id', $touchedIds)
      ->whereNull('deleted_at')
      ->update(['status' => 0, 'updated_at' => now()]);

    // Comercial -> habilitar para las carrocerías en uso (sin SIN CARROCERIA).
    $inUseBodyTypes = DB::table('ap_models_vn')
      ->whereNotNull('body_type_id')
      ->where('body_type_id', '!=', self::SIN_CARROCERIA)
      ->distinct()
      ->pluck('body_type_id')
      ->all();

    DB::table('approved_accessories')
      ->where('type_operation_id', self::OPERATION_COMERCIAL)
      ->whereNull('deleted_at')
      ->orderBy('id')
      ->pluck('id')
      ->each(function ($accId) use ($inUseBodyTypes) {
        $basePrice = DB::table('approved_accessory_prices')
          ->where('approved_accessory_id', $accId)
          ->min('price') ?? 0;
        $existing = DB::table('approved_accessory_prices')
          ->where('approved_accessory_id', $accId)
          ->pluck('body_type_id')
          ->all();

        foreach ($inUseBodyTypes as $bodyTypeId) {
          if (in_array($bodyTypeId, $existing)) {
            continue;
          }
          DB::table('approved_accessory_prices')->insert([
            'approved_accessory_id' => $accId,
            'body_type_id'          => $bodyTypeId,
            'price'                 => $basePrice,
            'created_at'            => now(),
            'updated_at'            => now(),
          ]);
        }
      });
  }

  public function down(): void
  {
    // Irreversible: no se restauran precios ni el status previo de los accesorios.
  }
};
