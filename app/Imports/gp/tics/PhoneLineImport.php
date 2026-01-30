<?php

namespace App\Imports\gp\tics;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class PhoneLineImport implements ToCollection, WithHeadingRow
{
    private $rows = [];

    /**
     * Procesar cada fila del Excel
     */
    public function collection(Collection $rows)
    {
        $this->rows = $rows->map(function ($row) {
            return [
                'ruc' => $row['ruc'] ?? null,
                'razon' => $row['razon'] ?? null,
                'cuenta' => $row['cuenta'] ?? null,
                'linea' => $row['linea'] ?? null,
                'plan' => $row['plan'] ?? null,
            ];
        })->filter(function ($row) {
            // Filtrar filas vacías
            return !empty($row['ruc']) && !empty($row['linea']) && !empty($row['plan']);
        });
    }

    /**
     * Obtener las filas procesadas
     */
    public function getRows()
    {
        return $this->rows;
    }
}
