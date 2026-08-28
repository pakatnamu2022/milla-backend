<?php

namespace App\Exports\gp\gestionhumana\payroll;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Plantilla vacía con el formato exacto que espera PayrollInsuranceFesaludImport:
 * N° Doc. | Apellido Paterno | Apellido Materno | Nombres | Aporte Mensual inc IGV (Tarifa)
 */
class PayrollInsuranceFesaludTemplateExport implements FromArray, WithHeadings, WithTitle
{
  public function array(): array
  {
    // Fila de ejemplo para guiar el llenado; se puede borrar antes de importar.
    return [
      ['12345678', 'PEREZ', 'GOMEZ', 'JUAN CARLOS', '150.00'],
    ];
  }

  public function headings(): array
  {
    return [
      'N° Doc.',
      'Apellido Paterno',
      'Apellido Materno',
      'Nombres',
      'Aporte Mensual inc IGV (Tarifa)',
    ];
  }

  public function title(): string
  {
    return 'FESALUD';
  }
}
