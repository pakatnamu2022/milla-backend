<?php

namespace App\Models\gp\gestionhumana\personal;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerContract extends BaseModel
{
    protected $table = 'rrhh_contrato';

    protected $fillable = [
        'empleado_id',
        'sueldo',
        'fecha_inicio_contrato',
        'fecha_fin_contrato',
        'status_deleted',
    ];

    protected $casts = [
        'sueldo' => 'decimal:2',
        'fecha_inicio_contrato' => 'date',
        'fecha_fin_contrato' => 'date',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'empleado_id');
    }

    /**
     * ID de rrhh_tipo_contrato para "INDETERMINADO" (cacheado por request).
     * A diferencia de los contratos a plazo fijo (que sí se renuevan/reemplazan
     * en rrhh_contrato cada vez que cambia el sueldo), a los trabajadores con
     * contrato indeterminado no se les vuelve a contratar tras un aumento —
     * RRHH solo actualiza rrhh_persona.sueldo directamente — así que el monto
     * guardado en su último contrato queda desactualizado indefinidamente.
     */
    private static function indeterminadoTipoContratoId(): ?int
    {
        static $id = null;
        static $resolved = false;

        if (!$resolved) {
            $id = \DB::table('rrhh_tipo_contrato')
                ->where('descripcion', 'INDETERMINADO')
                ->value('id');
            $resolved = true;
        }

        return $id;
    }

    /**
     * Sueldo vigente de un trabajador en una fecha dada, según su historial de
     * contratos (rrhh_contrato) — evita usar rrhh_persona.sueldo (siempre el
     * sueldo ACTUAL) al generar planillas de periodos pasados, donde el
     * trabajador podía tener un sueldo distinto (contrato anterior con otro
     * monto, antes de un ascenso/renovación).
     *
     * Busca primero el contrato vigente exactamente en esa fecha; si no hay
     * ninguno (huecos entre contratos), cae al contrato más reciente que ya
     * había iniciado antes de esa fecha. Devuelve null si el trabajador no
     * tiene ningún contrato con sueldo registrado — el llamador debe hacer
     * fallback a rrhh_persona.sueldo.
     *
     * Aumentos registrados (gh_salary_increases, solo indeterminados): si hay un aumento con
     * fecha efectiva <= $date y posterior (o igual) al inicio del contrato resuelto, gana sobre
     * el sueldo del contrato — es el sueldo real vigente aunque el contrato no se haya renovado.
     *
     * Excepción (legado): si el contrato resuelto es el ÚLTIMO contrato del trabajador y es
     * INDETERMINADO, y el trabajador aún NO tiene aumentos registrados, se devuelve null para
     * que el llamador use rrhh_persona.sueldo — su columna `sueldo` no es confiable porque el
     * contrato nunca se reemplazó al subir el sueldo. Al registrar el primer aumento, el log
     * pasa a ser la fuente de verdad y esta excepción deja de aplicar a ese trabajador.
     */
    public static function salaryForWorkerAtDate(int $workerId, string $date): ?float
    {
        $contract = self::resolveContractAtDate($workerId, $date);
        $increase = SalaryIncrease::latestAtDate($workerId, $date);

        if ($increase && (!$contract || $increase->effective_date->gte($contract->fecha_inicio_contrato))) {
            return (float)$increase->new_salary;
        }

        if (!$contract) {
            return null;
        }

        $indeterminadoId = self::indeterminadoTipoContratoId();
        if ($indeterminadoId !== null && $contract->tipo_contrato_id === $indeterminadoId) {
            $isLastContract = !static::where('empleado_id', $workerId)
                ->where('status_deleted', 1)
                ->where('fecha_inicio_contrato', '>', $contract->fecha_inicio_contrato)
                ->exists();

            if ($isLastContract && !SalaryIncrease::where('worker_id', $workerId)->exists()) {
                return null;
            }
        }

        return (float)$contract->sueldo;
    }

    /**
     * Último contrato vigente (por fecha de inicio) del trabajador, o null.
     * Lo usa el registro de aumentos: solo procede si este contrato es INDETERMINADO.
     */
    public static function latestContract(int $workerId): ?self
    {
        return static::where('empleado_id', $workerId)
            ->where('status_deleted', 1)
            ->orderByDesc('fecha_inicio_contrato')
            ->orderByDesc('id')
            ->first();
    }

    public static function isIndeterminado(self $contract): bool
    {
        $id = self::indeterminadoTipoContratoId();

        return $id !== null && (int)$contract->tipo_contrato_id === $id;
    }

    public static function resolveContractAtDate(int $workerId, string $date): ?self
    {
        $base = static::where('empleado_id', $workerId)
            ->where('status_deleted', 1)
            ->whereNotNull('sueldo')
            ->where('sueldo', '>', 0)
            ->where('fecha_inicio_contrato', '<=', $date);

        $contract = (clone $base)
            ->where(function ($q) use ($date) {
                $q->whereNull('fecha_fin_contrato')
                    ->orWhere('fecha_fin_contrato', '>=', $date);
            })
            ->orderByDesc('fecha_inicio_contrato')
            ->first();

        if (!$contract) {
            // Sin contrato vigente exacto (hueco entre contratos): usar el más
            // reciente que ya había iniciado a esa fecha.
            $contract = (clone $base)
                ->orderByDesc('fecha_inicio_contrato')
                ->first();
        }

        return $contract;
    }
}
