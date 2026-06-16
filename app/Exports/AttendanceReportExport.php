<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AttendanceReportExport implements WithMultipleSheets
{
  public function __construct(
    private readonly \Illuminate\Support\Collection $absentRows,
    private readonly \Illuminate\Support\Collection $lateRows,
  ) {}

  public function sheets(): array
  {
    $absentColumns = [
      'dni'           => 'DNI',
      'full_name'     => 'Nombre Completo',
      'jefe_directo'  => 'Jefe Directo',
      'cargo'         => 'Cargo',
      'area'          => 'Área',
      'sede'          => 'Sede',
    ];

    $lateColumns = [
      'dni'           => 'DNI',
      'full_name'     => 'Nombre Completo',
      'jefe_directo'  => 'Jefe Directo',
      'check_in'      => 'Hora Marcación',
      'schedule'      => 'Hora Programada',
      'minutes_late'  => 'Minutos de Atraso',
      'cargo'         => 'Cargo',
      'area'          => 'Área',
      'sede'          => 'Sede',
    ];

    return [
      new GeneralExport($this->absentRows, $absentColumns, 'Sin Marcación'),
      new GeneralExport($this->lateRows, $lateColumns, 'Tardanzas'),
    ];
  }
}
