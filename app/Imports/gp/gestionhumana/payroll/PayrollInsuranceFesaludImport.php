<?php

namespace App\Imports\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\payroll\PayrollInsurance;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Collection;

class PayrollInsuranceFesaludImport implements ToCollection
{
    private int $periodId;
    private int $businessPartnerId;
    private array $results = [
        'created' => 0,
        'updated' => 0,
        'errors' => [],
        'rows_processed' => 0,
        'skipped' => 0,
    ];

    public function __construct(int $periodId, int $businessPartnerId)
    {
        $this->periodId = $periodId;
        $this->businessPartnerId = $businessPartnerId;
    }

    public function collection(Collection $rows): void
    {
        // Buscar la fila de encabezados (la que contiene "N° Doc.")
        $headerRowIndex = $this->findHeaderRow($rows);

        if ($headerRowIndex === null) {
            $this->results['errors'][] = "No se encontró la fila de encabezados con 'N° Doc.'";
            return;
        }

        // Obtener los encabezados y mapear las columnas
        $headers = $rows[$headerRowIndex]->toArray();
        $columnMapping = $this->mapColumns($headers);

        // Validar que se encontraron todas las columnas requeridas
        $requiredColumns = ['num_doc', 'apellido_paterno', 'apellido_materno', 'nombres', 'aporte_mensual'];
        foreach ($requiredColumns as $col) {
            if (!isset($columnMapping[$col])) {
                $this->results['errors'][] = "No se encontró la columna requerida: " . $this->getOriginalColumnName($col);
                return;
            }
        }

        // Procesar las filas de datos (empiezan después de los encabezados)
        $dataStartIndex = $headerRowIndex + 1;
        for ($i = $dataStartIndex; $i < $rows->count(); $i++) {
            $rowNumber = $i + 1; // +1 para numeración de Excel
            $rowArray = $rows[$i]->toArray();

            // Detener si la fila está vacía o es TOTAL
            if ($this->isRowEmpty($rowArray) || $this->isTotalRow($rowArray)) {
                break;
            }

            try {
                $this->processRow($rowArray, $columnMapping, $rowNumber);
                $this->results['rows_processed']++;
            } catch (Exception $e) {
                $this->results['errors'][] = "Fila {$rowNumber}: " . $e->getMessage();
            }
        }
    }

    /**
     * Busca la fila que contiene los encabezados (la que tiene "N° Doc.")
     */
    private function findHeaderRow(Collection $rows): ?int
    {
        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            foreach ($rowArray as $cell) {
                $normalizedCell = $this->normalizeHeader($cell);
                if (str_contains($normalizedCell, 'N° DOC') || str_contains($normalizedCell, 'Nº DOC')) {
                    return $index;
                }
            }
        }
        return null;
    }

    /**
     * Mapea las columnas del Excel a nuestros campos internos
     */
    private function mapColumns(array $headers): array
    {
        $mapping = [];

        foreach ($headers as $index => $header) {
            $normalized = $this->normalizeHeader($header);

            if (str_contains($normalized, 'N° DOC') || str_contains($normalized, 'Nº DOC')) {
                $mapping['num_doc'] = $index;
            } elseif ($normalized === 'APELLIDO PATERNO') {
                $mapping['apellido_paterno'] = $index;
            } elseif ($normalized === 'APELLIDO MATERNO') {
                $mapping['apellido_materno'] = $index;
            } elseif ($normalized === 'NOMBRES') {
                $mapping['nombres'] = $index;
            } elseif (str_contains($normalized, 'APORTE MENSUAL') && str_contains($normalized, 'IGV')) {
                $mapping['aporte_mensual'] = $index;
            }
        }

        return $mapping;
    }

    /**
     * Normaliza el texto de un encabezado (quita saltos de línea, espacios extras, etc.)
     */
    private function normalizeHeader(?string $header): string
    {
        if (empty($header)) {
            return '';
        }

        // Quitar saltos de línea
        $header = str_replace(["\n", "\r", "\r\n"], ' ', $header);

        // Quitar espacios duplicados
        $header = preg_replace('/\s+/', ' ', $header);

        // Convertir a mayúsculas y recortar
        return strtoupper(trim($header));
    }

    /**
     * Procesa una fila de datos
     */
    private function processRow(array $row, array $columnMapping, int $rowNumber): void
    {
        // Extraer valores usando el mapeo de columnas
        $numDoc = $this->getCellValue($row, $columnMapping['num_doc']);
        $apellidoPaterno = $this->getCellValue($row, $columnMapping['apellido_paterno']);
        $apellidoMaterno = $this->getCellValue($row, $columnMapping['apellido_materno']);
        $nombres = $this->getCellValue($row, $columnMapping['nombres']);
        $aporteMensual = $this->getCellValue($row, $columnMapping['aporte_mensual']);

        // Validar que al menos el documento esté presente
        if (empty($numDoc)) {
            throw new Exception('El campo "N° Doc." es requerido');
        }

        // Concatenar nombres completos para contracting_name
        $contractingName = trim(implode(' ', array_filter([
            trim($apellidoPaterno),
            trim($apellidoMaterno),
            trim($nombres)
        ])));

        if (empty($contractingName)) {
            throw new Exception('Los campos de nombre (Apellido Paterno, Apellido Materno, Nombres) no pueden estar todos vacíos');
        }

        // Buscar worker_id por vat (num_doc)
        $worker = Worker::withoutGlobalScope('working')
            ->where('vat', trim($numDoc))
            ->first();

        if (!$worker) {
            throw new Exception("No se encontró trabajador con documento: {$numDoc}");
        }

        // Limpiar y validar aporte mensual
        $rateWithTaxValue = $this->parseDecimal($aporteMensual);

        DB::beginTransaction();
        try {
            // Buscar si ya existe un registro con los mismos datos
            // Como doc_number_affiliate es null, buscamos por worker + period + business_partner
            $existingRecord = PayrollInsurance::where('worker_id', $worker->id)
                ->where('period_id', $this->periodId)
                ->where('business_partner_id', $this->businessPartnerId)
                ->whereNull('doc_number_affiliate')
                ->first();

            $data = [
                'worker_id' => $worker->id,
                'period_id' => $this->periodId,
                'business_partner_id' => $this->businessPartnerId,
                'doc_number_affiliate' => null,
                'rate_with_tax' => $rateWithTaxValue,
                'contracting_name' => $contractingName,
                'num_doc_contracting' => trim($numDoc),
            ];

            if ($existingRecord) {
                $existingRecord->update($data);
                $this->results['updated']++;
            } else {
                PayrollInsurance::create($data);
                $this->results['created']++;
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtiene el valor de una celda del array
     */
    private function getCellValue(array $row, int $columnIndex): ?string
    {
        return $row[$columnIndex] ?? null;
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

    /**
     * Verifica si una fila está completamente vacía
     */
    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (!empty($value) && trim($value) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Verifica si una fila es la fila de TOTAL
     */
    private function isTotalRow(array $row): bool
    {
        foreach ($row as $value) {
            if (empty($value)) {
                continue;
            }

            $valueStr = strtoupper(trim($value));

            // Detectar si contiene "TOTAL"
            if (str_contains($valueStr, 'TOTAL')) {
                return true;
            }

            // Detectar si es una fórmula de suma (=SUM...)
            if (is_string($value) && (str_starts_with($value, '=SUM') || str_starts_with($value, '=SUMA'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtiene el nombre original de una columna para mensajes de error
     */
    private function getOriginalColumnName(string $internalName): string
    {
        $names = [
            'num_doc' => 'N° Doc.',
            'apellido_paterno' => 'Apellido Paterno',
            'apellido_materno' => 'Apellido Materno',
            'nombres' => 'Nombres',
            'aporte_mensual' => 'Aporte Mensual inc IGV (Tarifa)',
        ];

        return $names[$internalName] ?? $internalName;
    }

    public function getResults(): array
    {
        return $this->results;
    }
}