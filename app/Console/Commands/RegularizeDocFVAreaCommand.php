<?php

namespace App\Console\Commands;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RegularizeDocFVAreaCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'docfv:regularize-area {--preview : Solo mostrar previsualización sin actualizar} {--force : Actualizar sin pedir confirmación}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Regulariza los campos Modulo y Area en RM20101_DOCFV según el area_id de los documentos electrónicos';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $this->info('Iniciando regularización de RM20101_DOCFV...');
    $this->newLine();

    // Mapeo de area_id a valor de Area
    $areaMapping = [
      ApMasters::AREA_COMERCIAL => 'COMERCIAL',
      ApMasters::AREA_POSVENTA => 'CAJA',
      ApMasters::AREA_TALLER => 'TALLER',
      ApMasters::AREA_MESON => 'MESON',
    ];

    // Obtener todos los registros de RM20101_DOCFV
    $docFVRecords = DB::connection(Company::CONNECTION_DYNAMICS_3)
      ->table('RM20101_DOCFV')
      ->get();

    if ($docFVRecords->isEmpty()) {
      $this->warn('No se encontraron registros en RM20101_DOCFV');
      return 0;
    }

    $this->info("Total de registros en RM20101_DOCFV: {$docFVRecords->count()}");
    $this->newLine();

    $matched = [];
    $notMatched = [];
    $noChanges = [];

    // Procesar cada registro
    $this->withProgressBar($docFVRecords, function ($docFV) use (&$matched, &$notMatched, &$noChanges, $areaMapping) {
      // Buscar el documento electrónico por full_number
      $electronicDocument = ElectronicDocument::where('full_number', $docFV->DocumentoId)->first();

      if (!$electronicDocument) {
        $notMatched[] = [
          'DocumentoId' => $docFV->DocumentoId,
          'CurrentModulo' => $docFV->Modulo,
          'CurrentArea' => $docFV->Area,
        ];
        return;
      }

      // Calcular nuevos valores
      $newModulo = in_array($electronicDocument->area_id, [ApMasters::AREA_POSVENTA, ApMasters::AREA_TALLER, ApMasters::AREA_MESON])
        ? 'POSTVENTA'
        : 'COMERCIAL';

      $newArea = $areaMapping[$electronicDocument->area_id] ?? 'POSTVENTA';

      // Verificar si hay cambios
      if ($docFV->Modulo === $newModulo && $docFV->Area === $newArea) {
        $noChanges[] = [
          'DocumentoId' => $docFV->DocumentoId,
          'Modulo' => $docFV->Modulo,
          'Area' => $docFV->Area,
        ];
        return;
      }

      $matched[] = [
        'DocumentoId' => $docFV->DocumentoId,
        'ElectronicDocumentId' => $electronicDocument->id,
        'AreaId' => $electronicDocument->area_id,
        'CurrentModulo' => $docFV->Modulo,
        'CurrentArea' => $docFV->Area,
        'NewModulo' => $newModulo,
        'NewArea' => $newArea,
      ];
    });

    $this->newLine(2);

    // Mostrar resumen
    $this->info('=== RESUMEN ===');
    $this->table(
      ['Categoría', 'Cantidad'],
      [
        ['Registros con cambios', count($matched)],
        ['Registros sin cambios', count($noChanges)],
        ['Registros sin match', count($notMatched)],
      ]
    );

    $this->newLine();

    // Mostrar previsualización de cambios
    if (!empty($matched)) {
      $this->info('=== PREVISUALIZACIÓN DE CAMBIOS ===');

      // Mostrar solo los primeros 20 para no saturar la consola
      $preview = array_slice($matched, 0, 20);
      $this->table(
        ['DocumentoId', 'Doc ID', 'Area ID', 'Modulo Actual', 'Area Actual', 'Nuevo Modulo', 'Nueva Area'],
        array_map(function ($item) {
          return [
            $item['DocumentoId'],
            $item['ElectronicDocumentId'],
            $item['AreaId'],
            $item['CurrentModulo'],
            $item['CurrentArea'],
            $item['NewModulo'],
            $item['NewArea'],
          ];
        }, $preview)
      );

      if (count($matched) > 20) {
        $this->warn("... y " . (count($matched) - 20) . " registros más");
      }
      $this->newLine();
    }

    // Mostrar registros sin match
    if (!empty($notMatched) && count($notMatched) <= 10) {
      $this->warn('=== REGISTROS SIN MATCH ===');
      $this->table(
        ['DocumentoId', 'Modulo Actual', 'Area Actual'],
        array_map(function ($item) {
          return [
            $item['DocumentoId'],
            $item['CurrentModulo'],
            $item['CurrentArea'],
          ];
        }, $notMatched)
      );
      $this->newLine();
    } elseif (!empty($notMatched)) {
      $this->warn("Hay " . count($notMatched) . " registros sin match en ElectronicDocument");
      $this->newLine();
    }

    // Si es solo previsualización, terminar aquí
    if ($this->option('preview')) {
      $this->info('Modo previsualización activado. No se realizaron cambios.');
      return 0;
    }

    // Si no hay cambios, terminar
    if (empty($matched)) {
      $this->info('No hay registros para actualizar.');
      return 0;
    }

    // Pedir confirmación si no está en modo force
    if (!$this->option('force')) {
      if (!$this->confirm("¿Desea continuar con la actualización de " . count($matched) . " registros?", false)) {
        $this->warn('Operación cancelada por el usuario.');
        return 0;
      }
    }

    // Actualizar registros
    $this->info('Actualizando registros...');
    $updated = 0;
    $errors = 0;

    $this->withProgressBar($matched, function ($item) use (&$updated, &$errors) {
      try {
        DB::connection(Company::CONNECTION_DYNAMICS_3)
          ->table('RM20101_DOCFV')
          ->where('DocumentoId', $item['DocumentoId'])
          ->update([
            'Modulo' => $item['NewModulo'],
            'Area' => $item['NewArea'],
          ]);
        $updated++;
      } catch (\Exception $e) {
        $errors++;
        $this->error("Error actualizando {$item['DocumentoId']}: {$e->getMessage()}");
      }
    });

    $this->newLine(2);

    // Mostrar resultado final
    $this->info('=== RESULTADO FINAL ===');
    $this->table(
      ['Estado', 'Cantidad'],
      [
        ['Registros actualizados', $updated],
        ['Errores', $errors],
      ]
    );

    if ($updated > 0) {
      $this->info('✓ Regularización completada exitosamente');
    }

    return 0;
  }
}
