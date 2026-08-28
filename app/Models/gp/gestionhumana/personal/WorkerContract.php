<?php

namespace App\Models\gp\gestionhumana\personal;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerContract extends BaseModel
{
    protected $table = 'rrhh_contrato';

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

        return $contract ? (float)$contract->sueldo : null;
    }
}
