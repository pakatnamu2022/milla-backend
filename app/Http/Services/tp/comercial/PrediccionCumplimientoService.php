<?php

namespace App\Http\Services\tp\comercial;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrediccionCumplimientoService
{
    public function predecirCumplimiento(
        int $mesesHistoricos = 12,
        float $factorConfianza = 1.0,
        bool $detectarOutliers = true,
        bool $ponderarRecientes = true
    ): array {
        $datos = $this->obtenerDatosHistoricos($mesesHistoricos);
        
        if (count($datos) < 3) {
            return $this->respuestaError('Se necesitan al menos 3 meses de datos.');
        }

        if ($detectarOutliers) {
            $datos = $this->eliminarOutliers($datos);
        }

        if ($ponderarRecientes) {
            $datos = $this->aplicarPonderacion($datos);
        }

        $regresion = $this->calcularRegresionLinealPonderada($datos);

        $mesActual = Carbon::now();
        $prediccionLineal = $this->predecirConRegresion($regresion);

        $prediccionExponencial = $this->suavizamientoExponencial($datos);

        $prediccionFinal = ($prediccionLineal * 0.6) + ($prediccionExponencial * 0.4);

        $prediccionAjustada = $prediccionFinal * $factorConfianza;

        $metaActual = $this->obtenerMetaMes($mesActual->year, $mesActual->month);

        // 10. Calcular cumplimiento proyectado
        $cumplimientoProyectado = $metaActual > 0 
            ? round(($prediccionAjustada / $metaActual) * 100, 2) 
            : 0;

        $precision = $this->calcularPrecisionMejorada($datos, $regresion);
        $r2 = $regresion['r2'];
        $nivelConfianza = $this->determinarNivelConfianza($precision, count($datos), $r2);

        $recomendaciones = $this->generarRecomendacionesAvanzadas(
            $cumplimientoProyectado,
            $regresion['pendiente'],
            $r2,
            $precision
        );

        return [
            'success' => true,
            'datos_historicos' => $datos,
            'prediccion' => [
                'produccion_estimada' => round($prediccionAjustada, 2),
                'meta_mes_actual' => $metaActual,
                'cumplimiento_proyectado' => $cumplimientoProyectado,
                'precision' => round($precision, 2),
                'nivel_confianza' => $nivelConfianza,
                'meses_analizados' => count($datos),
                'factor_confianza' => $factorConfianza,
                'tendencia' => $regresion['pendiente'] > 1000 ? 'creciente' : 'decreciente',
                'variacion_mensual' => round($regresion['pendiente'], 2),
                'r2' => round($r2, 4),
                'metodo_principal' => 'regresion_ponderada',
                'prediccion_exponencial' => round($prediccionExponencial, 2),
            ],
            'recomendaciones' => $recomendaciones,
            'grafico' => $this->generarDatosGrafico($datos, $regresion, $mesActual)
        ];
    }

    private function eliminarOutliers(array $datos): array
    {
        if (count($datos) < 5) {
            return $datos; 
        }

        $producciones = array_column($datos, 'produccion');
        sort($producciones);

        $q1 = $producciones[floor(count($producciones) * 0.25)];
        $q3 = $producciones[floor(count($producciones) * 0.75)];
        $iqr = $q3 - $q1;
        $limiteInferior = $q1 - 1.5 * $iqr;
        $limiteSuperior = $q3 + 1.5 * $iqr;

        $datosFiltrados = array_filter($datos, function ($item) use ($limiteInferior, $limiteSuperior) {
            return $item['produccion'] >= $limiteInferior && $item['produccion'] <= $limiteSuperior;
        });

        if (count($datosFiltrados) < max(3, count($datos) * 0.5)) {
            return $datos;
        }

        return array_values($datosFiltrados);
    }

    private function aplicarPonderacion(array $datos): array
    {
        $n = count($datos);
        $factor = 0.9; 

        foreach ($datos as $index => &$item) {
            $peso = pow($factor, $n - $index - 1);
            $item['peso'] = $peso;
        }

        return $datos;
    }

    private function calcularRegresionLinealPonderada(array $datos): array
    {
        $n = count($datos);
        $sumPesos = array_sum(array_column($datos, 'peso') ?? array_fill(0, $n, 1));
        
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;

        foreach ($datos as $punto) {
            $x = $punto['mes'];
            $y = $punto['produccion'];
            $w = $punto['peso'] ?? 1;

            $sumX += $w * $x;
            $sumY += $w * $y;
            $sumXY += $w * $x * $y;
            $sumX2 += $w * $x * $x;
            $sumY2 += $w * $y * $y;
        }

        $pendiente = ($sumPesos * $sumXY - $sumX * $sumY) / ($sumPesos * $sumX2 - $sumX * $sumX);
        $interseccion = ($sumY - $pendiente * $sumX) / $sumPesos;

        // Calcular R² ponderado
        $mediaY = $sumY / $sumPesos;
        $ssTotal = 0;
        $ssResidual = 0;

        foreach ($datos as $punto) {
            $x = $punto['mes'];
            $y = $punto['produccion'];
            $w = $punto['peso'] ?? 1;
            $yPredicho = $pendiente * $x + $interseccion;

            $ssTotal += $w * pow($y - $mediaY, 2);
            $ssResidual += $w * pow($y - $yPredicho, 2);
        }

        $r2 = $ssTotal > 0 ? 1 - ($ssResidual / $ssTotal) : 0;

        return [
            'pendiente' => $pendiente,
            'interseccion' => $interseccion,
            'r2' => $r2,
            'n' => $n
        ];
    }

    private function predecirConRegresion(array $regresion): float
    {
        $proximoMes = $regresion['n'] + 1;
        $prediccion = $regresion['pendiente'] * $proximoMes + $regresion['interseccion'];
        return max(0, $prediccion);
    }

    private function suavizamientoExponencial(array $datos): float
    {
        $alpha = 0.3;
        $ultimoValor = end($datos)['produccion'];
        $prediccionAnterior = $ultimoValor;
        $valores = array_reverse($datos);
        foreach ($valores as $item) {
            $prediccionAnterior = $alpha * $item['produccion'] + (1 - $alpha) * $prediccionAnterior;
        }

        return max(0, $prediccionAnterior);
    }

    private function calcularPrecisionMejorada(array $datos, array $regresion): float
    {
        $errores = [];
        foreach ($datos as $punto) {
            $x = $punto['mes'];
            $y = $punto['produccion'];
            $yPredicho = $regresion['pendiente'] * $x + $regresion['interseccion'];

            if ($y > 0) {
                $error = abs(($y - $yPredicho) / $y) * 100;
                $errores[] = $error;
            }
        }

        if (empty($errores)) {
            return 0;
        }

        // MAPE: promedio de errores porcentuales
        $mape = array_sum($errores) / count($errores);
        $precision = max(0, 100 - $mape);
        return min(100, $precision);
    }

    private function determinarNivelConfianza(float $precision, int $meses, float $r2): string
    {
        $puntaje = 0;
        if ($meses >= 12) $puntaje += 30;
        elseif ($meses >= 8) $puntaje += 20;
        else $puntaje += 10;

        if ($precision >= 85) $puntaje += 40;
        elseif ($precision >= 70) $puntaje += 25;
        else $puntaje += 10;

        if ($r2 >= 0.6) $puntaje += 30;
        elseif ($r2 >= 0.3) $puntaje += 15;
        else $puntaje += 5;

        if ($puntaje >= 80) return 'alto';
        elseif ($puntaje >= 50) return 'medio';
        else return 'bajo';
    }

    private function generarRecomendacionesAvanzadas(
        float $cumplimiento,
        float $tendencia,
        float $r2,
        float $precision
    ): array {
        $recomendaciones = [];
        if ($cumplimiento >= 95) {
            $recomendaciones[] = 'Excelente proyección. Mantener las estrategias actuales y buscar crecimiento sostenible.';
        } elseif ($cumplimiento >= 75) {
            $recomendaciones[] = 'Buena proyección. Considerar acciones para alcanzar el 100%.';
        } elseif ($cumplimiento >= 50) {
            $recomendaciones[] = 'Proyección media. Requiere análisis de causas y acciones correctivas.';
        } else {
            $recomendaciones[] = 'Proyección crítica. Se requiere intervención urgente.';
        }

        // Recomendación por tendencia
        if ($tendencia > 50000) {
            $recomendaciones[] = 'Tendencia positiva muy fuerte. Aprovechar para invertir y capturar más mercado.';
        } elseif ($tendencia > 10000) {
            $recomendaciones[] = 'Tendencia positiva. Mantener el ritmo y buscar eficiencias.';
        } elseif ($tendencia > -10000) {
            $recomendaciones[] = 'Tendencia estable. Buscar oportunidades de mejora incremental.';
        } elseif ($tendencia > -50000) {
            $recomendaciones[] = 'Tendencia negativa moderada. Revisar estrategias comerciales.';
        } else {
            $recomendaciones[] = 'Tendencia negativa fuerte. Urge revisar modelo de negocio y operaciones.';
        }

        // Recomendación por calidad del modelo
        if ($r2 < 0.3) {
            $recomendaciones[] = 'El modelo tiene baja precisión (R² bajo). Los datos pueden tener mucha variabilidad o outliers. Considerar análisis más detallado.';
        } elseif ($r2 < 0.6) {
            $recomendaciones[] = 'El modelo tiene precisión media. Mejorar con más datos históricos o incluir variables adicionales.';
        } else {
            $recomendaciones[] = 'El modelo tiene buena precisión (R² alto). Confiable para toma de decisiones.';
        }

        return $recomendaciones;
    }

    private function obtenerDatosHistoricos(int $meses): array
    {
        $fin = Carbon::now()->subMonth();
        $inicio = $fin->copy()->subMonths($meses - 1)->startOfMonth();
        
        $resultados = DB::select("
            SELECT 
                YEAR(fecha_viaje) as year,
                MONTH(fecha_viaje) as month,
                COALESCE(SUM(produccion), 0) as total
            FROM op_despacho
            WHERE estado <> 10
                AND fecha_viaje BETWEEN ? AND ?
            GROUP BY YEAR(fecha_viaje), MONTH(fecha_viaje)
            ORDER BY year, month
        ", [$inicio->toDateString(), $fin->toDateString()]);
        
        $datosPorMes = [];
        $mesIndex = 1;
        $current = $inicio->copy();
        
        while ($current->lte($fin)) {
            $mesNumero = $current->month;

            $anio = $current->year;
            $nombreMes = OpGoalTravelService::MESES[$mesNumero] ?? $current->format('F');
            $datosPorMes[$mesIndex] = [
                'mes' => $mesIndex,
                'periodo' => $nombreMes. ' ' . $anio,
                'produccion' => 0,
                'year' => $current->year,
                'month' => $current->month
            ];
            $mesIndex++;
            $current->addMonth();
        }
        
        foreach ($resultados as $row) {
            foreach ($datosPorMes as $idx => $data) {
                if ($data['year'] == $row->year && $data['month'] == $row->month) {
                    $datosPorMes[$idx]['produccion'] = (float) $row->total;
                    break;
                }
            }
        }
        
        $datosValidos = array_filter($datosPorMes, function($item) {
            return $item['produccion'] > 0;
        });
        if (count($datosValidos) < 3) {
            return array_values($datosPorMes);
        }
        
        return array_values($datosValidos);
    }

    private function obtenerMetaMes(int $year, int $month): float
    {
        $meta = \App\Models\tp\comercial\OpGoalTravel::where('status_deleted', 1)
            ->whereYear('fecha', $year)
            ->whereMonth('fecha', $month)
            ->first();
        return $meta ? (float) $meta->total : 0;
    }

    private function generarDatosGrafico(array $datos, array $regresion, Carbon $mesActual): array
    {
        $grafico = [];
        foreach ($datos as $punto) {
            $grafico[] = [
                'periodo' => $punto['periodo'],
                'real' => $punto['produccion'],
                'tendencia' => round($regresion['pendiente'] * $punto['mes'] + $regresion['interseccion'], 2),
                'es_prediccion' => false
            ];
        }
        
        $ultimoMes = $regresion['n'];
        $mesPrediccion = $ultimoMes + 1;
        $prediccion = round($regresion['pendiente'] * $mesPrediccion + $regresion['interseccion'], 2);

        $mesNumero = $mesActual->month;
        $anio = $mesActual->year;
        $nombreMes = OpGoalTravelService::MESES[$mesNumero] ?? $mesActual->format('F');
        
        $grafico[] = [
            'periodo' => $nombreMes . ' ' . $anio,
            'real' => null,
            'tendencia' => $prediccion,
            'es_prediccion' => true
        ];
        
        return $grafico;
    }

    private function respuestaError(string $mensaje): array
    {
        return [
            'success' => false,
            'message' => $mensaje,
            'prediccion' => null,
            'precision' => 0,
            'nivel_confianza' => 'bajo'
        ];
    }


}
