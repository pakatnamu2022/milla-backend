<?php

namespace App\Models\ap\maestroGeneral;

use Illuminate\Database\Eloquent\Model;

class TechnicianHourlyCost extends Model
{
    protected $table = 'ap_technician_hourly_costs';

    protected $fillable = [
        'year',
        'month',
        'cost_per_hour',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'cost_per_hour' => 'decimal:2',
    ];

    /**
     * Obtiene el costo por hora para un periodo específico
     * Si no existe, retorna el valor por defecto de general_masters
     *
     * @param int $year
     * @param int $month
     * @return float
     */
    public static function getCostForPeriod(int $year, int $month): float
    {
        // Buscar en histórico
        $cost = self::where('year', $year)
            ->where('month', $month)
            ->value('cost_per_hour');

        if ($cost !== null) {
            return (float) $cost;
        }

        // Fallback: tomar de general_masters (ID = 61)
        $defaultCost = \DB::table('general_masters')
            ->where('id', 61)
            ->value('value');

        return (float) ($defaultCost ?? 8.00);
    }
}
