<?php

namespace App\Imports\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\payroll\WorkingCondition;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class WorkingConditionImport implements ToCollection
{
    private int $periodId;
    private array $results = [
        'created' => 0,
        'updated' => 0,
        'errors' => [],
        'rows_processed' => 0,
        'skipped' => 0,
    ];

    public function __construct(int $periodId)
    {
        $this->periodId = $periodId;
    }

    public function collection(Collection $rows): void
    {
        // Procesar desde la fila 3 (índice 2)
        // Fila 0: título (ignorar)
        // Fila 1: encabezados (ignorar)
        // Fila 2 en adelante: datos
        $dataStartIndex = 2;

        for ($i = $dataStartIndex; $i < $rows->count(); $i++) {
            $rowNumber = $i + 1; // +1 para numeración de Excel
            $rowArray = $rows[$i]->toArray();

            // Obtener valores de columnas B (índice 1) y C (índice 2)
            $dni = isset($rowArray[1]) ? trim($rowArray[1]) : null;
            $ct = isset($rowArray[2]) ? $rowArray[2] : null;

            // Detener si ambos están vacíos (fin de datos)
            if (empty($dni) && (empty($ct) || $ct === 0)) {
                break;
            }

            // Omitir fila si DNI está vacío
            if (empty($dni)) {
                $this->results['skipped']++;
                continue;
            }

            try {
                $this->processRow($dni, $ct, $rowNumber);
                $this->results['rows_processed']++;
            } catch (Exception $e) {
                $this->results['errors'][] = "Fila {$rowNumber}: " . $e->getMessage();
            }
        }
    }

    /**
     * Procesa una fila de datos
     */
    private function processRow(string $dni, $ct, int $rowNumber): void
    {
        // Normalizar DNI: siempre string con 8 dígitos (relleno con ceros a la izquierda)
        $dniNormalized = str_pad($dni, 8, '0', STR_PAD_LEFT);

        // Normalizar monto CT: convertir a decimal
        $ctValue = $this->parseDecimal($ct);

        // Buscar worker_id por DNI (vat)
        $worker = Worker::withoutGlobalScope('working')
            ->where('vat', $dniNormalized)
            ->first();

        if (!$worker) {
            throw new Exception("No se encontró trabajador con DNI: {$dniNormalized}");
        }

        DB::beginTransaction();
        try {
            // Buscar si ya existe un registro para este trabajador y periodo
            $existingRecord = WorkingCondition::where('worker_id', $worker->id)
                ->where('period_id', $this->periodId)
                ->first();

            $data = [
                'worker_id' => $worker->id,
                'period_id' => $this->periodId,
                'amount' => $ctValue,
                'status' => 1,
            ];

            if ($existingRecord) {
                $existingRecord->update($data);
                $this->results['updated']++;
            } else {
                WorkingCondition::create($data);
                $this->results['created']++;
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Convierte un valor a decimal
     */
    private function parseDecimal($value): float
    {
        if (empty($value)) {
            return 0.00;
        }

        // Si es string, quitar prefijos de moneda (S/, $, etc.) y comas
        if (is_string($value)) {
            $value = preg_replace('/[^\d.,\-]/', '', trim($value));
            $value = str_replace(',', '', $value);
        }

        return (float) number_format((float) $value, 2, '.', '');
    }

    public function getResults(): array
    {
        return $this->results;
    }
}