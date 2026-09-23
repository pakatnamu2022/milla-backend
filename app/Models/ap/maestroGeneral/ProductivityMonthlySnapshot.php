<?php

namespace App\Models\ap\maestroGeneral;

use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductivityMonthlySnapshot extends Model
{
    protected $table = 'ap_productivity_monthly_snapshots';

    protected $fillable = [
        'year',
        'month',
        'sede_id',
        // Core metrics
        'total_technicians',
        'total_billed_hours',
        'total_standard_hours',
        'total_productivity_hours',
        'total_earnings',
        'average_productivity_percentage',
        // Work order metrics
        'total_ots_closed',
        'ots_with_labour_charged',
        'ots_without_labour_charged',
        // Efficiency indicators
        'avg_hours_per_ot',
        'billing_rate',
        'reentry_rate',
        'labour_coverage_rate',
        // Status distribution
        'technicians_exceeded',
        'technicians_on_track',
        'technicians_warning',
        'technicians_critical',
        // Metadata
        'snapshot_date',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'sede_id' => 'integer',
        'total_technicians' => 'integer',
        'total_billed_hours' => 'decimal:2',
        'total_standard_hours' => 'decimal:2',
        'total_productivity_hours' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'average_productivity_percentage' => 'decimal:2',
        'total_ots_closed' => 'integer',
        'ots_with_labour_charged' => 'integer',
        'ots_without_labour_charged' => 'integer',
        'avg_hours_per_ot' => 'decimal:2',
        'billing_rate' => 'decimal:2',
        'reentry_rate' => 'decimal:2',
        'labour_coverage_rate' => 'decimal:2',
        'technicians_exceeded' => 'integer',
        'technicians_on_track' => 'integer',
        'technicians_warning' => 'integer',
        'technicians_critical' => 'integer',
        'snapshot_date' => 'datetime',
        'created_by' => 'integer',
    ];

    /**
     * Get the sede (headquarters) associated with this snapshot
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    /**
     * Scope to filter by year
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope to filter by month
     */
    public function scopeForMonth($query, int $month)
    {
        return $query->where('month', $month);
    }

    /**
     * Scope to filter by sede
     * If $sedeId is null, returns all sedes with workshop
     */
    public function scopeForSede($query, ?int $sedeId)
    {
        if ($sedeId === null) {
            return $query->onlyWithWorkshop();
        }
        return $query->where('sede_id', $sedeId);
    }

    /**
     * Scope to filter only snapshots from sedes with workshop (has_workshop = 1)
     */
    public function scopeOnlyWithWorkshop($query)
    {
        return $query->whereHas('sede', function ($q) {
            $q->where('has_workshop', 1);
        });
    }

    /**
     * Scope to get all workshop sedes snapshots for consolidation
     */
    public function scopeForConsolidation($query)
    {
        return $query->onlyWithWorkshop();
    }

    /**
     * Scope to order by period (year, month)
     */
    public function scopeOrderByPeriod($query, string $direction = 'asc')
    {
        return $query->orderBy('year', $direction)->orderBy('month', $direction);
    }

    /**
     * Get snapshot for a specific period
     *
     * @param int $year
     * @param int $month
     * @param int|null $sedeId
     * @return ProductivityMonthlySnapshot|null
     */
    public static function getForPeriod(int $year, int $month, ?int $sedeId = null): ?self
    {
        return self::where('year', $year)
            ->where('month', $month)
            ->forSede($sedeId)
            ->first();
    }

    /**
     * Get all snapshots for a year
     *
     * @param int $year
     * @param int|null $sedeId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getYearData(int $year, ?int $sedeId = null)
    {
        return self::forYear($year)
            ->forSede($sedeId)
            ->orderByPeriod()
            ->get();
    }

    /**
     * Get comparison data between years
     *
     * @param array $years
     * @param int|null $sedeId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getYearsComparison(array $years, ?int $sedeId = null)
    {
        return self::whereIn('year', $years)
            ->forSede($sedeId)
            ->orderByPeriod()
            ->get();
    }

    /**
     * Get period description (formatted)
     */
    public function getPeriodDescriptionAttribute(): string
    {
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        return $monthNames[$this->month] . ' ' . $this->year;
    }

    /**
     * Check if this sede has a workshop
     */
    public function getHasWorkshopAttribute(): bool
    {
        return $this->sede?->has_workshop ?? false;
    }

    /**
     * Get the productivity status based on percentage
     */
    public function getProductivityStatusAttribute(): string
    {
        $percentage = $this->average_productivity_percentage;

        if ($percentage < 70) {
            return 'critical';
        } elseif ($percentage < 99) {
            return 'warning';
        } elseif ($percentage <= 100) {
            return 'on_track';
        } else {
            return 'exceeded';
        }
    }

    /**
     * Delete snapshot for a specific period
     *
     * @param int $year
     * @param int $month
     * @param int|null $sedeId
     * @return bool
     */
    public static function deleteForPeriod(int $year, int $month, ?int $sedeId = null): bool
    {
        return self::where('year', $year)
            ->where('month', $month)
            ->forSede($sedeId)
            ->delete();
    }
}