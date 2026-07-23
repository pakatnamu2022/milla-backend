<?php

namespace App\Exports\ap\postventa\taller;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrderQuotationExportSimple implements FromArray, WithHeadings
{
  protected array $quotation;

  public function __construct(array $quotation)
  {
    $this->quotation = $quotation;
  }

  public function array(): array
  {
    return [
      ['Número de Cotización:', $this->quotation['quotation_number'] ?? 'N/A'],
      ['Cliente:', $this->quotation['customer_name'] ?? 'N/A'],
      ['Total:', $this->quotation['total_amount'] ?? 0],
    ];
  }

  public function headings(): array
  {
    return [
      ['COTIZACIÓN - TEST']
    ];
  }
}