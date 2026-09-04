<?php

namespace App\Console\Commands\gp\gestionhumana\personal;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * `rrhh_persona` tiene DNIs (vat) duplicados: varias filas para la misma persona, de las cuales
 * solo una es la "buena" (status_id=22, status_deleted=1, b_empleado=1 — trabajador activo). El
 * resto son duplicados (clientes, registros viejos, etc.) que igual pueden tener referencias
 * `worker_id` colgando en otras tablas (ej. gh_payroll_calculations), lo que hace que cálculos
 * como el promedio de horas extra de 6 meses no encuentren datos que en realidad sí existen,
 * solo que enganchados al ID equivocado.
 *
 * Este comando busca, por cada grupo de DNI duplicado, la fila "buena" (22,1,1) y reasigna en
 * todas las tablas con `worker_id -> rrhh_persona.id` cualquier referencia que apunte a las
 * filas duplicadas, para que apunten a la fila buena. No borra ni modifica `rrhh_persona`: solo
 * corrige las referencias.
 */
class FixDuplicateWorkerReferencesCommand extends Command
{
  protected $signature = 'workers:fix-duplicate-references
    {--dry : Solo mostrar lo que se haría, sin escribir en la base de datos}
    {--force : No pedir confirmación antes de aplicar los cambios}';

  protected $description = 'Reasigna worker_id de duplicados de rrhh_persona (mismo DNI) hacia la fila activa correcta (status_id=22, status_deleted=1, b_empleado=1)';

  /**
   * Tablas con columna worker_id que referencia rrhh_persona.id (confirmado vía
   * information_schema.KEY_COLUMN_USAGE). Se excluye user_series_assignment.worker_id, que
   * referencia usr_users.id, no rrhh_persona.
   */
  private const TABLES = [
    'ap_assign_brand_consultant',
    'ap_assign_company_branch_period',
    'ap_assignment_leadership_periods',
    'ap_campaign_schedules',
    'ap_opportunity',
    'detailed_development_plan',
    'gh_accountant_district_assignments',
    'gh_evaluation_par_evaluator',
    'gh_mobility_payroll',
    'gh_payroll_bonuses',
    'gh_payroll_calculations',
    'gh_payroll_family_allowance',
    'gh_payroll_food_card',
    'gh_payroll_insurances',
    'gh_payroll_liquidation_bbss',
    'gh_payroll_loans',
    'gh_payroll_register',
    'gh_payroll_schedules',
    'gh_working_conditions',
    'objective_advisors_period_pv',
    'phone_line_worker',
    'potential_buyers',
    'work_order_labour',
    'work_order_planning',
    'worker_attendance_rule',
    'worker_signature',
  ];

  public function handle(): int
  {
    $dry = (bool) $this->option('dry');

    $persons = DB::table('rrhh_persona')
      ->select(['id', 'vat', 'nombre_completo', 'status_id', 'status_deleted', 'b_empleado'])
      ->whereNotNull('vat')
      ->where('vat', '!=', '')
      ->get()
      ->groupBy(fn ($p) => trim((string) $p->vat));

    $duplicateGroups = $persons->filter(fn ($group) => $group->count() > 1);

    if ($duplicateGroups->isEmpty()) {
      $this->info('No se encontraron DNIs duplicados en rrhh_persona.');
      return self::SUCCESS;
    }

    $this->info("DNIs duplicados encontrados: {$duplicateGroups->count()}");
    $this->newLine();

    $ambiguous = [];
    $resolvable = [];

    foreach ($duplicateGroups as $vat => $group) {
      $good = $group->filter(fn ($p) => (int) $p->status_id === 22 && (int) $p->status_deleted === 1 && (int) $p->b_empleado === 1);

      if ($good->count() !== 1) {
        $ambiguous[] = [
          'vat' => $vat,
          'nombres' => $group->pluck('nombre_completo')->implode(' | '),
          'ids' => $group->pluck('id')->implode(', '),
          'buenos_encontrados' => $good->count(),
        ];
        continue;
      }

      $goodRow = $good->first();
      $badIds = $group->reject(fn ($p) => $p->id === $goodRow->id)->pluck('id')->values()->all();

      $resolvable[] = [
        'vat' => $vat,
        'nombre' => $goodRow->nombre_completo,
        'good_id' => $goodRow->id,
        'bad_ids' => $badIds,
      ];
    }

    if (!empty($ambiguous)) {
      $this->warn('Grupos AMBIGUOS (no tienen exactamente una fila 22/1/1) — requieren revisión manual, no se tocan:');
      $this->table(['DNI', 'Nombres', 'IDs', 'Filas buenas encontradas'], $ambiguous);
      $this->newLine();
    }

    if (empty($resolvable)) {
      $this->info('No hay grupos resolubles automáticamente.');
      return self::SUCCESS;
    }

    $this->info('Grupos a corregir:');
    $this->table(
      ['DNI', 'Nombre', 'ID bueno', 'IDs duplicados'],
      array_map(fn ($r) => [$r['vat'], $r['nombre'], $r['good_id'], implode(', ', $r['bad_ids'])], $resolvable)
    );
    $this->newLine();

    if (!$dry && !$this->option('force')) {
      if (!$this->confirm('¿Confirmas aplicar estos cambios en la base de datos?', false)) {
        $this->warn('Cancelado. No se modificó nada.');
        return self::SUCCESS;
      }
    }

    $summary = [];
    $conflicts = [];

    DB::beginTransaction();
    try {
      foreach ($resolvable as $r) {
        foreach (self::TABLES as $table) {
          $rows = DB::table($table)->whereIn('worker_id', $r['bad_ids'])->get(['id']);

          if ($rows->isEmpty()) {
            continue;
          }

          $updated = 0;
          foreach ($rows as $row) {
            try {
              DB::table($table)->where('id', $row->id)->update(['worker_id' => $r['good_id']]);
              $updated++;
            } catch (QueryException $e) {
              $conflicts[] = [
                'tabla' => $table,
                'row_id' => $row->id,
                'dni' => $r['vat'],
                'good_id' => $r['good_id'],
                'error' => $e->getMessage(),
              ];
            }
          }

          if ($updated > 0) {
            $summary[] = [
              'dni' => $r['vat'],
              'nombre' => $r['nombre'],
              'tabla' => $table,
              'filas_actualizadas' => $updated,
            ];
          }
        }
      }

      if ($dry) {
        DB::rollBack();
        $this->newLine();
        $this->info('--dry: no se escribió nada, esto es lo que se HABRÍA actualizado:');
      } else {
        DB::commit();
        $this->newLine();
        $this->info('Cambios aplicados:');
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      $this->error('Error inesperado, se revirtió todo: ' . $e->getMessage());
      return self::FAILURE;
    }

    if (empty($summary)) {
      $this->info('Ninguna tabla tenía referencias worker_id a los IDs duplicados.');
    } else {
      $this->table(['DNI', 'Nombre', 'Tabla', 'Filas'], $summary);
    }

    if (!empty($conflicts)) {
      $this->newLine();
      $this->error('Conflictos (ya existía una fila con ese worker_id "bueno" y no se pudo mover, requieren revisión manual):');
      $this->table(['Tabla', 'Row ID', 'DNI', 'ID bueno', 'Error'], $conflicts);
    }

    return self::SUCCESS;
  }
}
