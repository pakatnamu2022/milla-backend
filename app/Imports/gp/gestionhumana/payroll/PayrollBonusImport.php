<?php

namespace App\Imports\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\payroll\PayrollBonus;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

/**
 * Importa bonificaciones (gh_payroll_bonuses) desde el Excel generado por
 * PayrollBonusTemplateExport, para un periodo y tipo de bono fijos (elegidos en el modal antes de
 * subir el archivo). Mismo patrón que WorkingConditionImport, cambiando el destino.
 */
class PayrollBonusImport implements ToCollection
{
    private int $periodId;
    private int $typeId;
    private array $results = [
        'created' => 0,
        'updated' => 0,
        'errors' => [],
        'rows_processed' => 0,
        'skipped' => 0,
    ];

    public function __construct(int $periodId, int $typeId)
    {
        $this->periodId = $periodId;
        $this->typeId = $typeId;
    }

    public function collection(Collection $rows): void
    {
        // Procesar desde la fila 3 (índice 2)
        // Fila 0: título (ignorar)
        // Fila 1: encabezados (ignorar) → columna A = DNI, columna B = MONTO
        // Fila 2 en adelante: datos
        $dataStartIndex = 2;

        for ($i = $dataStartIndex; $i < $rows->count(); $i++) {
            $rowNumber = $i + 1; // +1 para numeración de Excel
            $rowArray = $rows[$i]->toArray();

            $dni = isset($rowArray[0]) ? trim($rowArray[0]) : null;
            $amount = isset($rowArray[1]) ? $rowArray[1] : null;

            // Detener si ambos están vacíos (fin de datos)
            if (empty($dni) && (empty($amount) || $amount === 0)) {
                break;
            }

            if (empty($dni)) {
                $this->results['skipped']++;
                continue;
            }

            try {
                $this->processRow($dni, $amount, $rowNumber);
                $this->results['rows_processed']++;
            } catch (Exception $e) {
                $this->results['errors'][] = "Fila {$rowNumber}: " . $e->getMessage();
            }
        }
    }

    private function processRow(string $dni, $amount, int $rowNumber): void
    {
        // Normalizar DNI: siempre string con 8 dígitos (relleno con ceros a la izquierda)
        $dniNormalized = str_pad($dni, 8, '0', STR_PAD_LEFT);

        $amountValue = $this->parseDecimal($amount);

        $worker = Worker::withoutGlobalScope('working')
            ->where('vat', $dniNormalized)
            ->first();

        if (!$worker) {
            throw new Exception("No se encontró trabajador con DNI: {$dniNormalized}");
        }

        DB::beginTransaction();
        try {
            $existingRecord = PayrollBonus::where('worker_id', $worker->id)
                ->where('period_id', $this->periodId)
                ->where('type_id', $this->typeId)
                ->first();

            $data = [
                'worker_id' => $worker->id,
                'period_id' => $this->periodId,
                'type_id' => $this->typeId,
                'amount' => $amountValue,
                'status' => 1,
            ];

            if ($existingRecord) {
                $existingRecord->update($data);
                $this->results['updated']++;
            } else {
                PayrollBonus::create($data);
                $this->results['created']++;
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function parseDecimal($value): float
    {
        if (empty($value)) {
            return 0.00;
        }

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
