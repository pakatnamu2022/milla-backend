<?php

namespace App\Models\ap\postventa\gestionProductos;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

/**
 * Modelo para períodos contables
 *
 * Un período contable representa un mes calendario completo que puede cerrarse
 * para evitar modificaciones retroactivas y garantizar integridad contable.
 *
 * @property int $id
 * @property string $name
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property bool $is_closed
 * @property Carbon|null $closed_at
 * @property int|null $closed_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read User|null $closedByUser
 */
class AccountingPeriod extends Model
{
    protected $table = 'accounting_periods';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_closed',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
        'closed_by' => 'integer',
    ];

    /**
     * Relación con el usuario que cerró el período
     */
    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Scope: Períodos cerrados
     */
    public function scopeClosed($query)
    {
        return $query->where('is_closed', true);
    }

    /**
     * Scope: Períodos abiertos
     */
    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }

    /**
     * Validación automática antes de guardar
     *
     * Valida que:
     * - start_date sea el día 1 del mes
     * - end_date sea el último día del mes
     * - Ambas fechas sean del mismo mes
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (AccountingPeriod $period) {
            $start = Carbon::parse($period->start_date);
            $end = Carbon::parse($period->end_date);

            // Validar que start_date sea día 1 del mes
            if ($start->day !== 1) {
                throw ValidationException::withMessages([
                    'start_date' => ['start_date debe ser el día 1 del mes']
                ]);
            }

            // Validar que end_date sea el último día del mes
            if (!$end->isSameDay($end->copy()->endOfMonth())) {
                throw ValidationException::withMessages([
                    'end_date' => ['end_date debe ser el último día del mes']
                ]);
            }

            // Validar que sean del mismo mes
            if (!$start->isSameMonth($end)) {
                throw ValidationException::withMessages([
                    'start_date' => ['El período debe representar un solo mes calendario'],
                    'end_date' => ['El período debe representar un solo mes calendario']
                ]);
            }
        });
    }

    /**
     * Verifica si una fecha dada está dentro de un período cerrado
     *
     * @param string|Carbon $date
     * @return bool
     */
    public static function isDateClosed($date): bool
    {
        $date = Carbon::parse($date);

        return static::closed()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }

    /**
     * Obtiene el período (abierto o cerrado) que contiene una fecha
     *
     * @param string|Carbon $date
     * @return AccountingPeriod|null
     */
    public static function getPeriodForDate($date): ?AccountingPeriod
    {
        $date = Carbon::parse($date);

        return static::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->with('closedByUser')
            ->first();
    }

    /**
     * Cierra el período contable
     *
     * @param int $userId ID del usuario que cierra el período
     * @return bool
     * @throws \Exception
     */
    public function close(int $userId): bool
    {
        if ($this->is_closed) {
            throw new \Exception('El período ya está cerrado');
        }

        return $this->update([
            'is_closed' => true,
            'closed_at' => now(),
            'closed_by' => $userId,
        ]);
    }

    /**
     * Reabre el período contable (requiere permisos especiales)
     *
     * @return bool
     * @throws \Exception
     */
    public function reopen(): bool
    {
        if (!$this->is_closed) {
            throw new \Exception('El período ya está abierto');
        }

        return $this->update([
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
        ]);
    }

    /**
     * Genera el nombre del período basado en las fechas
     *
     * @return string Ej: "Enero 2026", "Febrero 2026"
     */
    public function generateName(): string
    {
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        $start = Carbon::parse($this->start_date);
        $monthName = $monthNames[$start->month];
        $year = $start->year;

        return "{$monthName} {$year}";
    }

    /**
     * Crea un período para un mes específico
     *
     * @param int $month Mes (1-12)
     * @param int $year Año
     * @return static
     */
    public static function createForMonth(int $month, int $year): static
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $period = new static([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_closed' => false,
        ]);

        $period->name = $period->generateName();
        $period->save();

        return $period;
    }
}