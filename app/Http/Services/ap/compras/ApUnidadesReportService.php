<?php

namespace App\Http\Services\ap\compras;

use App\Models\ap\ApMasters;
use App\Models\ap\compras\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ApUnidadesReportService
{
    /**
     * Rango de días vencidos por número de renovación.
     * Clave: número de renovaciones. Valor: rango mínimo/máximo/etiqueta.
     */
    public const RANGOS_RENOVACIONES = [
        0 => ['min' => 1,   'max' => 30,  'label' => '01-30'],
        1 => ['min' => 31,  'max' => 60,  'label' => '31-60'],
        2 => ['min' => 61,  'max' => 90,  'label' => '61-90'],
        3 => ['min' => 91,  'max' => 120, 'label' => '91-120'],
        4 => ['min' => 121, 'max' => 150, 'label' => '121-150'],
        5 => ['min' => 151, 'max' => 180, 'label' => '151-180'],
        6 => ['min' => 181, 'max' => 210, 'label' => '181-210'],
        7 => ['min' => 211, 'max' => 240, 'label' => '211-240'],
        8 => ['min' => 241, 'max' => 270, 'label' => '241-270'],
    ];

    /**
     * Calcula los días vencidos desde la fecha de emisión hasta hoy.
     * Retorna 0 si la fecha es nula o futura.
     */
    public static function calcDiasVencido(?Carbon $emisionDate): int
    {
        if (!$emisionDate) {
            return 0;
        }
        $dias = (int) $emisionDate->diffInDays(Carbon::today(), false);
        return max(0, $dias);
    }

    /**
     * Determina el número de renovaciones a partir de los días vencidos.
     */
    public static function calcRenovaciones(int $dias): int
    {
        if ($dias <= 30)  return 0;
        if ($dias <= 60)  return 1;
        if ($dias <= 90)  return 2;
        if ($dias <= 120) return 3;
        if ($dias <= 150) return 4;
        if ($dias <= 180) return 5;
        if ($dias <= 210) return 6;
        if ($dias <= 240) return 7;
        return 8;
    }

    /**
     * Resuelve el estatus de la unidad desde el financing_type del documento electrónico.
     * LIBRE = sin documento de venta asociado.
     */
    public static function resolveEstatus(?string $financingType): string
    {
        if (empty($financingType)) {
            return 'LIBRE';
        }
        return strtoupper(trim($financingType));
    }

    /**
     * Construye la query base con filtros de fecha aplicables en SQL.
     * Los filtros de estatus y sede se aplican posteriormente en PHP sobre la colección
     * porque provienen de relaciones calculadas.
     */
    private function buildBaseQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = PurchaseOrder::with([
            'sede',
            'supplier',
            'vehicle.electronicDocumentParent',
            'vehicle.model.family.brand',
            'vehicle.model.vehicleType',
            'vehicle.color',
        ])
            ->where('type_operation_id', ApMasters::TIPO_OPERACION_COMERCIAL)
            ->whereNull('deleted_at');

        if (!empty($filters['fecha_emision_desde'])) {
            $query->where('emission_date', '>=', $filters['fecha_emision_desde']);
        }
        if (!empty($filters['fecha_emision_hasta'])) {
            $query->where('emission_date', '<=', $filters['fecha_emision_hasta']);
        }
        if (!empty($filters['fecha_vencimiento_desde'])) {
            $query->where('due_date', '>=', $filters['fecha_vencimiento_desde']);
        }
        if (!empty($filters['fecha_vencimiento_hasta'])) {
            $query->where('due_date', '<=', $filters['fecha_vencimiento_hasta']);
        }

        $today = Carbon::today();

        // dias_vencido_min → emission_date <= today - dias_vencido_min
        if (isset($filters['dias_vencido_min']) && $filters['dias_vencido_min'] !== null) {
            $query->where('emission_date', '<=', $today->copy()->subDays((int) $filters['dias_vencido_min']));
        }

        // dias_vencido_max → emission_date >= today - dias_vencido_max
        if (isset($filters['dias_vencido_max']) && $filters['dias_vencido_max'] !== null) {
            $query->where('emission_date', '>=', $today->copy()->subDays((int) $filters['dias_vencido_max']));
        }

        // renovaciones → rango de dias en emission_date
        if (isset($filters['renovaciones']) && isset(self::RANGOS_RENOVACIONES[$filters['renovaciones']])) {
            $rango = self::RANGOS_RENOVACIONES[$filters['renovaciones']];
            $query->whereBetween('emission_date', [
                $today->copy()->subDays($rango['max']),
                $today->copy()->subDays($rango['min']),
            ]);
        }

        return $query;
    }

    /**
     * Carga los registros y agrega las dimensiones calculadas como atributos dinámicos.
     * Aplica los filtros de estatus y sede que no pueden resolverse en SQL.
     */
    private function enrichAndFilter(array $filters): Collection
    {
        $records = $this->buildBaseQuery($filters)->get();

        $enriched = $records->map(function (PurchaseOrder $po) {
            $dias = self::calcDiasVencido($po->emission_date ? Carbon::parse($po->emission_date) : null);
            $po->_dias_vencido  = $dias;
            $po->_renovaciones  = self::calcRenovaciones($dias);
            $po->_estatus       = self::resolveEstatus($po->vehicle?->electronicDocumentParent?->financing_type);
            $po->_sede          = strtoupper(trim($po->sede?->suc_abrev ?? 'SIN SEDE'));
            $po->_marca         = $po->vehicle?->model?->family?->brand?->name ?? '';
            $po->_modelo        = $po->vehicle?->model?->version ?? '';
            $po->_tipo_vehiculo = $po->vehicle?->model?->vehicleType?->description ?? '';
            return $po;
        });

        // Filtro de estatus (computed en PHP)
        if (!empty($filters['estatus'])) {
            $estatusFiltro = strtoupper(trim($filters['estatus']));
            $enriched = $enriched->filter(fn($po) => $po->_estatus === $estatusFiltro);
        }

        // Filtro de sede (computed en PHP; podría hacerse en SQL con whereHas pero
        // suc_abrev puede variar en casing entre la tabla y el filtro)
        if (!empty($filters['sede'])) {
            $sedeFiltro = strtoupper(trim($filters['sede']));
            $enriched = $enriched->filter(fn($po) => $po->_sede === $sedeFiltro);
        }

        return $enriched->values();
    }

    /**
     * Resumen pivotado por estatus (filas) y sede (columnas).
     * Equivale a la TABLA 1 del Excel con la tabla dinámica.
     */
    public function getResumen(array $filters): array
    {
        $enriched = $this->enrichAndFilter($filters);

        $sedes = $enriched->pluck('_sede')->unique()->sort()->values()->toArray();

        $rows = $enriched->groupBy('_estatus')->map(function (Collection $items, string $estatus) use ($sedes) {
            $totalMonto    = 0.0;
            $totalCantidad = 0;
            $values        = [];

            $bySede = $items->groupBy('_sede');

            foreach ($sedes as $sede) {
                $sedeItems        = $bySede->get($sede, collect());
                $monto            = round((float) $sedeItems->sum('total'), 2);
                $cantidad         = $sedeItems->count();
                $values[$sede]    = ['monto' => $monto, 'cantidad' => $cantidad];
                $totalMonto      += $monto;
                $totalCantidad   += $cantidad;
            }

            return [
                'estatus' => $estatus,
                'values'  => $values,
                'total'   => ['monto' => round($totalMonto, 2), 'cantidad' => $totalCantidad],
            ];
        })->values()->toArray();

        return [
            'filters' => [
                'estatus'      => $filters['estatus'] ?? null,
                'sede'         => $filters['sede'] ?? null,
                'renovaciones' => isset($filters['renovaciones']) ? (int) $filters['renovaciones'] : null,
            ],
            'columns' => $sedes,
            'rows'    => $rows,
        ];
    }

    /**
     * Indicadores principales para el dashboard de unidades.
     */
    public function getDashboard(array $filters): array
    {
        $enriched = $this->enrichAndFilter($filters);

        $vencidas = $enriched->filter(fn($po) => $po->_dias_vencido > 0);
        $libres   = $enriched->filter(fn($po) => $po->_estatus === 'LIBRE');
        $conDoc   = $enriched->filter(fn($po) => $po->_estatus !== 'LIBRE');
        $sinSede  = $enriched->filter(fn($po) => $po->_sede === 'SIN SEDE');

        $porSede = $enriched->groupBy('_sede')->map(fn($items, $sede) => [
            'sede'     => $sede,
            'monto'    => round((float) $items->sum('total'), 2),
            'cantidad' => $items->count(),
        ])->sortKeys()->values();

        $porEstatus = $enriched->groupBy('_estatus')->map(fn($items, $estatus) => [
            'estatus'  => $estatus,
            'monto'    => round((float) $items->sum('total'), 2),
            'cantidad' => $items->count(),
        ])->values();

        $porRenovaciones = $enriched->groupBy('_renovaciones')
            ->map(fn($items, $reno) => [
                'renovaciones' => (int) $reno,
                'rango'        => self::RANGOS_RENOVACIONES[$reno]['label'] ?? 'N/A',
                'monto'        => round((float) $items->sum('total'), 2),
                'cantidad'     => $items->count(),
            ])
            ->sortKeys()
            ->values();

        return [
            'total_operaciones'       => $enriched->count(),
            'total_monto'             => round((float) $enriched->sum('total'), 2),
            'total_vencido'           => round((float) $vencidas->sum('total'), 2),
            'cantidad_vencida'        => $vencidas->count(),
            'con_documento'           => ['monto' => round((float) $conDoc->sum('total'), 2), 'cantidad' => $conDoc->count()],
            'libres'                  => ['monto' => round((float) $libres->sum('total'), 2), 'cantidad' => $libres->count()],
            'sin_sede'                => $sinSede->count(),
            'por_sede'                => $porSede,
            'por_estatus'             => $porEstatus,
            'por_renovaciones'        => $porRenovaciones,
            'por_rango_vencimiento'   => $this->calcPorRangoVencimiento($enriched),
        ];
    }

    /**
     * Distribución de unidades por rango de días vencidos.
     */
    public function getVencimientos(array $filters): array
    {
        $enriched = $this->enrichAndFilter($filters);
        return $this->calcPorRangoVencimiento($enriched);
    }

    private function calcPorRangoVencimiento(Collection $enriched): array
    {
        return collect(self::RANGOS_RENOVACIONES)->map(function (array $rango, int $reno) use ($enriched) {
            $items = $enriched->filter(fn($po) => $po->_renovaciones === $reno);
            return [
                'rango'    => $rango['label'],
                'dias_min' => $rango['min'],
                'dias_max' => $rango['max'],
                'monto'    => round((float) $items->sum('total'), 2),
                'cantidad' => $items->count(),
            ];
        })->values()->toArray();
    }
}
