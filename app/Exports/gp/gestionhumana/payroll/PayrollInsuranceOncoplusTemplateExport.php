<?php

namespace App\Exports\gp\gestionhumana\payroll;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Plantilla vacía con el formato exacto que espera PayrollInsuranceOncoplusImport:
 * Núm Doc Afiliado | Tarifa con IGV | Contratante | Núm Doc Contratante
 */
class PayrollInsuranceOncoplusTemplateExport implements FromArray, WithHeadings, WithTitle
{
  public function array(): array
  {
    // Fila de ejemplo para guiar el llenado; se puede borrar antes de importar.
    return [
      ['87654321', '150.00', 'PEREZ GOMEZ JUAN CARLOS', '12345678'],
    ];
  }

  public function headings(): array
  {
    return [
      'Núm Doc Afiliado',
      'Tarifa con IGV',
      'Contratante',
      'Núm Doc Contratante',
    ];
  }

  public function title(): string
  {
    return 'ONCOSALUD';
  }
}
