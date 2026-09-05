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
     * Excepción: si el contrato resuelto es el ÚLTIMO contrato del trabajador
     * (no existe uno posterior) y es de tipo INDETERMINADO, se devuelve null
     * a propósito para que el llamador use rrhh_persona.sueldo — un contrato
     * indeterminado nunca se reemplaza al subir el sueldo, así que su columna
     * `sueldo` no es confiable como fuente de verdad para ese caso.
     */
    public static function salaryForWorkerAtDate(int $workerId, string $date): ?float
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

        if (!$contract) {
            return null;
        }

        $indeterminadoId = self::indeterminadoTipoContratoId();
        if ($indeterminadoId !== null && $contract->tipo_contrato_id === $indeterminadoId) {
            $isLastContract = !static::where('empleado_id', $workerId)
                ->where('status_deleted', 1)
                ->where('fecha_inicio_contrato', '>', $contract->fecha_inicio_contrato)
                ->exists();

            if ($isLastContract) {
                return null;
            }
        }

        return (float)$contract->sueldo;
    }
}
