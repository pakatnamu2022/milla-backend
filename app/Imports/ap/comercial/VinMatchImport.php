<?php

namespace App\Imports\ap\comercial;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class VinMatchImport implements ToCollection
{
  public Collection $vins;

  public function __construct()
  {
    $this->vins = collect();
  }

  public function collection(Collection $collection)
  {
    $allVins = $collection->map(function ($row) {
      return strtoupper(trim((string)($row[0] ?? '')));
    })->filter(fn($vin) => !empty($vin));

    // Omitir fila de encabezado si el primer valor parece un header
    $headerKeywords = ['VIN', 'VIN NUMBER', 'NUMERO VIN', 'NUMERO DE VIN', 'N° VIN', 'NRO VIN'];

    if ($allVins->isNotEmpty() && in_array($allVins->first(), $headerKeywords)) {
      $this->vins = $allVins->slice(1)->values();
    } else {
      $this->vins = $allVins->values();
    }
  }
}
